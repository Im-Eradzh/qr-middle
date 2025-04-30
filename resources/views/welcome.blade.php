<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Payment System</title>
    <link rel="preload" as="style" href="{{ $apiUrl . 'build/assets/app-Bsz12FUo.css' }}">
    <link rel="stylesheet" href="{{ $apiUrl . 'build/assets/app-Bsz12FUo.css' }}" data-navigate-track="reload">
</head>
<body class="flex flex-col items-center justify-center min-h-screen bg-gray-100">    
        
    <div class="text-center mt-4 max-w-sm mx-auto px-4">
        <p class="text-lg sm:text-xl font-medium text-red-600 whitespace-nowrap overflow-hidden">
            正在跳转，请耐心等待
        </p>        
        <p class="mt-2 text-gray-700">您支付的金额</p>
        <p class="text-xl font-bold text-green-600 mt-2">
            RM {{ number_format(round($order->orderAmount, 2), 2) }}
        </p>

        <!-- Confirm Button (Auto-disabled) -->
        <button id="confirm-btn"
            class="mt-4 inline-block w-full bg-green-500 text-red-600 px-6 py-3 rounded-lg font-medium transition text-center opacity-50 cursor-not-allowed"
            disabled>
            处理中...
        </button>
    
        <p class="mt-10 text-gray-600 text-sm">
            <b>隐私保护：</b> 我们严格遵守隐私保护政策，所有支付信息仅用于本次交易，不会用于其他用途。
        </p>
    
        <img src="{{ $apiUrl . "images/lucky-painting.webp" }}" alt="Image" class="mt-8 mx-auto w-48 h-48 rounded-lg shadow-lg object-cover">
    
        <p class="mt-4 text-lg font-semibold">鸿运当头国画山水画正方形客厅旭日东升字画办公室靠山聚宝盆图</p>
        <p class="mt-1 text-gray-700"> RM {{ number_format(round($order->orderAmount, 2), 2) }}</p>
    </div>
    
    <script>
        var apiUrl = @json($apiUrl);
        var orderToken = @json($order->token);

        document.addEventListener('DOMContentLoaded', function () {
            let button = document.getElementById('confirm-btn');

            fetch(`${apiUrl}generate-qr/${orderToken}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        let countdown = 3;
                        button.innerText = `${countdown} 秒后跳转...`;

                        let interval = setInterval(() => {
                            countdown--;
                            if (countdown > 0) {
                                button.innerText = `${countdown} 秒后跳转...`;
                            } else {
                                clearInterval(interval);
                                window.location.href = `${apiUrl}show-qr/${orderToken}`;
                            }
                        }, 1000);
                    } else {
                        console.error("QR Code generation failed:", data.error);
                        button.innerText = "生成失败，请刷新重试";
                        button.classList.remove('opacity-50', 'cursor-not-allowed');
                        button.disabled = false;
                    }
                })
                .catch(error => {
                    console.error("Error fetching QR code:", error);
                    button.innerText = "请求错误，请刷新重试";
                    button.classList.remove('opacity-50', 'cursor-not-allowed');
                    button.disabled = false;
                });
        });
    </script>

</body>
</html>
