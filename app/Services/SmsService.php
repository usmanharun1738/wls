<?php

namespace App\Services;

use AfricasTalking\SDK\AfricasTalking;
use App\Models\Ranger;
use App\Models\Report;

class SmsService
{
    protected string $from;

    public function __construct(protected AfricasTalking $at)
    {
        $this->from = (string) config('services.africastalking.from', 'WLS');
    }

    /**
     * Send an SMS alert to a single recipient.
     */
    public function send(string $to, string $message): array
    {
        return $this->at->sms()->send([
            'to' => $to,
            'message' => $message,
            'from' => $this->from,
        ]);
    }

    /**
     * Send alerts to all active rangers for a given report.
     */
    public function alertRangers(Report $report): int
    {
        $rangers = Ranger::query()
            ->where('is_active', true)
            ->get();

        if ($rangers->isEmpty()) {
            return 0;
        }

        $message = $this->formatAlertMessage($report);
        $sent = 0;

        foreach ($rangers as $ranger) {
            $response = $this->send($ranger->phone_number, $message);

            if (($response['status'] ?? '') === 'success') {
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * Format the SMS alert message.
     */
    protected function formatAlertMessage(Report $report): string
    {
        $type = strtoupper(str_replace('_', ' ', $report->incident_type));

        return "[WLS ALERT] {$type} reported at {$report->location}. Ref: #{$report->reference_id}. Please respond.";
    }
}
