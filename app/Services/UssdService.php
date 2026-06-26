<?php

namespace App\Services;

use AfricasTalking\SDK\AfricasTalking;
use App\Enums\IncidentType;
use App\Models\Ranger;
use App\Models\Report;
use App\Models\Reward;
use App\Models\UssdSession;
use Illuminate\Support\Facades\Log;

class UssdService
{
    // Step number constants
    const STEP_WELCOME = 0;

    const STEP_MENU = 1;

    const STEP_INCIDENT_TYPE = 2;

    const STEP_LOCATION = 3;

    const STEP_ADDITIONAL_INFO = 4;

    const STEP_CONFIRMATION = 5;

    const STEP_AIRTIME_PIN = 8;

    protected string $currentLang = 'en';

    /**
     * Update session data while preserving the language setting.
     */
    protected function setSessionData(UssdSession $session, array $data): void
    {
        $data['lang'] = $this->currentLang;
        $session->update(['data' => $data]);
    }

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
            return $this->end($this->message('session_invalid'));
        }

        $session = UssdSession::firstOrCreate(
            ['session_id' => $sessionId],
            ['phone_number' => $phoneNumber, 'current_step' => self::STEP_WELCOME],
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

        // Check if language has been selected
        $data = (array) $session->data;
        if (! isset($data['lang'])) {
            return $this->handleLanguageSelection($session, $input);
        }

        $this->currentLang = $data['lang'];

        $response = match ($session->current_step) {
            self::STEP_WELCOME => $this->showWelcome($session),
            self::STEP_MENU => $this->handleWelcomeSelection($session, $input),
            self::STEP_INCIDENT_TYPE => $this->handleIncidentTypeSelection($session, $input),
            self::STEP_LOCATION => $this->handleLocationInput($session, $input),
            self::STEP_ADDITIONAL_INFO => $this->handleAdditionalInfo($session, $input),
            self::STEP_CONFIRMATION => $this->handleConfirmation($session, $input),
            self::STEP_AIRTIME_PIN => $this->handleAirtimePin($session, $input),
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
     * Language selection — shown before welcome if no lang is set.
     */
    protected function handleLanguageSelection(UssdSession $session, string $input): string
    {
        // First call — show language menu
        if ($input === '') {
            return $this->showLanguageMenu($session);
        }

        // User made a selection
        $lang = match ($input) {
            '1' => 'en',
            '2' => $this->isHausaPhone($session->phone_number) ? 'ha' : 'sw',
            '3' => $this->isHausaPhone($session->phone_number) ? 'sw' : 'ha',
            default => null,
        };

        if ($lang === null) {
            return $this->showLanguageMenu($session);
        }

        $data = (array) $session->data;
        $data['lang'] = $lang;
        $this->currentLang = $lang;
        $this->setSessionData($session, $data);
        $session->update(['current_step' => self::STEP_WELCOME]);

        return $this->showWelcome($session);
    }

    /**
     * Show the language selection menu.
     */
    protected function showLanguageMenu(UssdSession $session): string
    {
        $defaultLang = $this->isHausaPhone($session->phone_number) ? 'ha' : 'sw';
        $this->currentLang = 'en';

        return $this->con(
            $this->message('lang_title')."\n".
            $this->message('lang_en')."\n".
            $this->message('lang_ha')."\n".
            $this->message('lang_sw')
        );
    }

    /**
     * Detect if the phone number is likely Nigerian (Hausa region).
     */
    protected function isHausaPhone(string $phone): bool
    {
        return str_starts_with($phone, '+234');
    }

    /**
     * Step 0: Display the welcome menu.
     */
    protected function showWelcome(UssdSession $session): string
    {
        $session->update(['current_step' => self::STEP_MENU]);

        return $this->con(
            $this->message('welcome_title')."\n".
            $this->message('menu_report')."\n".
            $this->message('menu_reports')."\n".
            $this->message('menu_balance')."\n".
            $this->message('menu_airtime')
        );
    }

    /**
     * Step 1: User selected an option from the welcome menu.
     */
    protected function handleWelcomeSelection(UssdSession $session, string $input): string
    {
        if ($input === '1') {
            $session->update(['current_step' => self::STEP_INCIDENT_TYPE]);
            $this->setSessionData($session, ['menu_option' => $input]);

            return $this->con(
                $this->message('incident_title')."\n".
                $this->message('incident_poaching')."\n".
                $this->message('incident_snare')."\n".
                $this->message('incident_injured')."\n".
                $this->message('incident_back')
            );
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

                return $this->end($this->message('not_ranger'));
            }

            // Ranger airtime request — ask for PIN
            $session->update(['current_step' => self::STEP_AIRTIME_PIN]);
            $this->setSessionData($session, ['mode' => 'airtime_request']);

            $amount = config('services.africastalking.airtime_amount', 100);

            return $this->con($this->message('airtime_prompt', ['amount' => $amount]));
        }

        // Invalid option — show error and re-display welcome
        return $this->con(
            $this->message('invalid_option')."\n".
            $this->message('welcome_title')."\n".
            $this->message('menu_report')."\n".
            $this->message('menu_reports')."\n".
            $this->message('menu_balance')."\n".
            $this->message('menu_airtime')
        );
    }

    /**
     * Step 2: User selected incident type — ask for location.
     */
    protected function handleIncidentTypeSelection(UssdSession $session, string $input): string
    {
        if ($input === '0') {
            return $this->resetSession($session);
        }

        $incidentType = IncidentType::fromInput($input);

        if ($incidentType === null) {
            return $this->con(
                $this->message('invalid_option')."\n".
                $this->message('incident_title')."\n".
                $this->message('incident_poaching')."\n".
                $this->message('incident_snare')."\n".
                $this->message('incident_injured')."\n".
                $this->message('incident_back')
            );
        }

        $existingData = (array) $session->data;
        $existingData['incident_type'] = $incidentType->value;

        $session->update(['current_step' => self::STEP_LOCATION]);
        $this->setSessionData($session, $existingData);

        return $this->con($this->message('location_prompt'));
    }

    /**
     * Step 3: Location received — create report, alert rangers, end session.
     */
    protected function handleLocationInput(UssdSession $session, string $input): string
    {
        if ($input === '0') {
            $session->update(['current_step' => self::STEP_INCIDENT_TYPE]);

            return $this->con(
                $this->message('incident_title')."\n".
                $this->message('incident_poaching')."\n".
                $this->message('incident_snare')."\n".
                $this->message('incident_injured')."\n".
                $this->message('incident_back')
            );
        }

        if (blank(trim($input))) {
            return $this->con($this->message('location_blank'));
        }

        $data = (array) $session->data;
        $data['location'] = trim($input);
        $session->update(['data' => $data]);

        $incidentType = IncidentType::tryFrom($data['incident_type'] ?? '');

        // Ask for additional info only for poaching or injured animal
        if ($incidentType === IncidentType::Poaching || $incidentType === IncidentType::InjuredAnimal) {
            // Customise prompt based on type
            $prompt = $incidentType === IncidentType::Poaching
                ? $this->message('additional_poaching')
                : $this->message('additional_injured');

            $session->update(['current_step' => self::STEP_ADDITIONAL_INFO]);

            return $this->con($prompt);
        }

        // For snare/trap, skip to report creation
        return $this->createReport($session);
    }
    /**Handle Additional info */

    protected function handleAdditionalInfo(UssdSession $session, string $input): string
    {
        if ($input === '0') {
            $session->update(['current_step' => self::STEP_LOCATION]);

            return $this->con($this->message('location_prompt'));
        }

        if (blank(trim($input))) {
            return $this->con($this->message('additional_blank'));
        }

        $data = (array) $session->data;
        $data['additional_info'] = trim($input); // new column
        $this->setSessionData($session, $data);
        $session->update(['current_step' => self::STEP_CONFIRMATION]);

        $incidentType = IncidentType::tryFrom($data['incident_type'] ?? '');
        $typeLabel = $incidentType?->label() ?? 'Unknown';
        $location = $data['location'] ?? '';
        $additionalInfo = $data['additional_info'] ?? '';

        $summary = $this->message('confirm_title')."\n".
            $this->message('confirm_type', ['type' => $typeLabel])."\n".
            $this->message('confirm_location', ['location' => $location]);
        if ($additionalInfo) {
            $summary .= "\n".$this->message('confirm_additional', ['info' => $additionalInfo]);
        }
        $summary .= "\n".$this->message('confirm_confirm').
            "\n".$this->message('confirm_edit').
            "\n".$this->message('confirm_cancel');

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
            $session->update(['current_step' => self::STEP_LOCATION]);

            return $this->con($this->message('location_prompt'));
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

        $amount = (int) config('services.africastalking.airtime_amount', 100);

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

        return $this->end($this->message('report_success', [
            'ref' => $report->reference_id,
            'amount' => $amount,
        ]));
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
            return $this->con($this->message('pin_invalid_format'));
        }

        $phone = $this->normalizePhone($session->phone_number);

        $ranger = Ranger::where('phone_number', $phone)
            ->where('is_active', true)
            ->first();

        if (! $ranger) {
            $session->delete();

            return $this->end($this->message('not_ranger'));
        }

        // Check if account is locked
        if ($ranger->locked_until && $ranger->locked_until->isFuture()) {
            $minutes = (int) now()->diffInMinutes($ranger->locked_until);
            $session->delete();

            return $this->end($this->message('pin_account_locked', ['minutes' => $minutes]));
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

                return $this->end($this->message('pin_locked'));
            }

            $remaining = 3 - $ranger->pin_attempts;
            $session->delete();

            return $this->end($this->message('pin_wrong', ['remaining' => $remaining]));
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

            return $this->end($this->message('airtime_limit'));
        }

        // Send airtime
        try {
            $amount = (float) config('services.africastalking.airtime_amount', 100);
            $reward = app(AirtimeService::class)->sendToPhone($phone, $amount, 'Ranger airtime request');
            $status = $reward->status === 'sent'
                ? $this->message('airtime_sent', [
                    'amount' => number_format($amount, 0),
                    'phone' => $phone,
                ])
                : $this->message('airtime_failed');
        } catch (\Throwable $e) {
            logger()->error('Ranger airtime request failed: '.$e->getMessage());
            $status = $this->message('airtime_failed_retry');
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
            return $this->end($this->message('report_history_empty'));
        }

        $lines = collect([$this->message('report_history_title')]);

        foreach ($reports as $report) {
            $status = strtoupper(substr($report->status, 0, 1));
            $lines->push($this->message('report_history_line', [
                'ref' => $report->reference_id,
                'status' => $status,
                'location' => $report->location,
            ]));
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

        return $this->end(
            $this->message('balance_title')."\n".
            $this->message('balance_verified', ['count' => $verifiedCount])."\n".
            $this->message('balance_total', ['total' => $total])."\n".
            $this->message('balance_footer')
        );
    }

    /**
     * Reset the session and show welcome again.
     */
    protected function resetSession(UssdSession $session): string
    {
        $session->update([
            'current_step' => self::STEP_WELCOME,
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
     * Get a translated USSD message from the language files.
     */
    protected function message(string $key, array $replace = []): string
    {
        return __("ussd.{$key}", $replace, $this->currentLang);
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
