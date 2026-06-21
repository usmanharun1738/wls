
## 1\. Product Requirements Document (PRD)

## 1.1 Executive Summary

WLS (wild life support) is a mobile-first platform that enables rural communities to report wildlife crimes (poaching, snares, injured animals) via USSD, automatically alerts nearby rangers via SMS, and rewards reporters with mobile airtime. The system works entirely offline (USSD/SMS) and requires no smartphone or internet connection.

---

## 0\. Implementation Status (Hackathon — June 27, 2026)

> **Last updated**: 2026-06-21 | **Test suite**: 59 tests, 162 assertions, all passing | **Service code**: `*384*44275#` | **SMS short code**: `44275`

### 0.1 Achieved Objectives

| # | Objective | Status | Details |
|---|---|---|---|
| 1 | **USSD Reporting Menu** | ✅ Live | 5-step flow: Welcome → Select type (Poaching/Snare/Injured Animal) → Enter location → Confirmation with ref ID. Tested e2e via ngrok + AT sandbox. |
| 2 | **SMS Ranger Alerts** | ✅ Live | Location-based alerts: matches report location to nearest rangers via keyword matching, falls back to all active if no match. Sent from short code `44275`. |
| 3 | **Admin Dashboard** | ✅ Built | Livewire + Flux UI at `/dashboard` — 5 stat cards, filterable reports table (status/type/date/search), inline verify/reject buttons, pagination. |
| 4 | **Rangers Page** | ✅ Built | Livewire + Flux UI at `/rangers` — ranger stats, table with phone/email/location/status, pending alerts per ranger via `report_ranger` pivot. |
| 5 | **Report Lifecycle** | ✅ Operational | Pending → Verified (by admin) or Rejected. Status badges color-coded. DB columns ready for airtime. |
| 6 | **Reporting History (USSD)** | ✅ Built | Option 2 from welcome menu shows last 5 reports with status. |
| 7 | **Reward Balance (USSD)** | ✅ Built | Option 3 from welcome menu shows verified count × ₦100 = total earned. |
| 8 | **AT SDK Integration** | ✅ Configured | `africastalking/africastalking` v3.0 with singleton service provider. USSD + SMS + Airtime APIs wired. |
| 9 | **Test Suite** | ✅ 59 passing | 4 test files: `UssdServiceTest` (7 unit), `SmsServiceTest` (4 unit), `UssdCallbackTest` (5 feature), `ReportVerificationTest` (9 feature). |
| 10 | **ngrok + Callback URL** | ✅ Live | Callback registered at `https://d296-102-91-105-29.ngrok-free.app/api/ussd/callback` |
| 11 | **Database Seeders** | ✅ Seeded | 10 active rangers with real Kaduna-area locations, 1 admin user (`admin@wls.test`). |

### 0.2 Not Yet Implemented

| # | Objective | Priority | Blockers / Notes |
|---|---|---|---|
| 1 | **Airtime Rewards** | P1 — Hackathon | `rewards` table migration + model + `AirtimeService` + wire verify to trigger airtime. SDK already installed. ~2 hours. |
| 2 | **Docker Deployment** | P1 — Hackathon | `Dockerfile` + `docker-compose.yml` + nginx config. PRD has templates ready. ~1 hour. |
| 3 | **Dashboard — Reason on Reject** | P2 — Nice-to-have | Add optional reason field when admin rejects a report. |
| 4 | **Dashboard — Map View** | P2 — Nice-to-have | Plot reports on Leaflet/Mapbox if GPS coords present. Visual impact for demo. |
| 5 | **GPS Coordinates Parsing** | P2 — Nice-to-have | Parse "lat,lng" format from USSD location input. GPS-based ranger proximity already supported if coords present. |
| 6 | **Rate Limiting** | P3 — Post-hackathon | Apply on USSD callback + admin API endpoints. |
| 7 | **Ranger Dashboard** | P3 — Post-hackathon | Separate login for rangers to view alerts assigned to them. |
| 8 | **Eco-Tourism Payments** | P4 — Future | Excluded from hackathon scope per decision. |

