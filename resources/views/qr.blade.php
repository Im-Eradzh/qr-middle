<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Payment System</title>
    @vite('resources/css/app.css')    
</head>
<body class="flex flex-col items-center justify-center min-h-screen bg-gray-100">
    <div class="bg-white shadow-lg rounded-lg p-6 flex flex-col items-center text-center">
        <h1 class="text-2xl font-bold mb-4">长按二维码扫描付款</h1>
        <p class="text-green-600 text-xl font-semibold mb-2">¥ {{ round($order['orderAmount'] / 0.63, 2) }}</p>
        <img src="{{ $qrCodeDataUri }}" alt="QR Code" class="w-64 h-64 mb-4">
        <p class="text-gray-700 font-medium">账单代码: <span class="font-semibold">{{ $order['orderId'] }}</span></p>
        <p class="text-gray-600 mt-2">您的付款将在安全可靠的环境中处理！</p>
    </div>
    <script>
        function checkPaymentStatus() {
            fetch("{{ route('check.status', ['orderId' => $order->id]) }}")
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        window.location.href = "{{ $order->returnUrl }}";
                    } else if (data.status === 'failed') {                        
                        window.location.href = "{{ $order->returnUrl }}";
                    } else {
                        setTimeout(checkPaymentStatus, 5000); // Retry every 5 seconds
                    }
                })
                .catch(error => console.error("Error checking payment status:", error));
        }
    
        setTimeout(checkPaymentStatus, 5000);
    </script>
</body>
</html>
