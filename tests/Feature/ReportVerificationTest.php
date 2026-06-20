<?php

use App\Models\Report;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create();
});

test('admin can view reports', function () {
    Report::factory()->count(5)->create();

    $response = $this->actingAs($this->admin)
        ->getJson('/api/admin/reports');

    $response->assertOk()
        ->assertJsonCount(5, 'data');
});

test('admin can filter reports by status', function () {
    Report::factory()->count(3)->pending()->create();
    Report::factory()->count(2)->verified()->create();

    $response = $this->actingAs($this->admin)
        ->getJson('/api/admin/reports?status=pending');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

test('admin can filter reports by incident type', function () {
    Report::factory()->count(2)->poaching()->create();
    Report::factory()->count(4)->snare()->create();

    $response = $this->actingAs($this->admin)
        ->getJson('/api/admin/reports?type=snare');

    $response->assertOk()
        ->assertJsonCount(4, 'data');
});

test('admin can verify a pending report', function () {
    $report = Report::factory()->pending()->create();

    $response = $this->actingAs($this->admin)
        ->postJson("/api/admin/reports/{$report->id}/verify");

    $response->assertOk()
        ->assertJsonPath('message', 'Report verified.');

    $report->refresh();
    expect($report->status)->toBe('verified')
        ->and($report->verified_by)->toBe($this->admin->id);
});

test('admin can reject a pending report', function () {
    $report = Report::factory()->pending()->create();

    $response = $this->actingAs($this->admin)
        ->postJson("/api/admin/reports/{$report->id}/reject");

    $response->assertOk()
        ->assertJsonPath('message', 'Report rejected.');

    $report->refresh();
    expect($report->status)->toBe('rejected')
        ->and($report->verified_by)->toBe($this->admin->id);
});

test('cannot verify an already verified report', function () {
    $report = Report::factory()->verified()->create();

    $response = $this->actingAs($this->admin)
        ->postJson("/api/admin/reports/{$report->id}/verify");

    $response->assertStatus(422);
});

test('cannot reject an already rejected report', function () {
    $report = Report::factory()->rejected()->create();

    $response = $this->actingAs($this->admin)
        ->postJson("/api/admin/reports/{$report->id}/reject");

    $response->assertStatus(422);
});

test('unauthenticated user cannot access admin reports', function () {
    $response = $this->getJson('/api/admin/reports');

    $response->assertStatus(401);
});

test('dashboard stats returns correct counts', function () {
    Report::factory()->count(5)->pending()->create();
    Report::factory()->count(3)->verified()->create();
    Report::factory()->count(2)->rejected()->create();

    $response = $this->actingAs($this->admin)
        ->getJson('/api/admin/dashboard/stats');

    $response->assertOk()
        ->assertJsonPath('total', 10)
        ->assertJsonPath('pending', 5)
        ->assertJsonPath('verified', 3)
        ->assertJsonPath('rejected', 2);
});

test('admin can search reports by location', function () {
    Report::factory()->create(['location' => 'Kamuku National Park']);
    Report::factory()->create(['location' => 'River Kaduna']);
    Report::factory()->create(['location' => 'Birnin Gwari Forest']);

    $response = $this->actingAs($this->admin)
        ->getJson('/api/admin/reports?search=Kamuku');

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});
