<?php

namespace App\Livewire;

use App\Models\Ranger;
use App\Models\Report;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Rangers')]
class RangersDashboard extends Component
{
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
        $rangers = Ranger::withCount([
            'reports' => function ($q) {
                $q->where('status', 'pending');
            },
        ])
            ->orderBy('is_active', 'desc')
            ->orderBy('name')
            ->get();

        return view('livewire.rangers-dashboard', [
            'rangers' => $rangers,
            'stats' => $this->getStats(),
        ]);
    }
}
