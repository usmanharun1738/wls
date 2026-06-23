<?php

namespace App\Services;

use AfricasTalking\SDK\AfricasTalking;
use App\Models\Report;
use App\Models\Reward;

class AirtimeService
{
    protected string $currency;

    public function __construct(protected AfricasTalking $at)
    {
        $this->currency = config('services.africastalking.currency', 'NGN');
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

        // Demo/sandbox mode — simulate successful airtime delivery
        if (config('services.africastalking.airtime_simulate')) {
            $reward = Reward::create([
                'report_id' => $report->id,
                'phone_number' => $report->phone_number,
                'amount' => $report->reward_amount,
                'currency_code' => $this->currency,
                'status' => 'sent',
                'transaction_id' => 'WLS-SIM-'.strtoupper(uniqid()),
                'error_message' => null,
            ]);

            $report->update(['reward_sent' => true]);

            return true;
        }

        $response = $this->at->airtime()->send([
            'recipients' => [
                [
                    'phoneNumber' => $report->phone_number,
                    'currencyCode' => $this->currency,
                    'amount' => (float) $report->reward_amount,
                ],
            ],
        ]);

        $data = $response['data'] ?? null;
        $firstResponse = $data->responses[0] ?? null;

        $reward = Reward::create([
            'report_id' => $report->id,
            'phone_number' => $report->phone_number,
            'amount' => $report->reward_amount,
            'currency_code' => $this->currency,
            'status' => ($firstResponse && $firstResponse->status === 'Sent') ? 'sent' : 'failed',
            'transaction_id' => $firstResponse?->requestId ?? null,
            'error_message' => $firstResponse?->errorMessage ?? $data->errorMessage ?? null,
        ]);

        if ($reward->status === 'sent') {
            $report->update(['reward_sent' => true]);
        }

        return $reward->status === 'sent';
    }
}
