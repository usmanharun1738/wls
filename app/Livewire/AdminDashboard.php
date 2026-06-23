<?php

namespace App\Livewire;

use App\Models\Ranger;
use App\Models\Report;
use App\Services\AirtimeService;
use Flux\Flux;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Title('Wildlife Reports')]
class AdminDashboard extends Component
{
    use WithPagination;

    public string $filterStatus = '';

    public string $filterType = '';

    public string $filterDateFrom = '';

    public string $filterDateTo = '';

    public string $search = '';

    public ?int $rejectingReportId = null;

    public string $rejectionReason = '';

    public int $lastSeenReportId = 0;

    public function mount(): void
    {
        $this->lastSeenReportId = (int) Report::max('id');
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatingFilterType(): void
    {
        $this->resetPage();
    }

    public function updatingFilterDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingFilterDateTo(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->filterStatus = '';
        $this->filterType = '';
        $this->filterDateFrom = '';
        $this->filterDateTo = '';
        $this->search = '';
        $this->resetPage();
    }

    public function filterByStatus(string $status): void
    {
        $this->filterStatus = $this->filterStatus === $status ? '' : $status;
        $this->resetPage();
    }

    public function setDatePreset(string $preset): void
    {
        $this->filterDateFrom = match ($preset) {
            'today' => today()->toDateString(),
            'yesterday' => today()->subDay()->toDateString(),
            'week' => today()->subWeek()->toDateString(),
            'month' => today()->subMonth()->toDateString(),
            default => '',
        };
        $this->filterDateTo = today()->toDateString();
        $this->resetPage();
    }

    public function export(): StreamedResponse
    {
        $query = Report::with('verifier')->latest();

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }
        if ($this->filterType) {
            $query->where('incident_type', $this->filterType);
        }
        if ($this->filterDateFrom) {
            $query->whereDate('created_at', '>=', $this->filterDateFrom);
        }
        if ($this->filterDateTo) {
            $query->whereDate('created_at', '<=', $this->filterDateTo);
        }
        if ($this->search) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('location', 'like', "%{$search}%")
                    ->orWhere('reference_id', 'like', "%{$search}%");
            });
        }

        $reports = $query->get();

        return response()->streamDownload(function () use ($reports) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Ref ID', 'Phone', 'Type', 'Location', 'Status', 'Date', 'Verified By', 'Rejection Reason']);

            foreach ($reports as $r) {
                fputcsv($handle, [
                    $r->reference_id,
                    $r->phone_number,
                    $r->incident_type,
                    $r->location,
                    $r->status,
                    $r->created_at->toDateTimeString(),
                    $r->verifier?->name ?? '',
                    $r->rejection_reason ?? '',
                ]);
            }

            fclose($handle);
        }, 'wls-reports-'.now()->format('Y-m-d-His').'.csv');
    }

    public function verify(Report $report): void
    {
        if (! $report->isPending()) {
            Flux::toast(variant: 'error', text: 'Report is not pending.');

            return;
        }

        $report->update([
            'status' => 'verified',
            'verified_by' => auth()->id(),
        ]);

        // Send airtime reward (fire-and-forget — don't block verify)
        try {
            $sent = app(AirtimeService::class)->sendReward($report);
            $msg = $sent
                ? "Report #{$report->reference_id} verified. Airtime reward sent!"
                : "Report #{$report->reference_id} verified. Airtime pending.";
        } catch (\Throwable $e) {
            logger()->error('Airtime reward failed: '.$e->getMessage());
            $msg = "Report #{$report->reference_id} verified. Airtime queued.";
        }

        // Send SMS notification to reporter
        try {
            $smsMessage = "Congratulations! Your report #{$report->reference_id} has been verified and a reward of NGN {$report->reward_amount} airtime has been sent to {$report->phone_number}. Thank you for helping protect wildlife! - WLS Team";
            app(SmsService::class)->send($report->phone_number, $smsMessage);
        } catch (\Throwable $e) {
            logger()->error('Reporter SMS notification failed: '.$e->getMessage());
        }

        Flux::toast(variant: 'success', text: $msg);
    }

    public function startReject(Report $report): void
    {
        if (! $report->isPending()) {
            Flux::toast(variant: 'error', text: 'Report is not pending.');

            return;
        }

        $this->rejectingReportId = $report->id;
        $this->rejectionReason = '';
    }

    public function confirmReject(): void
    {
        $report = Report::find($this->rejectingReportId);

        if (! $report || ! $report->isPending()) {
            Flux::toast(variant: 'error', text: 'Report is not pending.');
            $this->rejectingReportId = null;

            return;
        }

        $report->update([
            'status' => 'rejected',
            'rejection_reason' => $this->rejectionReason ?: null,
            'verified_by' => auth()->id(),
        ]);

        Flux::toast(variant: 'success', text: "Report #{$report->reference_id} rejected.");

        $this->rejectingReportId = null;
        $this->rejectionReason = '';
    }

    public function cancelReject(): void
    {
        $this->rejectingReportId = null;
        $this->rejectionReason = '';
    }

    public function getStats(): array
    {
        return [
            'total' => Report::count(),
            'pending' => Report::where('status', 'pending')->count(),
            'verified' => Report::where('status', 'verified')->count(),
            'rejected' => Report::where('status', 'rejected')->count(),
            'today' => Report::whereDate('created_at', today())->count(),
            'poaching' => Report::where('incident_type', 'poaching')->count(),
            'snare' => Report::where('incident_type', 'snare')->count(),
            'injured_animal' => Report::where('incident_type', 'injured_animal')->count(),
        ];
    }

    public function render(): View
    {
        $query = Report::with(['verifier', 'rangers'])->withCount('rangers')->latest();

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        if ($this->filterType) {
            $query->where('incident_type', $this->filterType);
        }

        if ($this->filterDateFrom) {
            $query->whereDate('created_at', '>=', $this->filterDateFrom);
        }

        if ($this->filterDateTo) {
            $query->whereDate('created_at', '<=', $this->filterDateTo);
        }

        if ($this->search) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('location', 'like', "%{$search}%")
                    ->orWhere('reference_id', 'like', "%{$search}%");
            });
        }

        // Check for new reports since last poll (ignore if no filters active)
        $latestId = (int) Report::max('id');
        if ($latestId > $this->lastSeenReportId && $this->lastSeenReportId > 0) {
            $newCount = Report::where('id', '>', $this->lastSeenReportId)->count();
            Flux::toast(variant: 'success', text: "{$newCount} new report(s) received!");
        }
        $this->lastSeenReportId = $latestId;

        return view('livewire.admin-dashboard', [
            'reports' => $query->paginate(15),
            'stats' => $this->getStats(),
            'rangerCount' => Ranger::where('is_active', true)->count(),
            'rangerBases' => $this->getRangerBaseSummary(),
            'rangerPositions' => $this->getRangerSvgPositions(),
        ]);
    }

    protected function getRangerBaseSummary(): array
    {
        return Ranger::selectRaw('base_location, count(*) as count')
            ->where('is_active', true)
            ->whereNotNull('base_location')
            ->groupBy('base_location')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($r) => ['name' => $r->base_location, 'count' => $r->count])
            ->toArray();
    }

    protected function getRangerSvgPositions(): array
    {
        // Approximate SVG positions for ranger bases within Kaduna state area
        // Kaduna center is roughly (433, 299) in SVG coordinates
        $baseMap = [
            'Kamuku National Park HQ' => ['x' => 350, 'y' => 260, 'label' => 'Kamuku'],
            'Birnin Gwari Forest Station' => ['x' => 340, 'y' => 280, 'label' => 'Birnin Gwari'],
            'Kuyambana Game Reserve Outpost' => ['x' => 310, 'y' => 290, 'label' => 'Kuyambana'],
            'River Kaduna Patrol Base' => ['x' => 450, 'y' => 300, 'label' => 'R. Kaduna'],
            'Dagida Forest Reserve' => ['x' => 350, 'y' => 310, 'label' => 'Dagida'],
            'Kaduna Central Command' => ['x' => 433, 'y' => 290, 'label' => 'HQ'],
            'Kainji Lake National Park' => ['x' => 280, 'y' => 320, 'label' => 'Kainji'],
            'Yankari Game Reserve South' => ['x' => 500, 'y' => 270, 'label' => 'Yankari'],
            'Old Oyo National Park - North Gate' => ['x' => 250, 'y' => 330, 'label' => 'Old Oyo'],
            'Zugurma Game Reserve HQ' => ['x' => 280, 'y' => 340, 'label' => 'Zugurma'],
        ];

        return Ranger::where('is_active', true)
            ->whereNotNull('base_location')
            ->get()
            ->map(function ($r) use ($baseMap) {
                $pos = $baseMap[$r->base_location] ?? ['x' => 433, 'y' => 299, 'label' => null];

                return [
                    'x' => $pos['x'] + rand(-15, 15),
                    'y' => $pos['y'] + rand(-10, 10),
                    'name' => $r->name,
                    'location' => $r->base_location,
                    'label' => $pos['label'],
                ];
            })
            ->toArray();
    }
}
