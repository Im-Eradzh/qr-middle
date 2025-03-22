<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Merchant;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Validation\ValidationException;

class QrRequestController extends Controller
{
    public function requestQr(Request $request)
    {
        try {
            // Validate request data
            $validatedData = $request->validate([
                'merchantId'  => 'required|string|max:255',
                'merchantKey' => 'required|string|max:255',
                'secretKey'   => 'required|string|max:255',
                'orderId'     => 'required|string|max:255|unique:orders,orderId',
                'orderAmount' => 'required|numeric|min:0.01',
                'channelType' => 'required|string|max:255',
                'notifyUrl'   => 'required|url|max:255',            
                'returnUrl'   => 'required|url|max:255',
            ]);

            // Validate merchant credentials
            $merchant = Merchant::where('merchant_id', $validatedData['merchantId'])
                ->where('merchant_key', $validatedData['merchantKey'])
                ->where('secret_key', $validatedData['secretKey'])
                ->first();                

            if (!$merchant) {
                Log::warning('Invalid merchant credentials: ' . $validatedData['merchantId']);
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to generate QR code.',
                    'error' => 'Invalid merchant credentials.',
                ], 403);
            }

            // Generate a unique token
            $token = Str::uuid(); 

            // Store order in the database with token
            DB::beginTransaction();
            Order::create([
                'merchant_id' => $validatedData['merchantId'],
                'orderId' => $validatedData['orderId'],
                'orderAmount' => $validatedData['orderAmount'],
                'channelType' => $validatedData['channelType'],
                'notifyUrl' => $validatedData['notifyUrl'],
                'returnUrl' => $validatedData['returnUrl'],
                'token' => $token
            ]);
            DB::commit();

            // Return structured response
            return response()->json([
                'success' => true,
                'message' => 'QR code generated successfully.',
                'redirect_url' => route('product.page', ['token' => $token])
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
}

