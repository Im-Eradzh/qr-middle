<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Payment System</title>
    <link rel="preload" as="style" href="{{ $apiUrl . 'build/assets/app-Bsz12FUo.css' }}">
    <link rel="stylesheet" href="{{ $apiUrl . 'build/assets/app-Bsz12FUo.css' }}" data-navigate-track="reload">    
</head>
<body class="flex flex-col items-center justify-center min-h-screen bg-gray-100">
    <div class="bg-white shadow-lg rounded-lg p-6 flex flex-col items-center text-center">
        <h1 class="text-2xl font-bold mb-4">长按二维码扫描付款</h1>
        <p class="text-green-600 text-xl font-semibold mb-2"> RM {{ number_format(round($order->orderAmount, 2), 2) }}</p>
        <img src="{{ $qrCodeDataUri }}" alt="QR Code" class="w-64 h-64 mb-4">
        <p class="text-gray-700 font-medium">账单代码: <span class="font-semibold">{{ $order['orderId'] }}</span></p>
        <p class="text-gray-600 mt-2">您的付款将在安全可靠的环境中处理！</p>
    </div>
    <script>
        const apiUrl = "{{ $apiUrl }}"; // Ensure $apiUrl is passed from the backend
        const orderId = "{{ $order->id }}"; // Get the order ID dynamically
        const returnUrl = "{{ $order->returnUrl }}"; // Ensure return URL is available
    
        function checkPaymentStatus() {
            fetch(`${apiUrl}check-status/${orderId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success' || data.status === 'failed') {
                        window.location.href = returnUrl;
                    } else {
                        setTimeout(checkPaymentStatus, 3000); // Retry every 3 seconds
                    }
                })
                .catch(error => console.error("Error checking payment status:", error));
        }
    
        setTimeout(checkPaymentStatus, 3000);
    </script>
</body>
</html>
