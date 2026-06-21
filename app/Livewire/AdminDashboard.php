<?php

namespace App\Livewire;

use App\Models\Report;
use Flux\Flux;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

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

        Flux::toast(variant: 'success', text: "Report #{$report->reference_id} verified successfully.");
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
        ];
    }

    public function render(): View
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

        return view('livewire.admin-dashboard', [
            'reports' => $query->paginate(15),
            'stats' => $this->getStats(),
        ]);
    }
}
