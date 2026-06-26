<?php

namespace App\Services;

use AfricasTalking\SDK\AfricasTalking;
use App\Models\Ranger;
use App\Models\Report;
use App\Models\Reward;
use App\Models\UssdSession;
use Illuminate\Support\Facades\Log;

class UssdService
{
    /**
     * Mapping of user input digits to incident types.
     */
    protected const INCIDENT_MAP = [
        '1' => 'poaching',
        '2' => 'snare',
        '3' => 'injured_animal',
    ];

    public function __construct(
        protected AfricasTalking $at,
        protected SmsService $smsService,
    ) {}

    /**
     * Handle an incoming USSD request and return the response string.
     */
    public function handleRequest(array $input): string
    {
        $sessionId = $input['sessionId'] ?? '';
        $phoneNumber = $input['phoneNumber'] ?? '';
        $text = $input['text'] ?? '';

        if (blank($sessionId) || blank($phoneNumber)) {
            return $this->end('Invalid session. Please try again.');
        }

        $session = UssdSession::firstOrCreate(
            ['session_id' => $sessionId],
            ['phone_number' => $phoneNumber, 'current_step' => 0],
        );

        return $this->processStep($session, $text);
    }

    /**
     * Route the USSD step based on session state.
     */
    protected function processStep(UssdSession $session, string $input): string
    {

        $input = $this->extractLastInput($input);

        // Reset stale sessions (>10 minutes old)
        if ($session->created_at->diffInMinutes(now()) > 10) {
            return $this->resetSession($session);
        }

        $response = match ($session->current_step) {
            0 => $this->showWelcome($session),
            1 => $this->handleWelcomeSelection($session, $input),
            2 => $this->handleIncidentTypeSelection($session, $input),
            3 => $this->handleLocationInput($session, $input),
            4 => $this->handleAdditionalInfo($session, $input),
            5 => $this->handleConfirmation($session, $input),
            8 => $this->handleAirtimePin($session, $input),
            default => $this->resetSession($session),
        };

        Log::channel('ussd')->info('USSD step processed', [
            'session_id' => $session->session_id,
            'phone' => $session->phone_number,
            'step' => $session->current_step,
            'input' => $input,
            'response_prefix' => str_starts_with($response, 'CON') ? 'CON' : 'END',
        ]);

        return $response;
    }

    /**
     * Step 0: Display the welcome menu.
     */
    protected function showWelcome(UssdSession $session): string
    {
        $session->update(['current_step' => 1]);

        return $this->con("Welcome to Wild life Support\n1. Report Incident\n2. Check My Reports\n3. Check Balance\n4. Request Airtime");
    }

    /**
     * Step 1: User selected an option from the welcome menu.
     */
    protected function handleWelcomeSelection(UssdSession $session, string $input): string
    {
        if ($input === '1') {
            $session->update([
                'current_step' => 2,
                'data' => ['menu_option' => $input],
            ]);

            return $this->con("Select incident type:\n1. Poaching\n2. Snare/Trap\n3. Injured Animal\n0. Back");
        }

        if ($input === '2') {
            return $this->showReportingHistory($session);
        }

        if ($input === '3') {
            return $this->showBalance($session);
        }

        if ($input === '4') {
            // Check if caller is an active ranger first
            $phone = $this->normalizePhone($session->phone_number);
            $ranger = Ranger::where('phone_number', $phone)
                ->where('is_active', true)
                ->exists();

            if (! $ranger) {
                $session->delete();

                return $this->end('Service not available to you.');
            }

            // Ranger airtime request — ask for PIN
            $session->update([
                'current_step' => 8,
                'data' => ['mode' => 'airtime_request'],
            ]);

            $amount = config('services.africastalking.airtime_amount', 100);

            return $this->con("Enter your 4-digit PIN to request NGN {$amount} airtime:");
        }

        // Invalid option — show error and re-display welcome
        return $this->con("Invalid option.\nWelcome to Wild life Support\n1. Report Incident\n2. Check My Reports\n3. Check Balance\n4. Request Airtime");
    }

    /**
     * Step 2: User selected incident type — ask for location.
     */
    protected function handleIncidentTypeSelection(UssdSession $session, string $input): string
    {
        if ($input === '0') {
            return $this->resetSession($session);
        }

        $incidentType = self::INCIDENT_MAP[$input] ?? null;

        if ($incidentType === null) {
            return $this->con("Invalid option.\nSelect incident type:\n1. Poaching\n2. Snare/Trap\n3. Injured Animal\n0. Back");
        }

        $existingData = (array) $session->data;
        $existingData['incident_type'] = $incidentType;

        $session->update([
            'current_step' => 3,
            'data' => $existingData,
        ]);

        return $this->con('Enter location (e.g., "Near River Kaduna" or GPS coords):');
    }

