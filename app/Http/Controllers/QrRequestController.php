<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Validation\ValidationException;

class QrRequestController extends Controller
{
    protected $secretKey;

    public function __construct()
    {
        $this->secretKey = env('QR_SECRET_KEY', 'default_secret_key');
    }

    public function requestQr(Request $request)
    {
        try {
            // Validate request data
            $validatedData = $request->validate([
                'merchantId'  => 'required|string|max:255',
                'orderId'     => 'required|string|max:255|unique:orders,orderId',
                'orderAmount' => 'required|numeric|min:0.01',
                'channelType' => 'required|string|max:255',
                'notifyUrl'   => 'required|url|max:255',            
                'returnUrl'   => 'required|url|max:255',
                'sign'        => 'required|string',
            ]);

            // Generate signature for verification
            $generatedSign = $this->generateSignature($validatedData);
            
            if ($generatedSign !== $validatedData['sign']) {
                Log::warning('Invalid signature for order: ' . $validatedData['orderId']);
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to generate QR code.',
                    'error' => 'Invalid signature.',
                ], 403);
            }

            // Generate a unique token
            $token = Str::uuid(); // You can also use Str::random(32)

            // Store order in the database with token
            DB::beginTransaction();
            Order::create(array_merge($validatedData, ['token' => $token]));
            DB::commit();

            // Return structured response
            return response()->json([
                'success' => true,
                'message' => 'QR code generated successfully.',
                'redirect_url' => route('order.page', ['token' => $token])
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate QR code.',
                'error' => 'Invalid request parameters.',
            ], 400);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('QR Request Failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate QR code.',
                'error' => 'An error occurred while processing your request. Please try again later.',
            ], 500);
        }
    }

    private function generateSignature(array $data)
    {
        // Remove sign key before generating signature
        unset($data['sign']);
        
        // Sort parameters alphabetically
        ksort($data);

        // Concatenate parameters into a query string
        $queryString = http_build_query($data);

        // Generate HMAC-SHA256 signature using the secret key
        return hash_hmac('sha256', $queryString, $this->secretKey);
    }

    public function orderStatus(Request $request)
    {      
        $validatedData = $request->validate([
            'orderId' => 'required|string|max:255',
        ]);

        try {
            $order = Order::where('orderId', $validatedData['orderId'])->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found.',
                    'error' => 'Invalid orderId.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'orderId' => $order->orderId,
                'status' => $order->status,
            ]);
        } catch (Exception $e) {
            Log::error('Order Status Check Failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve order status.',
                'error' => 'An error occurred while processing your request. Please try again later.',
            ], 500);
            }
    }
}