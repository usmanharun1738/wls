<?php

namespace App\Services;

use AfricasTalking\SDK\AfricasTalking;
use App\Models\Ranger;
use App\Models\Report;
use Illuminate\Database\Eloquent\Collection;

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
     * Send alerts to rangers based on location proximity for a given report.
     *
     * Strategy:
     *   1. If report has GPS coords, find rangers within ~50km radius using Haversine
     *   2. Otherwise, match rangers whose base_location partially matches the report location
     *   3. If no matches found, fall back to alerting all active rangers
     */
    public function alertRangers(Report $report): int
    {
        $rangers = $this->findRelevantRangers($report);

        if ($rangers->isEmpty()) {
            return 0;
        }

        $message = $this->formatAlertMessage($report, $rangers->count());
        $sent = 0;

        foreach ($rangers as $ranger) {
            $response = $this->send($ranger->phone_number, $message);

            $smsStatus = ($response['status'] ?? '') === 'success' ? 'sent' : 'failed';
            $messageId = $response['data']->SMSMessageData->Recipients[0]->messageId ?? null;

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

        logger()->info('SMS alerts dispatched', [
            'report_id' => $report->id,
            'rangers_alerted' => $sent,
            'total_active' => Ranger::where('is_active', true)->count(),
        ]);

        return $sent;
    }

    /**
     * Find rangers relevant to this report's location.
     */
    protected function findRelevantRangers(Report $report): Collection
    {
        $query = Ranger::query()->where('is_active', true);

        // Tier 1: GPS-based proximity (if both report and rangers have coordinates)
        if ($report->latitude && $report->longitude) {
            $nearby = $this->findRangersByProximity($report->latitude, $report->longitude);

            if ($nearby->isNotEmpty()) {
                return $nearby;
            }
        }

        // Tier 2: Text-based location matching
        $words = $this->extractLocationKeywords($report->location);

        if (! empty($words)) {
            $matched = $query->where(function ($q) use ($words) {
                foreach ($words as $word) {
                    $q->orWhere('base_location', 'like', "%{$word}%");
                }
            })->get();

            if ($matched->isNotEmpty()) {
                return $matched;
            }
        }

        // Tier 3: Fallback — all active rangers
        return Ranger::where('is_active', true)->get();
    }

    /**
     * Find rangers within ~50km of given coordinates using Haversine formula.
     */
    protected function findRangersByProximity(float $lat, float $lng): Collection
    {
        // 50km radius in degrees (~0.45 at the equator)
        $latRange = 0.45;
        $lngRange = 0.45;

        return Ranger::query()
            ->where('is_active', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereBetween('latitude', [$lat - $latRange, $lat + $latRange])
            ->whereBetween('longitude', [$lng - $lngRange, $lng + $lngRange])
            ->get();
    }

    /**
     * Extract meaningful keywords from a location string for matching.
     */
    protected function extractLocationKeywords(string $location): array
    {
        // Remove common filler words and return meaningful tokens
        $fillerWords = ['near', 'at', 'the', 'in', 'by', 'area', 'around', 'close', 'to'];

        return collect(explode(' ', $location))
            ->map(fn ($w) => trim($w, ".,;:!?\t\n\r"))
            ->filter(fn ($w) => strlen($w) > 2 && ! in_array(strtolower($w), $fillerWords))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Format the SMS alert message.
     */
    protected function formatAlertMessage(Report $report, int $rangerCount): string
    {
        $type = strtoupper(str_replace('_', ' ', $report->incident_type->value ?? ''));
        $rangerWord = $rangerCount === 1 ? 'ranger' : 'rangers';

        return "[WLS ALERT] {$type} reported at {$report->location}. Ref: #{$report->reference_id}. {$rangerCount} {$rangerWord} alerted. Please respond.";
    }
}
