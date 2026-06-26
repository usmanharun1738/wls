<?php

namespace App\Services;

use AfricasTalking\SDK\AfricasTalking;
use App\Models\Report;
use App\Models\Reward;
use Illuminate\Support\Facades\Http;

class AirtimeService
{
    protected string $currency;

    protected string $apiKey;

    protected string $username;

    protected string $baseUrl;

    public function __construct(protected AfricasTalking $at)
    {
        $this->currency = config('services.africastalking.currency', 'NGN');
        $this->apiKey = config('services.africastalking.api_key');
        $this->username = config('services.africastalking.username', 'sandbox');

        $domain = $this->username === 'sandbox' ? 'api.sandbox.africastalking.com' : 'api.africastalking.com';
        $this->baseUrl = "https://{$domain}/version1/";
    }

    /**
     * Send airtime reward for a verified report.
     */
    public function sendReward(Report $report): bool
    {
        // Prevent duplicate rewards
        if (Reward::where('report_id', $report->id)->where('status', 'sent')->exists()) {
            return false;
        }

        // Send via AT API using JSON (the SDK uses form-encoded which sandbox rejects)
        $response = Http::withHeaders([
            'apiKey' => $this->apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post($this->baseUrl.'airtime/send', [
            'username' => $this->username,
            'recipients' => [
                [
                    'phoneNumber' => $report->phone_number,
                    'amount' => $this->currency.' '.number_format((float) $report->reward_amount, 2),
                ],
            ],
        ]);

        $data = $response->json();
        $firstResponse = $data['responses'][0] ?? null;

        $reward = Reward::create([
            'report_id' => $report->id,
            'phone_number' => $report->phone_number,
            'amount' => $report->reward_amount,
            'currency_code' => $this->currency,
            'status' => ($firstResponse && ($firstResponse['status'] ?? '') === 'Sent') ? 'sent' : 'failed',
            'transaction_id' => $firstResponse['requestId'] ?? null,
            'error_message' => $firstResponse['errorMessage'] === 'None' ? null : ($firstResponse['errorMessage'] ?? $data['errorMessage'] ?? null),
        ]);

        if ($reward->status === 'sent') {
            $report->update(['reward_sent' => true]);
        }

        return $reward->status === 'sent';
    }

    /**
     * Send airtime directly to a phone number (used for ranger self-request).
     */
    public function sendToPhone(string $phoneNumber, float $amount, ?string $note = null): Reward
    {
        $rangerPhone = $phoneNumber;

        $response = Http::withHeaders([
            'apiKey' => $this->apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post($this->baseUrl.'airtime/send', [
            'username' => $this->username,
            'recipients' => [
                [
                    'phoneNumber' => $rangerPhone,
                    'amount' => $this->currency.' '.number_format($amount, 2),
                ],
            ],
        ]);

        $data = $response->json();
        $firstResponse = $data['responses'][0] ?? null;

        $status = ($firstResponse && ($firstResponse['status'] ?? '') === 'Sent') ? 'sent' : 'failed';
        $txId = $firstResponse['requestId'] ?? null;
        $apiError = $data['errorMessage'] ?? null;
        $respError = $firstResponse['errorMessage'] ?? null;
        $errorMsg = match (true) {
            $respError && $respError !== 'None' => $respError,
            $apiError && $apiError !== 'None' => $apiError,
            default => $note,
        };

        return Reward::create([
            'report_id' => null,
            'phone_number' => $rangerPhone,
            'amount' => $amount,
            'currency_code' => $this->currency,
            'status' => $status,
            'transaction_id' => $txId,
            'error_message' => $errorMsg,
        ]);
    }
}
