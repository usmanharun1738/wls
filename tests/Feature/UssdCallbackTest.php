<?php

use AfricasTalking\SDK\AfricasTalking;
use AfricasTalking\SDK\SMS;
use App\Models\Ranger;
use App\Models\Report;
use App\Models\UssdSession;

beforeEach(function () {
    // Mock AT SDK at the container level for all feature tests
    $smsMock = Mockery::mock(SMS::class);
    $smsMock->shouldReceive('send')->andReturn(['status' => 'success']);

    $atMock = Mockery::mock(AfricasTalking::class);
    $atMock->shouldReceive('sms')->andReturn($smsMock);

    app()->instance(AfricasTalking::class, $atMock);

    // Seed some rangers so alerts have targets
    Ranger::factory()->count(5)->create();
});

test('ussd callback shows language selector on first request', function () {
    $response = $this->postJson('/api/ussd/callback', [
        'sessionId' => 'feature-test-001',
        'phoneNumber' => '+2347000000100',
        'text' => '',
    ]);

    $response->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertSee('Select language:')
        ->assertSee('1. English');
});

test('ussd callback shows incident types on option 1', function () {
    UssdSession::create([
        'session_id' => 'feature-test-002',
        'phone_number' => '+2347000000200',
        'current_step' => 1,
        'data' => ['lang' => 'en'],
    ]);

    $response = $this->postJson('/api/ussd/callback', [
        'sessionId' => 'feature-test-002',
        'phoneNumber' => '+2347000000200',
        'text' => '1',
    ]);

    $response->assertOk()
        ->assertSee('Select incident type')
        ->assertSee('Poaching')
        ->assertSee('Snare/Trap')
        ->assertSee('Injured Animal');
});

test('ussd callback creates report on full flow', function () {
    UssdSession::create([
        'session_id' => 'feature-test-003',
        'phone_number' => '+2347000000300',
        'current_step' => 3,
        'data' => ['incident_type' => 'snare', 'lang' => 'en'],
    ]);

    $response = $this->postJson('/api/ussd/callback', [
        'sessionId' => 'feature-test-003',
        'phoneNumber' => '+2347000000300',
        'text' => 'Near River Kaduna',
    ]);

    $response->assertOk()
        ->assertSee('Thank you!')
        ->assertSee('Rangers have been alerted');

    expect(Report::where('phone_number', '+2347000000300')->exists())->toBeTrue();
});

test('ussd callback handles empty location gracefully', function () {
    UssdSession::create([
        'session_id' => 'feature-test-004',
        'phone_number' => '+2347000000400',
        'current_step' => 3,
        'data' => ['incident_type' => 'snare', 'lang' => 'en'],
    ]);

    $response = $this->postJson('/api/ussd/callback', [
        'sessionId' => 'feature-test-004',
        'phoneNumber' => '+2347000000400',
        'text' => '   ',
    ]);

    $response->assertOk()
        ->assertSee('Please enter a location');
});

test('ussd callback endpoint is accessible without auth', function () {
    $response = $this->postJson('/api/ussd/callback', [
        'sessionId' => 'feature-test-005',
        'phoneNumber' => '+2347000000500',
        'text' => '',
    ]);

    $response->assertOk();
});
