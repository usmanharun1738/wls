<?php

namespace App\Livewire;

use App\Models\Ranger;
use App\Models\Report;
use Flux\Flux;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Rangers')]
class RangersDashboard extends Component
{
    public string $search = '';

    public string $filterStatus = '';

    public function toggleActive(Ranger $ranger): void
    {
        $ranger->update(['is_active' => ! $ranger->is_active]);

        Flux::toast(
            variant: 'success',
            text: $ranger->is_active
                ? "{$ranger->name} is now active."
                : "{$ranger->name} has been deactivated.",
        );
    }

    public function getStats(): array
    {
        return [
            'total' => Ranger::count(),
            'active' => Ranger::where('is_active', true)->count(),
            'inactive' => Ranger::where('is_active', false)->count(),
            'totalReports' => Report::count(),
            'reportsToday' => Report::whereDate('created_at', today())->count(),
        ];
    }

    public function render(): View
    {
        $query = Ranger::query()
            ->withCount('reports')
            ->withMax('reports as last_alerted_at', 'report_ranger.alerted_at');

        if ($this->filterStatus !== '') {
            $query->where('is_active', $this->filterStatus === 'active');
        }

        if ($this->search) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('base_location', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        $rangers = $query
            ->orderBy('is_active', 'desc')
            ->orderBy('name')
            ->get();

        return view('livewire.rangers-dashboard', [
            'rangers' => $rangers,
            'stats' => $this->getStats(),
        ]);
    }
}
