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
    private $apiUrl;

    public function __construct()
    {
        $this->apiUrl = 'https://' . request()->headers->get('host') . '/';
    }


    public function pay($token)
    {
        $order = Order::where('token', $token)->firstOrFail();        

        if (!$this->apiUrl) {
            return response()->json(['error' => 'Failed to retrieve API URL'], 500);
        }

        $apiUrl = $this->apiUrl;

        return view('welcome', compact('order', 'apiUrl'));
    }

    public function generateQR($token)
    {
        $order = Order::where('token', $token)->firstOrFail();

        if ($order->qr_data) {
            return response()->json(['success' => true]);
        }

        $authToken = $this->getApiToken();
        if (!$authToken) {
            return response()->json(['error' => 'Failed to retrieve API token'], 500);
        }

        $response = Http::withToken($authToken)->post(env('QR_REQUEST_URL'), [
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

        $order->update([
            'transaction_refno' => $transactionRefNo, 
            'qr_data' => $qrString,
            'status' => 'pending'
        ]);

        return response()->json(['success' => true]);
    }

    public function showQR($token)
    {
        $order = Order::where('token', $token)->firstOrFail();

        if (!$this->apiUrl) {
            return response()->json(['error' => 'Failed to retrieve API URL'], 500);
        }
        
        $apiUrl = $this->apiUrl;

        if (!$order->qr_data) {
            return redirect()->route('generate-qr', ['token' => $token]);
        }

        $builder = new Builder(
            writer: new PngWriter(),
            writerOptions: [],
            validateResult: false,
            data: $order->qr_data,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 300,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin
        );

        $result = $builder->build();
        $qrCodeDataUri = $result->getDataUri();

        CheckTransactionStatusJob::dispatch($order, 0)->delay(now()->addSeconds(5));

        return view('qr', compact('qrCodeDataUri', 'order', 'apiUrl'));
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
