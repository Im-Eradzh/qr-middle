<?php

namespace App\Livewire;

use Livewire\Component;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Log;

class Payment extends Component
{
    public $amount = 0.00;
    public ?string $qrCode = null;
    public ?string $transactionRefno = null;
    public ?string $status = null;
    public ?string $token = null;

    // Request QR code
    public function requestQr(): void
    {
        $this->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        try {
            $client = new Client();

            // Step 1: Login to get token
            $response = $client->post(env('QR_LOGIN_URL'), [
                'json' => [
                    'email' => 'test@mail.com',
                    'password' => 'password',
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            $this->token = $data['access_token'] ?? '';

            if (!$this->token) {
                throw new \Exception("Failed to get API token");
            }

            // Step 2: Request QR
            $response = $client->post('https://qr.test/api/request-qr', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                ],
                'json' => [
                    'username' => 'SEANG888',
                    'amount' => $this->amount,
                ]
            ]);

            $qrData = json_decode($response->getBody(), true);

            if (!isset($qrData['data'][0]['qr_data'])) {
                throw new \Exception("Invalid QR response format");
            }

            $qrCodeData = $qrData['data'][0]['qr_data'];
            $this->transactionRefno = $qrData['data'][0]['transaction_refno'] ?? null;

            // Step 3: Generate QR Code
            $qrCode = new QrCode($qrCodeData);
            $writer = new PngWriter();
            $result = $writer->write($qrCode);
            $this->qrCode = base64_encode($result->getString());
        } catch (RequestException $e) {
            Log::error("Request QR Error: " . $e->getMessage());
            $this->dispatch('qr-error', 'Failed to generate QR code. Please try again.');
        } catch (\Exception $e) {
            Log::error("General Error: " . $e->getMessage());
            $this->dispatch('qr-error', $e->getMessage());
        }
    }

    // Check transaction status
    public function checkTransactionStatus(): void
    {
        if (!$this->transactionRefno) {
            $this->dispatch('qr-error', 'No transaction reference found.');
            return;
        }

        
        try {
            $client = new Client();

            $response = $client->post('https://qr.test/api/transaction/status', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                ],
                'json' => [
                    'transaction_refno' => $this->transactionRefno,
                ]
            ]);

            $status = json_decode($response->getBody(), true);            
            
            $this->status = $status['data']['status'] ?? 'Unknown status';
        } catch (RequestException $e) {
            Log::error("Transaction Check Error: " . $e->getMessage());
            $this->dispatch('qr-error', 'Error checking transaction status.');
        }
    }

    public function render()
    {
        return view('livewire.payment');
    }
}

