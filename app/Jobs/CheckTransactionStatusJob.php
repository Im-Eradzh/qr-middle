<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class CheckTransactionStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 10; // Retry 10 times if notification fails
    public $backoff = 30; // Wait 30 seconds before retrying

    protected $order;
    protected $attempt;

    public function __construct(Order $order, int $attempt = 1)
    {
        $this->order = $order;
        $this->attempt = $attempt;
    }

    public function handle(): void
    {
        if (!$this->order->transaction_refno) {
            Log::error("Order {$this->order->id} has no transaction_refno");
            return;
        }

        $token = Cache::get('api_access_token');

        if (!$token) {
            Log::error("Failed to retrieve API token for order {$this->order->id}");
            return;
        }

        $response = Http::withToken($token)->post(env('QR_TRANSACTION_URL'), [
            'transaction_refno' => $this->order->transaction_refno,
        ]);

        if (!$response->successful()) {
            Log::error("Failed to check transaction status for order {$this->order->id}", ['response' => $response->json()]);
            return;
        }

        $jsonResponse = $response->json();
        $data = $jsonResponse['data'] ?? [];

        if (!isset($data['status'])) {
            Log::error("Invalid response format for order {$this->order->id}", ['response' => $jsonResponse]);
            return;
        }

        $status = strtolower($data['status']);
        $message = $data['message'] ?? 'Unknown status';
        $redirectUrl = $this->order->returnUrl;

        if ($status === 'success') {
            $this->order->update(['status' => 'success']);
            Log::info("Order {$this->order->id} transaction successful: $message");
            $this->sendNotification($data, true);
            Http::get($redirectUrl);
            return;
        }

        // Retry mechanism (Max 3 retries)
        if ($this->attempt < 4) {
            $delay = 20; // Retry every 20 seconds
            Log::info("Transaction still pending for Order {$this->order->id}, retrying attempt {$this->attempt} in {$delay} seconds");

            CheckTransactionStatusJob::dispatch($this->order, $this->attempt + 1)
                ->delay(now()->addSeconds($delay));
        } else {
            $this->order->update(['status' => 'failed']);
            Log::info("Order {$this->order->id} transaction failed after max retries.");
            $this->sendNotification($data, false);
            Http::get($redirectUrl);
        }
    }

    private function sendNotification(array $data, bool $isSuccess): void
    {
        $notifyUrl = $this->order->notifyUrl;
        if (!$notifyUrl) {
            Log::warning("No notifyUrl for order {$this->order->id}");
            return;
        }

        $signKey = env('PAYMENT_SIGN_KEY');

        $payload = [
            'merchantId' => $this->order->merchantId,
            'orderId' => $this->order->orderId,
            'status' => $isSuccess ? 'paid' : 'failed',
            'msg' => $data['message'] ?? ($isSuccess ? 'Transaction successful' : 'Transaction failed'),
        ];

        $sign = $this->generateSignature($payload, $signKey);
        $payload['sign'] = $sign;

        $this->order->update(['sign' => $sign]);

        $response = Http::asJson()->post($notifyUrl, $payload);

        if ($response->successful() && ($response->json()['code'] ?? 500) == 200) {
            Log::info("Notified merchant successfully for order {$this->order->id}");
            $this->order->update(['notified' => true]); // ✅ Update flag
        } else {
            Log::error("Failed to notify merchant for order {$this->order->id}", ['response' => $response->json()]);
            
            // Retry notification if it fails
            if ($this->attempt < 10) {
                Log::warning("Retrying notification for order {$this->order->id}, attempt {$this->attempt} in 30 seconds.");
                CheckTransactionStatusJob::dispatch($this->order, $this->attempt + 1)
                    ->delay(now()->addSeconds(30));
            } else {
                Log::error("Notification failed after max retries for order {$this->order->id}. Marking as server not reachable.");
                $this->order->update(['status' => 'failed']);
            }
        }
    }


    private function generateSignature(array $data, string $signKey): string
    {
        $data = array_filter($data);
        ksort($data);
        $queryString = urldecode(http_build_query($data));
        return md5($queryString . '&key=' . $signKey);
    }
}
