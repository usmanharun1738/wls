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

            $smsStatus = ($response['status'] ?? '') === 'success' ? 'sent' : 'failed';
            $messageId = $response['data']['SMSMessageData']['Recipients'][0]['messageId'] ?? null;

            // Record the alert in the pivot table
            $report->rangers()->attach($ranger->id, [
                'alerted_at' => now(),
                'sms_status' => $smsStatus,
                'sms_message_id' => $messageId,
            ]);

            if ($smsStatus === 'sent') {
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