### 0.3 Hackathon Demo Script

```
1. Admin logs in at /dashboard → sees stats cards + empty table
2. Simulate USSD: Dial *384*44275# → "Welcome to Wildlife Alert"
3. Press 1 → "1. Poaching  2. Snare  3. Injured Animal"
4. Press 1 → "Enter location"
5. Type "Near Kaduna River" → "Thank you! Report #WLS-... submitted."
6. Refresh dashboard → new report appears as "Pending"
7. Click "Verify" → status changes to "Verified", toast notification
8. (Post-airtime) Reporter dials option 3 → "Total earned: NGN 100"
```

### 0.4 Key Architecture Decisions

| Decision | Rationale |
|---|---|
| **USSD session state in DB** (`ussd_sessions`) | Survives server restarts; AT sessionIds are persistent across a user session |
| **SMS fire-and-forget** (try-catch in USSD) | USSD must respond <10s; SMS failures must not break reporting |
| **Livewire SFC for dashboard** | Follows project convention (settings pages use same pattern); Flux UI free edition |
| **SQLite for dev/testing** | Already in `.env`; zero-config; MySQL for production |
| **AT cumulative text parsing** (`extractLastInput`) | AT sends `1*2*Near River` — we extract only last segment after `*` |
| **No `rewards` table yet** | `reward_amount` + `reward_sent` columns on `reports` ready for airtime integration |

---


## 1.2 Problem Statement

- Wildlife authorities lack real‑time intelligence from remote areas.
- Community members want to report incidents but lack internet/smartphones.
- No incentive system exists to encourage reporting.
- Rangers are not immediately alerted when incidents occur.

## 1.3 Target Users

| User | Needs |
| --- | --- |
| **Community Member** | Simple way to report incidents; receive rewards |
| **Ranger** | Immediate alerts with location and incident type |
| **Wildlife Authority** | Dashboard to view and manage reports |
| **Admin** | Verify reports; manage airtime rewards |

## 1.4 Functional Requirements

### FR1: USSD Reporting Menu

| ID    | Requirement                                                                                   |
| ----- | --------------------------------------------------------------------------------------------- |
| FR1.1 | User dials a USSD shortcode (`*384#`)                                                         |
| FR1.2 | Menu displays: "1. Report Poaching 2. Report Snare 3. Report Injured Animal 4. Check Balance" |
| FR1.3 | User selects option and provides location (GPS or landmark)                                   |
| FR1.4 | System confirms submission with a reference ID                                                |

### FR2: Automatic Ranger Alert

| ID | Requirement |
| --- | --- |
| FR2.1 | Upon USSD submission, system fetches nearest rangers by location |
| FR2.2 | Sends SMS to rangers: `[ALERT] Poaching at [location]. Ref: #1234` |
| FR2.3 | SMS includes a link to view full report on the dashboard |

### FR3: Airtime Rewards

| ID | Requirement |
| --- | --- |
| FR3.1 | Each verified report earns the reporter ₦100 airtime |
| FR3.2 | Admin can manually approve/reject reports |
| FR3.3 | Airtime is sent automatically via Africa's Talking Airtime API |

### FR4: Admin Dashboard

| ID | Requirement |
| --- | --- |
| FR4.1 | View all reports with status (Pending/Verified/Rejected) |
| FR4.2 | Filter by date, location, incident type |
| FR4.3 | Verify a report → triggers airtime reward |
| FR4.4 | Reject a report with a reason |

### FR5: Reporting History

| ID | Requirement |
| --- | --- |
| FR5.1 | Users can check their reporting history and total rewards via USSD |
| FR5.2 | Rangers can view all alerts they received |

## 1.5 Non-Functional Requirements

| ID | Requirement |
| --- | --- |
| NFR1 | USSD response time < 3 seconds |
| NFR2 | SMS delivery < 10 seconds |
| NFR3 | System must handle 100 concurrent USSD sessions |
| NFR4 | 99.5% uptime during hackathon demo |
| NFR5 | Docker container starts in < 30 seconds |

