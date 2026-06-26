<?php

use AfricasTalking\SDK\AfricasTalking;
use App\Enums\IncidentType;
use App\Models\Report;
use App\Models\UssdSession;
use App\Services\SmsService;
use App\Services\UssdService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    // Mock the Africa's Talking SDK — we never call real APIs in tests
    $this->at = Mockery::mock(AfricasTalking::class);
    $this->smsService = Mockery::mock(SmsService::class);
    $this->smsService->shouldReceive('alertRangers')->andReturn(3);

    $this->ussdService = new UssdService($this->at, $this->smsService);
});

test('welcome screen shows language selector on first request', function () {
    $response = $this->ussdService->handleRequest([
        'sessionId' => 'test-session-001',
        'phoneNumber' => '+2347000000001',
        'text' => '',
    ]);

    expect($response)->toStartWith('CON')
        ->and($response)->toContain('Select language:')
        ->and($response)->toContain('1. English')
        ->and($response)->toContain('2. Hausa')
        ->and($response)->toContain('3. Kiswahili');

    // Session should be created at step 0 (language selection)
    $session = UssdSession::where('session_id', 'test-session-001')->first();
    expect($session)->not->toBeNull()
        ->and($session->current_step)->toBe(0);
});

test('selecting report incident shows incident type menu', function () {
    // Seed session at step 1 (welcome already shown)
    UssdSession::create([
        'session_id' => 'test-session-002',
        'phone_number' => '+2347000000002',
        'current_step' => 1,
        'data' => ['lang' => 'en'],
    ]);

    $response = $this->ussdService->handleRequest([
        'sessionId' => 'test-session-002',
        'phoneNumber' => '+2347000000002',
        'text' => '1',
    ]);

    expect($response)->toStartWith('CON')
        ->and($response)->toContain('Select incident type')
        ->and($response)->toContain('1. Poaching')
        ->and($response)->toContain('2. Snare/Trap')
        ->and($response)->toContain('3. Injured Animal');
});

test('selecting an incident type prompts for location', function () {
    UssdSession::create([
        'session_id' => 'test-session-003',
        'phone_number' => '+2347000000003',
        'current_step' => 2,
        'data' => ['menu_option' => '1', 'lang' => 'en'],
    ]);

    $response = $this->ussdService->handleRequest([
        'sessionId' => 'test-session-003',
        'phoneNumber' => '+2347000000003',
        'text' => '2',
    ]);

    expect($response)->toStartWith('CON')
        ->and($response)->toContain('Enter location');

    $session = UssdSession::where('session_id', 'test-session-003')->first();
    expect($session->current_step)->toBe(3)
        ->and($session->data['incident_type'])->toBe('snare');
});

test('submitting location creates report and ends session', function () {
    UssdSession::create([
        'session_id' => 'test-session-004',
        'phone_number' => '+2347000000004',
        'current_step' => 3,
        'data' => ['incident_type' => 'snare', 'lang' => 'en'],
    ]);

    $response = $this->ussdService->handleRequest([
        'sessionId' => 'test-session-004',
        'phoneNumber' => '+2347000000004',
        'text' => 'Near River Kaduna Bridge',
    ]);

    expect($response)->toStartWith('END')
        ->and($response)->toContain('Thank you!')
        ->and($response)->toContain('Rangers have been alerted');

    // Report should exist
    $report = Report::where('phone_number', '+2347000000004')->first();
    expect($report)->not->toBeNull()
        ->and($report->location)->toBe('Near River Kaduna Bridge')
        ->and($report->incident_type)->toBe(IncidentType::Snare)
        ->and($report->status)->toBe('pending');

    // Session should be cleaned up
    expect(UssdSession::where('session_id', 'test-session-004')->exists())->toBeFalse();
});

test('checking reports shows history for caller', function () {
    UssdSession::create([
        'session_id' => 'test-session-005',
        'phone_number' => '+2347000000005',
        'current_step' => 1,
        'data' => ['lang' => 'en'],
    ]);

    // Create some reports for this user
    Report::factory()->count(2)->create([
        'phone_number' => '+2347000000005',
        'status' => 'verified',
    ]);

    $response = $this->ussdService->handleRequest([
        'sessionId' => 'test-session-005',
        'phoneNumber' => '+2347000000005',
        'text' => '2',
    ]);

    expect($response)->toStartWith('END')
        ->and($response)->toContain('Your Recent Reports');
});

test('checking balance shows reward total', function () {
    UssdSession::create([
        'session_id' => 'test-session-006',
        'phone_number' => '+2347000000006',
        'current_step' => 1,
        'data' => ['lang' => 'en'],
    ]);

    Report::factory()->count(3)->create([
        'phone_number' => '+2347000000006',
        'status' => 'verified',
        'reward_sent' => true,
    ]);

    $response = $this->ussdService->handleRequest([
        'sessionId' => 'test-session-006',
        'phoneNumber' => '+2347000000006',
        'text' => '3',
    ]);

    expect($response)->toStartWith('END')
        ->and($response)->toContain('Your Rewards')
        ->and($response)->toContain('300');
});

test('invalid session id returns error', function () {
    $response = $this->ussdService->handleRequest([
        'sessionId' => '',
        'phoneNumber' => '',
        'text' => '',
    ]);

    expect($response)->toStartWith('END')
        ->and($response)->toContain('Invalid session');
});
