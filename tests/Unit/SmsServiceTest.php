<?php

use AfricasTalking\SDK\AfricasTalking;
use AfricasTalking\SDK\SMS;
use App\Models\Ranger;
use App\Models\Report;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->smsMock = Mockery::mock(SMS::class);

    $this->at = Mockery::mock(AfricasTalking::class);
    $this->at->shouldReceive('sms')->andReturn($this->smsMock);

    $this->smsService = new SmsService($this->at);
});

test('send returns success response', function () {
    $this->smsMock->shouldReceive('send')
        ->once()
        ->with(Mockery::on(function ($arg) {
            return $arg['to'] === '+2347000000001'
                && str_contains($arg['message'], '[WLS ALERT]');
        }))
        ->andReturn(['status' => 'success', 'data' => []]);

    $response = $this->smsService->send('+2347000000001', '[WLS ALERT] Test message');

    expect($response['status'])->toBe('success');
});

test('alertRangers sends to all active rangers', function () {
    Ranger::factory()->count(3)->create(['is_active' => true]);
    Ranger::factory()->create(['is_active' => false]); // inactive — should be skipped

    $report = Report::factory()->create([
        'incident_type' => 'snare',
        'location' => 'Dagida Forest Reserve',
    ]);

    // Should be called 3 times (only active rangers)
    $this->smsMock->shouldReceive('send')
        ->times(3)
        ->andReturn(['status' => 'success', 'data' => []]);

    $sent = $this->smsService->alertRangers($report);

    expect($sent)->toBe(3);
});

test('alertRangers handles no active rangers gracefully', function () {
    Ranger::query()->delete();

    $report = Report::factory()->create();

    $sent = $this->smsService->alertRangers($report);

    expect($sent)->toBe(0);
});

test('formatAlertMessage includes incident type and location', function () {
    $report = Report::factory()->create([
        'incident_type' => 'poaching',
        'location' => 'Kamuku National Park',
        'reference_id' => 'WLS-20260620-ABCDE',
    ]);

    // Use reflection to test the protected method
    $reflection = new ReflectionMethod(SmsService::class, 'formatAlertMessage');

    $message = $reflection->invoke($this->smsService, $report);

    expect($message)->toContain('[WLS ALERT]')
        ->and($message)->toContain('POACHING')
        ->and($message)->toContain('Kamuku National Park')
        ->and($message)->toContain('WLS-20260620-ABCDE');
});