## 1.6 User Stories

| ID | Story |
| --- | --- |
| US1 | As a **community member**, I want to report a poaching incident via USSD so that rangers can respond quickly. |
| US2 | As a **ranger**, I want to receive an SMS alert with the location so I can respond immediately. |
| US3 | As a **community member**, I want to receive airtime rewards for my reports so I feel motivated to participate. |
| US4 | As an **admin**, I want to verify reports so that only genuine reports receive rewards. |
| US5 | As a **wildlife authority**, I want to see a dashboard of all incidents so I can allocate resources effectively. |

## 1.7 Success Metrics

| Metric | Target |
| --- | --- |
| USSD completion rate | \> 80% |
| SMS alert delivery rate | \> 95% |
| Airtime reward delivery rate | \> 98% |
| Reports per day (demo) | 50+ |

---

## 2\. Product Specification Document (PSD) / Technical Specification

## 2.1 System Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                      User Devices                               │
│  ┌────────────┐    ┌────────────┐    ┌────────────────────┐    │
│  │ Mobile Phone│    │ Mobile Phone│    │   Web Browser      │    │
│  │   (USSD)    │    │   (SMS)     │    │   (Dashboard)      │    │
│  └──────┬─────┘    └──────┬─────┘    └─────────┬──────────┘    │
└─────────┼─────────────────┼────────────────────┼────────────────┘
          │                 │                    │
          ▼                 ▼                    ▼
┌─────────────────────────────────────────────────────────────────┐
│                   Africa's Talking API Gateway                  │
│  ┌────────────┐  ┌────────────┐  ┌────────────┐               │
│  │ USSD API   │  │  SMS API   │  │ Airtime API│               │
│  └──────┬─────┘  └──────┬─────┘  └──────┬─────┘               │
└─────────┼─────────────────┼────────────────────┼────────────────┘
          │                 │                    │
          └─────────────────┼────────────────────┘
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│                      Laravel Application                        │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐     │   │
│  │  │ USSD        │  │ SMS         │  │ Airtime     │     │   │
│  │  │ Controller  │  │ Controller  │  │ Controller  │     │   │
│  │  └──────┬──────┘  └──────┬──────┘  └──────┬──────┘     │   │
│  │         │                │                │             │   │
│  │         └────────────────┼────────────────┘             │   │
│  │                          ▼                              │   │
│  │              ┌─────────────────────┐                    │   │
│  │              │   Report Service    │                    │   │
│  │              └─────────────────────┘                    │   │
│  │                          │                              │   │
│  │                          ▼                              │   │
│  │              ┌─────────────────────┐                    │   │
│  │              │   MySQL Database    │                    │   │
│  │              │  (reports, users,   │                    │   │
│  │              │   rangers, rewards) │                    │   │
│  │              └─────────────────────┘                    │   │
│  └──────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
```

## 2.2 Technology Stack

| Layer             | Technology                      | Version | Notes |
| ----------------- | ------------------------------- | ------- | ----- |
| Backend Framework | Laravel                         | 13      | |
| Language          | PHP                             | 8.5     | |
| Database (dev)    | SQLite                          | —       | Zero-config local dev |
| Database (prod)   | MySQL                           | 8.0     | |
| Frontend          | Livewire + Flux UI + Tailwind   | v4 / v2 / v4 | Admin dashboard |
| Auth              | Laravel Fortify                 | v1      | Login, 2FA, passkeys |
| Testing           | Pest                            | v4      | 59 tests passing |
| AT SDK            | `africastalking/africastalking` | v3.0.2  | USSD + SMS + Airtime |
| Tunnel (dev)      | ngrok                           | —       | Expose localhost to AT sandbox |
| Container         | Docker (planned)                | 24+     | For hackathon deployment |

## 2.3 Database Schema

### reports table

```
CREATE TABLE reports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reference_id VARCHAR(20) UNIQUE NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    incident_type ENUM('poaching', 'snare', 'injured_animal') NOT NULL,
    location VARCHAR(255) NOT NULL,
    latitude DECIMAL(10,8) NULL,
    longitude DECIMAL(11,8) NULL,
    description TEXT NULL,
    status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    verified_by BIGINT UNSIGNED NULL,
    reward_amount DECIMAL(10,2) DEFAULT 100.00,
    reward_sent BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_phone (phone_number),
    INDEX idx_created (created_at)
);
```

### rangers table

```
CREATE TABLE rangers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone_number VARCHAR(20) UNIQUE NOT NULL,
    email VARCHAR(100) NULL,
    base_location VARCHAR(255) NULL,
    latitude DECIMAL(10,8) NULL,
    longitude DECIMAL(11,8) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### rewards table

