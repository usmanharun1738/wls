<?php

namespace App\Livewire;

use App\Models\Report;
use App\Services\AirtimeService;
use App\Services\SmsService;
use Flux\Flux;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Title('Wildlife Reports')]
class ReportsDashboard extends Component
{
    use WithPagination;

    public string $filterStatus = '';

    public string $filterType = '';

    public string $filterDateFrom = '';

    public string $filterDateTo = '';

    public string $search = '';

    public ?int $rejectingReportId = null;

    public string $rejectionReason = '';

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
            fputcsv($handle, ['Ref ID', 'Phone', 'Type', 'Location', 'Additional Info', 'Status', 'Date', 'Verified By', 'Rejection Reason']);

            foreach ($reports as $r) {
                fputcsv($handle, [
                    $r->reference_id,
                    $r->phone_number,
                    $r->incident_type,
                    $r->location,
                    $r->additional_info ?? '',
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

        // Send airtime reward
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

        if (! $report) {
            Flux::toast(variant: 'error', text: 'Report not found.');
            $this->rejectingReportId = null;

            return;
        }

        $report->refresh();

        if (! $report->isPending()) {
            Flux::toast(variant: 'error', text: "Report #{$report->reference_id} is not pending (current status: {$report->status}).");
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

        return view('livewire.reports-dashboard', [
            'reports' => $query->paginate(15),
            'stats' => $this->getStats(),
        ]);
    }
}