    /**
     * Step 3: Location received — create report, alert rangers, end session.
     */
    protected function handleLocationInput(UssdSession $session, string $input): string
    {
        if ($input === '0') {
            $session->update(['current_step' => 2]);

            return $this->con("Select incident type:\n1. Poaching\n2. Snare/Trap\n3. Injured Animal\n0. Back");
        }

        if (blank(trim($input))) {
            return $this->con('Please enter a location description:');
        }

        $data = (array) $session->data;
        $data['location'] = trim($input);
        $session->update(['data' => $data]);

        $incidentType = $data['incident_type'] ?? 'poaching';

        // Ask for additional info only for poaching or injured animal
        if ($incidentType === 'poaching' || $incidentType === 'injured_animal') {
            // Customise prompt based on type
            if ($incidentType === 'poaching') {
                $prompt = 'Additional info (e.g., animal name, number of poachers, vehicle plate no): ';
            } else { // injured_animal
                $prompt = 'Additional info (e.g., animal species, injury type): ';
            }

            $session->update(['current_step' => 4]);

            return $this->con($prompt);
        }

        // For snare/trap, skip to report creation
        return $this->createReport($session);
    }
    /**Handle Additional info */

    protected function handleAdditionalInfo(UssdSession $session, string $input): string
    {
        if ($input === '0') {
            $session->update(['current_step' => 3]);

            return $this->con('Enter location (e.g., "Near River Kaduna" or GPS coords):');
        }

        if (blank(trim($input))) {
            return $this->con('Please provide some additional details to help rangers:');
        }

        $data = (array) $session->data;
        $data['additional_info'] = trim($input); // new column
        $session->update([
            'data' => $data,
            'current_step' => 5,
        ]);

        $incidentType = $data['incident_type'] ?? 'poaching';
        $typeLabel = match ($incidentType) {
            'poaching' => 'Poaching',
            'snare' => 'Snare/Trap',
            'injured_animal' => 'Injured Animal',
            default => ucfirst($incidentType),
        };
        $location = $data['location'] ?? '';
        $additionalInfo = $data['additional_info'] ?? '';

        $summary = "Confirm report:\nType: {$typeLabel}\nLocation: {$location}";
        if ($additionalInfo) {
            $summary .= "\nAdditional: {$additionalInfo}";
        }
        $summary .= "\n1. Confirm\n2. Edit\n0. Cancel";

        return $this->con($summary);
    }

    /**
     * Step 5: Show report summary and ask for confirmation.
     * 1 = Confirm → create report
     * 2 = Edit → go back to location
     * 0 = Cancel → reset to welcome
     */
    protected function handleConfirmation(UssdSession $session, string $input): string
    {
        if ($input === '1') {
            return $this->createReport($session);
        }

        if ($input === '2') {
            // Go back to edit location
            $session->update(['current_step' => 3]);

            return $this->con('Enter location (e.g., "Near River Kaduna" or GPS coords):');
        }

        // 0 or anything else — cancel
        return $this->resetSession($session);
    }

    /**
     * Create the report record and trigger SMS alerts.
     */
    protected function createReport(UssdSession $session): string
    {
        $data = (array) $session->data;
        $incidentType = $data['incident_type'] ?? 'poaching';
        $location = $data['location'] ?? '';
        $additionalInfo = $data['additional_info'] ?? null;

        $amount = config('services.africastalking.airtime_amount', 100);

        $report = Report::create([
            'reference_id' => $this->generateReferenceId(),
            'phone_number' => $session->phone_number,
            'incident_type' => $incidentType,
            'location' => $location,
            'additional_info' => $additionalInfo,
            'status' => 'pending',
            'reward_amount' => $amount,
        ]);

        // Alert rangers via SMS (fire-and-forget — don't break USSD if SMS fails)
        try {
            $this->smsService->alertRangers($report);
        } catch (\Throwable $e) {
            logger()->error('SMS alert failed for report #'.$report->reference_id, [
                'error' => $e->getMessage(),
            ]);
        }

        // Clean up session
        $session->delete();

        return $this->end("Thank you! Report #{$report->reference_id} submitted.\nRangers have been alerted.\nYou will receive NGN {$amount} if verified.");
    }