```
CREATE TABLE rewards (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_id BIGINT UNSIGNED NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency_code VARCHAR(3) DEFAULT 'NGN',
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    transaction_id VARCHAR(100) NULL,
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
    INDEX idx_phone (phone_number),
    INDEX idx_status (status)
);
```

### ussd\_sessions table (for session state)

```
CREATE TABLE ussd_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(100) UNIQUE NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    current_step INT DEFAULT 0,
    data JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_session (session_id),
    INDEX idx_phone (phone_number)
);
```

## 2.4 API Endpoints

| Endpoint | Method | Auth | Purpose |
| --- | --- | --- | --- |
| `/api/ussd/callback` | POST | None | Receives USSD requests from AT |
| `/api/sms/callback` | POST | None | Receives SMS delivery reports from AT |
| `/api/admin/reports` | GET | Auth | List all reports (paginated, filterable) |
| `/api/admin/reports/{report}/verify` | POST | Auth | Verify a report |
| `/api/admin/reports/{report}/reject` | POST | Auth | Reject a report |
| `/api/admin/rangers` | GET | Auth | List all rangers |
| `/api/admin/dashboard/stats` | GET | Auth | Dashboard statistics |
| `/dashboard` | GET | Auth | Livewire admin dashboard (full page) |
| `/login`, `/register` | GET/POST | None | Fortify auth pages |

## 2.5 USSD Flow Diagram

```
Step 0: Welcome Screen
┌─────────────────────────────────────┐
│ Welcome to Wildlife Alert System    │
│ 1. Report Incident                  │
│ 2. Check My Reports                 │
│ 3. Check Balance                    │
└─────────────────────────────────────┘
              │
              ▼ (Option 1)
Step 1: Incident Type
┌─────────────────────────────────────┐
│ Select incident type:               │
│ 1. Poaching                         │
│ 2. Snare/Trap                       │
│ 3. Injured Animal                   │
└─────────────────────────────────────┘
              │
              ▼
Step 2: Location
┌─────────────────────────────────────┐
│ Enter location:                     │
│ (e.g., "Near River Bridge" or       │
│  GPS coordinates)                   │
└─────────────────────────────────────┘
              │
              ▼
Step 3: Confirmation
┌─────────────────────────────────────┐
│ Thank you! Report #1234 submitted.  │
│ Rangers have been alerted.          │
│ You will receive ₦100 if verified.  │
└─────────────────────────────────────┘
```

## 2.6 Security Considerations

| Area | Measure |
| --- | --- |
| API Keys | Stored in `.env`, never committed |
| USSD Callback | Validate `sessionId` and `phoneNumber` |
| SQL Injection | Use Laravel Eloquent ORM |
| XSS | Blade auto-escaping |
| CSRF | API routes use token or exempt for callbacks |
| Rate Limiting | Apply on USSD/SMS endpoints |

---

## 3\. Technical Design Document (TDD)

## 3.1 Project Setup

### Step 1: Create Laravel Project

```
composer create-project laravel/laravel wildlife-alert
cd wildlife-alert
```

### Step 2: Install Africa's Talking PHP SDK

```
composer require africastalking/africastalking
```

