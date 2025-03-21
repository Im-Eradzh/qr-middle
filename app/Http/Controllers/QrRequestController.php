<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;

class QrRequestController extends Controller
{        
    public function requestQr(Request $request)
    {
        // Validate request data
        $validatedData = $request->validate([
            'merchantId'  => 'required|string|max:255',
            'orderId'     => 'required|string|max:255|unique:orders,orderId',
            'orderAmount' => 'required|numeric|min:0',
            'channelType' => 'required|string|max:255',
            'notifyUrl'   => 'required|url|max:255',            
            'returnUrl'   => 'required|url|max:255',
        ]);

        // Store order in the database
        $order = Order::create($validatedData);

        // Redirect to product page with Order ID
        return response()->json([
            'redirect_url' => route('product.page', ['orderId' => $order->id])
        ]);
    }
    
}
