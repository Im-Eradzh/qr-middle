<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Http;
use Endroid\QrCode\Encoding\Encoding;
use Illuminate\Support\Facades\Cache;
use Endroid\QrCode\RoundBlockSizeMode;
use App\Jobs\CheckTransactionStatusJob;
use Endroid\QrCode\ErrorCorrectionLevel;

class PaymentController extends Controller
{
    public function pay($token)
    {
        $order = Order::where('token', $token)->firstOrFail();
    
         return view('welcome', compact('order'));
    }

    
    public function generateQR($token)
    {        
        $order = Order::where('token', $token)->firstOrFail();

        // Get the API token
        $token = $this->getApiToken();
        if (!$token) {
            return response()->json(['error' => 'Failed to retrieve API token'], 500);
        }        

        // Make the QR API request
        $response = Http::withToken($token)->post(env('QR_REQUEST_URL'), [
            'merchantId'  => $order->merchantId,
            'orderId'     => $order->orderId,
            'orderAmount' => $order->orderAmount,
            'channelType' => $order->channelType,
            'notifyUrl'   => $order->notifyUrl            
        ]);

        if (!$response->successful()) {
            return response()->json(['error' => 'Failed to generate QR code', 'details' => $response->json()], 500);
        }

        $qrData = $response->json();
        $qrString = $qrData['data'][0]['qr_data'] ?? null;
        $transactionRefNo = $qrData['data'][0]['transaction_refno'] ?? null;        

        if (!$qrString || !$transactionRefNo) {
            return response()->json(['error' => 'QR data or transaction reference not found in response'], 500);
        }

        // Update order with transaction reference number
        $order->update(['transaction_refno' => $transactionRefNo, 'status' => 'pending']);

        // Dispatch job for transaction status check
        CheckTransactionStatusJob::dispatch($order, 0)->delay(now()->addSeconds(30));

        // Generate QR Code
        $builder = new Builder(
            writer: new PngWriter(),
            writerOptions: [],
            validateResult: false,
            data: $qrString,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 300,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin
        );

        $result = $builder->build();
        $qrCodeDataUri = $result->getDataUri();

        return view('qr', compact('qrCodeDataUri', 'order'));
    }



    private function getApiToken()
    {
        if (Cache::has('api_access_token')) {
            return Cache::get('api_access_token');
        }

        $response = Http::post(env('QR_LOGIN_URL'), [
            'email'    => env('QR_USERNAME'),
            'password' => env('QR_PASSWORD'),
        ]);

        if ($response->successful()) {
            $tokenData = $response->json();
            $token = $tokenData['access_token'];
            $expiresIn = $tokenData['expires_in'] ?? 3600;

            Cache::put('api_access_token', $token, now()->addSeconds($expiresIn - 60));
            return $token;
        }

        return null;
    }

    public function checkStatus($orderId)
    {
        $order = Order::findOrFail($orderId);
        return response()->json(['status' => $order->status]);
    }

}