The official PHP SDK provides access to SMS, USSD, Airtime, Voice, and Payments services [12](https://github.com/S-Rasheed/africastalking-php) [11](https://root.packagist.org/packages/appslabke/africastalking).

### Step 3: Environment Configuration (.env)

```
APP_NAME=WildlifeAlert
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=wildlife_alert
DB_USERNAME=root
DB_PASSWORD=

# Africa's Talking
AFRICASTALKING_USERNAME=sandbox
AFRICASTALKING_API_KEY=your_sandbox_api_key
AFRICASTALKING_FROM=your_shortcode_or_sender_id
AFRICASTALKING_CURRENCY=NGN
```

### Step 4: Create Service Provider

```
php artisan make:provider AfricasTalkingServiceProvider
```

Register the SDK as a singleton in the service container:

```
// app/Providers/AfricasTalkingServiceProvider.php
use AfricasTalking\SDK\AfricasTalking;

public function register()
{
    $this->app->singleton(AfricasTalking::class, function ($app) {
        $username = config('services.africastalking.username');
        $apiKey = config('services.africastalking.api_key');
        return new AfricasTalking($username, $apiKey);
    });
}
```

### Step 5: Add Config (config/services.php)

```
'africastalking' => [
    'username' => env('AFRICASTALKING_USERNAME', 'sandbox'),
    'api_key' => env('AFRICASTALKING_API_KEY'),
    'from' => env('AFRICASTALKING_FROM'),
    'currency' => env('AFRICASTALKING_CURRENCY', 'NGN'),
],
```

## 3.2 Core Service Classes

### USSD Service (app/Services/UssdService.php)

Handles USSD session logic and menu flow.

```
<?php

namespace App\Services;

use App\Models\Report;
use App\Models\UssdSession;
use AfricasTalking\SDK\AfricasTalking;

class UssdService
{
    protected $at;

    public function __construct(AfricasTalking $at)
    {
        $this->at = $at;
    }

    public function handleRequest(array $input): string
    {
        $sessionId = $input['sessionId'];
        $phoneNumber = $input['phoneNumber'];
        $text = $input['text'] ?? '';

        $session = UssdSession::firstOrCreate(
            ['session_id' => $sessionId],
            ['phone_number' => $phoneNumber, 'current_step' => 0]
        );

        $response = $this->processStep($session, $text);

        return $response;
    }

    protected function processStep(UssdSession $session, string $input): string
    {
        $step = $session->current_step;

        switch ($step) {
            case 0:
                $session->current_step = 1;
                $session->save();
                return "CON Welcome to Wildlife Alert System\n1. Report Incident\n2. Check My Reports\n3. Check Balance";

            case 1:
                $session->data = ['incident_type' => $input];
                $session->current_step = 2;
                $session->save();
                return "CON Enter location (e.g., 'Near River Bridge'):";

            case 2:
                $data = $session->data;
                $data['location'] = $input;
                $session->data = $data;
                $session->current_step = 3;
                $session->save();

                // Create report
                $report = Report::create([
                    'reference_id' => 'REP-' . strtoupper(uniqid()),
                    'phone_number' => $session->phone_number,
                    'incident_type' => $this->mapIncidentType($data['incident_type']),
                    'location' => $data['location'],
                    'status' => 'pending',
                ]);

                // Alert rangers via SMS
                $this->alertRangers($report);

                // Clear session
                $session->delete();

                return "END Thank you! Report #{$report->reference_id} submitted.\nRangers have been alerted.\nYou will receive ₦100 if verified.";

            default:
                $session->delete();
                return "END Session expired. Please dial again.";
        }
    }

    protected function alertRangers(Report $report): void
    {
        $rangers = \App\Models\Ranger::where('is_active', true)->get();

        $sms = $this->at->sms();
        $message = "[ALERT] {$report->incident_type} at {$report->location}. Ref: #{$report->reference_id}";

        foreach ($rangers as $ranger) {
            $sms->send([
                'to' => $ranger->phone_number,
                'message' => $message,
                'from' => config('services.africastalking.from'),
            ]);
        }
    }

    protected function mapIncidentType(string $input): string
    {
        $map = ['1' => 'poaching', '2' => 'snare', '3' => 'injured_animal'];
        return $map[$input] ?? 'poaching';
    }
}
```

### Airtime Service (app/Services/AirtimeService.php)

Handles airtime reward distribution.

```
<?php

namespace App\Services;

use App\Models\Report;
use App\Models\Reward;
use AfricasTalking\SDK\AfricasTalking;

class AirtimeService
{
    protected $at;
    protected $currency;

    public function __construct(AfricasTalking $at)
    {
        $this->at = $at;
        $this->currency = config('services.africastalking.currency', 'NGN');
    }

    public function sendReward(Report $report): bool
    {
        // Check if reward already sent
        if (Reward::where('report_id', $report->id)->where('status', 'sent')->exists()) {
            return false;
        }

        $airtime = $this->at->airtime();
        
        $response = $airtime->send([
            'recipients' => [
                [
                    'phoneNumber' => $report->phone_number,
                    'currencyCode' => $this->currency,
                    'amount' => (float) $report->reward_amount,
                ]
            ]
        ]);

        $reward = Reward::create([
            'report_id' => $report->id,
            'phone_number' => $report->phone_number,
            'amount' => $report->reward_amount,
            'currency_code' => $this->currency,
            'status' => $response['status'] === 'success' ? 'sent' : 'failed',
            'transaction_id' => $response['data']['responses'][0]['transactionId'] ?? null,
            'error_message' => $response['errorMessage'] ?? null,
        ]);

        return $reward->status === 'sent';
    }
}
```

### Report Service (app/Services/ReportService.php)

Manages report verification and reward triggering.

```
<?php

namespace App\Services;

use App\Models\Report;

class ReportService
{
    protected $airtimeService;

    public function __construct(AirtimeService $airtimeService)
    {
        $this->airtimeService = $airtimeService;
    }

    public function verify(Report $report, int $adminId): bool
    {
        if ($report->status !== 'pending') {
            return false;
        }

        $report->status = 'verified';
        $report->verified_by = $adminId;
        $report->save();

        // Send airtime reward
        return $this->airtimeService->sendReward($report);
    }

    public function reject(Report $report, int $adminId): bool
    {
        if ($report->status !== 'pending') {
            return false;
        }

        $report->status = 'rejected';
        $report->verified_by = $adminId;
        $report->save();

        return true;
    }
}
```

## 3.3 Controller Examples

### USSD Controller (app/Http/Controllers/UssdController.php)

```
<?php

namespace App\Http\Controllers;

use App\Services\UssdService;
use Illuminate\Http\Request;

class UssdController extends Controller
{
    protected $ussdService;

    public function __construct(UssdService $ussdService)
    {
        $this->ussdService = $ussdService;
    }

    public function callback(Request $request)
    {
        $response = $this->ussdService->handleRequest($request->all());
        return response($response, 200)
            ->header('Content-Type', 'text/plain');
    }
}
```

### Admin Dashboard Controller (app/Http/Controllers/Admin/ReportController.php)

```
<?php

namespace App\Http\Controllers\Admin;

use App\Models\Report;
use App\Services\ReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index()
    {
        $reports = Report::with('verifier')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $stats = [
            'total' => Report::count(),
            'pending' => Report::where('status', 'pending')->count(),
            'verified' => Report::where('status', 'verified')->count(),
            'rejected' => Report::where('status', 'rejected')->count(),
        ];

        return view('admin.reports.index', compact('reports', 'stats'));
    }

    public function verify(Report $report)
    {
        $success = $this->reportService->verify($report, auth()->id());

        return redirect()->back()->with(
            $success ? 'success' : 'error',
            $success ? 'Report verified and reward sent!' : 'Failed to verify report.'
        );
    }

    public function reject(Report $report)
    {
        $success = $this->reportService->reject($report, auth()->id());

        return redirect()->back()->with(
            $success ? 'success' : 'error',
            $success ? 'Report rejected.' : 'Failed to reject report.'
        );
    }
}
```

## 3.4 Routes (routes/api.php)

```
<?php

use App\Http\Controllers\UssdController;
use App\Http\Controllers\Admin\ReportController;

// USSD Callback (public)
Route::post('/ussd/callback', [UssdController::class, 'callback']);

// Admin Routes (protected)
Route::prefix('admin')->middleware('auth')->group(function () {
    Route::get('/reports', [ReportController::class, 'index'])->name('admin.reports');
    Route::post('/reports/{report}/verify', [ReportController::class, 'verify'])->name('admin.reports.verify');
    Route::post('/reports/{report}/reject', [ReportController::class, 'reject'])->name('admin.reports.reject');
});
```

## 3.5 Docker Configuration

### Dockerfile

```
FROM php:8.2-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy existing application directory contents
COPY . /var/www

# Install dependencies
RUN composer install --no-interaction --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
RUN chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Expose port 9000
EXPOSE 9000

CMD ["php-fpm"]
```

### docker-compose.yml

```
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: wildlife-alert-app
    restart: unless-stopped
    working_dir: /var/www
    volumes:
      - ./:/var/www
    networks:
      - wildlife-network
    depends_on:
      - db

  db:
    image: mysql:8.0
    container_name: wildlife-alert-db
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: wildlife_alert
      MYSQL_ROOT_PASSWORD: rootpassword
    ports:
      - "3306:3306"
    volumes:
      - db_data:/var/lib/mysql
    networks:
      - wildlife-network

  nginx:
    image: nginx:alpine
    container_name: wildlife-alert-nginx
    restart: unless-stopped
    ports:
      - "8000:80"
    volumes:
      - ./:/var/www
      - ./docker/nginx.conf:/etc/nginx/conf.d/default.conf
    networks:
      - wildlife-network
    depends_on:
      - app

networks:
  wildlife-network:
    driver: bridge

volumes:
  db_data:
```

### Nginx Config (docker/nginx.conf)

```
server {
    listen 80;
    server_name localhost;
    root /var/www/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## 3.6 Testing Strategy

### Unit Tests

```
php artisan make:test UssdServiceTest --unit
php artisan make:test AirtimeServiceTest --unit
```

### Feature Tests

```
php artisan make:test UssdCallbackTest
php artisan make:test ReportVerificationTest
```

### Sample Test (tests/Unit/UssdServiceTest.php)

```
<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\UssdService;
use App\Models\UssdSession;

class UssdServiceTest extends TestCase
{
    public function test_welcome_screen_displays_correct_menu()
    {
        $service = app(UssdService::class);
        $response = $service->handleRequest([
            'sessionId' => 'test123',
            'phoneNumber' => '+2347000000000',
            'text' => '',
        ]);

        $this->assertStringContainsString('Welcome to Wildlife Alert System', $response);
        $this->assertStringContainsString('1. Report Incident', $response);
        $this->assertStringStartsWith('CON', $response);
    }
}
```

## 3.7 Deployment Checklist

| Step | Action |
| --- | --- |
| 1 | Set up Africa's Talking sandbox account |
| 2 | Configure USSD callback URL to `https://your-domain.com/api/ussd/callback` |
| 3 | Configure SMS callback URL (if using incoming SMS) |
| 4 | Run migrations: `php artisan migrate` |
| 5 | Seed rangers: `php artisan db:seed --class=RangerSeeder` |
| 6 | Build Docker image: `docker build -t wildlife-alert .` |
| 7 | Run containers: `docker-compose up -d` |
| 8 | Test USSD flow using AT simulator |
| 9 | Submit to AT Marketplace |

---

## Reference Links

- **Official Africa's Talking API Documentation:** https://developers.africastalking.com\[reference:2\]
- **Official PHP SDK:** [https://github.com/S-Rasheed/africastalking-php\[reference:3\]](https://github.com/S-Rasheed/africastalking-php%5Breference:3%5D)
- **Packagist Package:** [https://packagist.org/packages/africastalking/africastalking\[reference:4\]](https://packagist.org/packages/africastalking/africastalking%5Breference:4%5D)

---








