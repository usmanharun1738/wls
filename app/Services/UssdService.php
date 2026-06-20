<?php

namespace App\Services;

use AfricasTalking\SDK\AfricasTalking;
use App\Models\Report;
use App\Models\UssdSession;

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
        // Check for session reset (option 0 from welcome)
        if ($input === '0' && $session->current_step > 0) {
            return $this->resetSession($session);
        }

        return match ($session->current_step) {
            0 => $this->showWelcome($session),
            1 => $this->handleWelcomeSelection($session, $input),
            2 => $this->handleIncidentTypeSelection($session, $input),
            3 => $this->handleLocationInput($session, $input),
            default => $this->resetSession($session),
        };
    }

    /**
     * Step 0: Display the welcome menu.
     */
    protected function showWelcome(UssdSession $session): string
    {
        $session->update(['current_step' => 1]);

        return $this->con("Welcome to Wildlife Alert\n1. Report Incident\n2. Check My Reports\n3. Check Balance");
    }

    /**
     * Step 1: User selected an option from the welcome menu.
     */
    protected function handleWelcomeSelection(UssdSession $session, string $input): string
    {
        if ($input === '2') {
            return $this->showReportingHistory($session);
        }

        if ($input === '3') {
            return $this->showBalance($session);
        }

        // Default or "1" — show incident type sub-menu
        $session->update([
            'current_step' => 2,
            'data' => ['menu_option' => $input],
        ]);

        return $this->con("Select incident type:\n1. Poaching\n2. Snare/Trap\n3. Injured Animal\n0. Back");
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

        return $this->con('Enter location (e.g., "Near River Bridge" or GPS coords):');
    }

    /**
     * Step 3: Location received — create report, alert rangers, end session.
     */
    protected function handleLocationInput(UssdSession $session, string $input): string
    {
        if (blank(trim($input))) {
            return $this->con('Please enter a location description:');
        }

        return $this->createReport($session, trim($input));
    }

    /**
     * Create the report record and trigger SMS alerts.
     */
    protected function createReport(UssdSession $session, string $location): string
    {
        $data = (array) $session->data;
        $incidentType = $data['incident_type'] ?? 'poaching';

        $report = Report::create([
            'reference_id' => $this->generateReferenceId(),
            'phone_number' => $session->phone_number,
            'incident_type' => $incidentType,
            'location' => $location,
            'status' => 'pending',
        ]);

        // Alert rangers via SMS
        $this->smsService->alertRangers($report);

        // Clean up session
        $session->delete();

        return $this->end("Thank you! Report #{$report->reference_id} submitted.\nRangers have been alerted.\nYou will receive NGN 100 if verified.");
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
        $session->update(['current_step' => 0, 'data' => null]);

        return $this->showWelcome($session);
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
     */
    protected function con(string $message): string
    {
        return 'CON '.$message;
    }

    /**
     * Format an END (terminal) USSD response.
     */
    protected function end(string $message): string
    {
        return 'END '.$message;
    }
}
