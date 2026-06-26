<?php

namespace App\Livewire;

use App\Models\Ranger;
use App\Models\Report;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Dashboard')]
class AdminDashboard extends Component
{
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

    public function getHotspots(): array
    {
        return Report::selectRaw('location, count(*) as count')
            ->groupBy('location')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->toArray();
    }

    public function render(): View
    {
        return view('livewire.admin-dashboard', [
            'stats' => $this->getStats(),
            'hotspots' => $this->getHotspots(),
            'rangerCount' => Ranger::where('is_active', true)->count(),
            'rangerBases' => $this->getRangerBaseSummary(),
            'rangerPositions' => $this->getRangerSvgPositions(),
        ]);
    }

    protected function getRangerBaseSummary(): array
    {
        return Ranger::selectRaw('base_location, count(*) as count')
            ->where('is_active', true)->whereNotNull('base_location')
            ->groupBy('base_location')->orderByDesc('count')
            ->get()->map(fn ($r) => ['name' => $r->base_location, 'count' => $r->count])
            ->toArray();
    }

    protected function getRangerSvgPositions(): array
    {
        $baseMap = [
            'Kamuku National Park HQ' => ['x' => 350, 'y' => 260, 'label' => 'Kamuku'],
            'Birnin Gwari Forest Station' => ['x' => 340, 'y' => 280, 'label' => 'Birnin Gwari'],
            'Kuyambana Game Reserve Outpost' => ['x' => 310, 'y' => 290, 'label' => 'Kuyambana'],
            'River Kaduna Patrol Base' => ['x' => 450, 'y' => 300, 'label' => 'R. Kaduna'],
            'Dagida Forest Reserve' => ['x' => 350, 'y' => 310, 'label' => 'Dagida'],
            'Kaduna Central Command' => ['x' => 433, 'y' => 290, 'label' => 'HQ'],
        ];

        return Ranger::where('is_active', true)->whereNotNull('base_location')->get()
            ->map(fn ($r) => array_merge($baseMap[$r->base_location] ?? ['x' => 433, 'y' => 299, 'label' => null], [
                'x' => ($baseMap[$r->base_location]['x'] ?? 433) + rand(-15, 15),
                'y' => ($baseMap[$r->base_location]['y'] ?? 299) + rand(-10, 10),
                'name' => $r->name, 'location' => $r->base_location,
            ]))->toArray();
    }
}