    /**
     * Handle ranger airtime request — validate PIN, check 24h limit, send airtime.
     */
    protected function handleAirtimePin(UssdSession $session, string $input): string
    {
        if ($input === '0') {
            return $this->resetSession($session);
        }

        if (! preg_match('/^\d{4}$/', $input)) {
            return $this->con('Invalid PIN. Please enter a 4-digit PIN:');
        }

        $phone = $this->normalizePhone($session->phone_number);

        $ranger = Ranger::where('phone_number', $phone)
            ->where('is_active', true)
            ->first();

        if (! $ranger) {
            $session->delete();

            return $this->end('Service not available to you.');
        }

        // Check if account is locked
        if ($ranger->locked_until && $ranger->locked_until->isFuture()) {
            $minutes = now()->diffInMinutes($ranger->locked_until);
            $session->delete();

            return $this->end("Account locked. Try again in {$minutes} minutes.");
        }

        // Verify PIN
        if ($ranger->pin !== $input) {
            $ranger->increment('pin_attempts');

            if ($ranger->pin_attempts >= 3) {
                $ranger->update([
                    'locked_until' => now()->addHour(),
                    'pin_attempts' => 0,
                ]);
                $session->delete();

                return $this->end('Too many wrong attempts. Account locked for 1 hour.');
            }

            $remaining = 3 - $ranger->pin_attempts;
            $session->delete();

            return $this->end("Invalid PIN. {$remaining} attempt(s) remaining.");
        }

        // Correct PIN — reset lockout counters
        $ranger->update([
            'pin_attempts' => 0,
            'locked_until' => null,
        ]);

        // Check 24-hour limit
        $recentAirtime = Reward::where('phone_number', $phone)
            ->whereNull('report_id')
            ->where('created_at', '>=', now()->subHours(24))
            ->exists();

        if ($recentAirtime) {
            $session->delete();

            return $this->end('You already requested airtime in the last 24 hours. Please try again later.');
        }

        // Send airtime
        try {
            $amount = (float) config('services.africastalking.airtime_amount', 100);
            $reward = app(AirtimeService::class)->sendToPhone($phone, $amount, 'Ranger airtime request');
            $status = $reward->status === 'sent'
                ? 'NGN '.number_format($amount, 0)." airtime has been sent to {$phone}. Thank you for your service!"
                : 'Airtime request failed. Please contact admin.';
        } catch (\Throwable $e) {
            logger()->error('Ranger airtime request failed: '.$e->getMessage());
            $status = 'Airtime request failed. Please try again later.';
        }

        $session->delete();

        return $this->end($status);
    }

    /**
     * Normalize Nigerian phone numbers to international format.
     */
    protected function normalizePhone(string $phone): string
    {
        $phone = trim($phone);

        if (str_starts_with($phone, '+')) {
            return $phone;
        }

        $digits = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($digits, '234') && strlen($digits) === 13) {
            return '+'.$digits;
        }

        if (str_starts_with($digits, '0') && strlen($digits) === 11) {
            return '+234'.substr($digits, 1);
        }

        return '+'.$digits;
    }

    /**
     * Show reporting history for the caller.
     */
    protected function showReportingHistory(UssdSession $session): string
    {
        $reports = Report::where('phone_number', $session->phone_number)
            ->latest()
            ->take(5)
            ->get();

        $session->delete();

        if ($reports->isEmpty()) {
            return $this->end('You have no reports yet. Dial *384# to report an incident.');
        }

        $lines = collect(['Your Recent Reports:']);

        foreach ($reports as $report) {
            $status = strtoupper(substr($report->status, 0, 1));
            $lines->push("#{$report->reference_id} - {$status} - {$report->location}");
        }

        return $this->end($lines->implode("\n"));
    }

    /**
     * Show balance for the caller.
     */
    protected function showBalance(UssdSession $session): string
    {
        $verifiedCount = Report::where('phone_number', $session->phone_number)
            ->where('status', 'verified')
            ->where('reward_sent', true)
            ->count();

        $session->delete();

        $total = $verifiedCount * 100;

        return $this->end("Your Rewards:\nVerified reports: {$verifiedCount}\nTotal earned: NGN {$total}\nThank you for helping protect wildlife!");
    }

    /**
     * Reset the session and show welcome again.
     */
    protected function resetSession(UssdSession $session): string
    {
        $session->update([
            'current_step' => 0,
            'data' => null,
            'created_at' => now(),
        ]);

        return $this->showWelcome($session);
    }

    /**
     * Extract the last user input from AT's cumulative text format.
     * AT sends "1*1*Near Kaduna River" — we only want "Near Kaduna River".
     */
    protected function extractLastInput(string $text): string
    {
        if (blank($text)) {
            return '';
        }

        $parts = explode('*', $text);

        return (string) end($parts);
    }

    /**
     * Generate a unique reference ID for a report.
     */
    protected function generateReferenceId(): string
    {
        return 'WLS-'.date('Ymd').'-'.strtoupper(substr(uniqid(), -5));
    }

    /**
     * Format a CON (continue) USSD response.
     * AT limits responses to ~182 chars total — truncate if needed.
     */
    protected function con(string $message): string
    {
        return 'CON '.$this->truncateResponse($message);
    }

    /**
     * Format an END (terminal) USSD response.
     * AT limits responses to ~182 chars total — truncate if needed.
     */
    protected function end(string $message): string
    {
        return 'END '.$this->truncateResponse($message);
    }

    /**
     * Truncate a USSD message to fit within AT's ~182 character limit
     * (182 minus 3-4 for the CON/END prefix).
     */
    protected function truncateResponse(string $message): string
    {
        $limit = 178;

        if (mb_strlen($message) <= $limit) {
            return $message;
        }

        Log::channel('ussd')->warning('USSD response truncated', [
            'length' => mb_strlen($message),
            'limit' => $limit,
            'original' => $message,
        ]);

        return mb_substr($message, 0, $limit - 3).'...';
    }
}
