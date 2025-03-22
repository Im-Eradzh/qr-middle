<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Payment System</title>
    @vite('resources/css/app.css')    
</head>
<body class="flex flex-col items-center justify-center min-h-screen bg-gray-100">    
        
    <div class="text-center mt-4 max-w-sm mx-auto px-4">
        <p class="text-base sm:text-lg font-medium whitespace-nowrap overflow-hidden"> 
            感谢您购买我们的产品。请确认您的订单正确无误。
        </p>
        <p class="mt-2 text-gray-700">您支付的金额</p>
        <p class="text-xl font-bold text-green-600 mt-2">¥ {{ round($order->orderAmount / 0.63, 2) }}</p>
 <!-- Price with CNY symbol -->
        
        <a href="{{ $apiUrl . 'generate-qr/' . $order->token}}" 
            class="mt-4 inline-block w-full bg-green-500 text-white px-6 py-3 rounded-lg font-medium hover:bg-green-600 transition text-center"
            x-data="{ processing: false }"
            x-on:click.prevent="processing = true; $event.target.classList.add('opacity-50', 'cursor-not-allowed'); $event.target.innerText = '处理中...'; window.location = $event.target.href"
            x-bind:disabled="processing">
            确认
        </a> <!-- Confirm Button -->
    
        <p class="mt-10 text-gray-600 text-sm">
            <b>隐私保护：</b> 我们严格遵守隐私保护政策，所有支付信息仅用于本次交易，不会用于其他用途。
        </p> <!-- Short description -->
    
        <img src="{{ asset('images/ape.jpg') }}" alt="Ape NFT Image" class="mt-8 mx-auto w-48 h-48 rounded-lg shadow-lg object-cover">
    
        <p class="mt-4 text-lg font-semibold">Ape NFT</p> <!-- Product Name -->
        <p class="mt-1 text-gray-700">数量: 1</p> <!-- Quantity -->
    </div>
    
    
</body>
</html>
