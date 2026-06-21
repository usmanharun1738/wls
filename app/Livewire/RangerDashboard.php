<?php

namespace App\Livewire;

use App\Models\Ranger;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('My Alerts')]
#[Layout('layouts.auth')]
class RangerDashboard extends Component
{
    public function logout(): void
    {
        session()->forget(['ranger_id', 'ranger_name']);

        $this->redirect(route('ranger.login'), navigate: true);
    }

    public function getRanger(): ?Ranger
    {
        return Ranger::find(session('ranger_id'));
    }

    public function getStats(Ranger $ranger): array
    {
        return [
            'totalAlerts' => $ranger->reports()->count(),
            'pendingAlerts' => $ranger->reports()->where('status', 'pending')->count(),
            'verifiedAlerts' => $ranger->reports()->where('status', 'verified')->count(),
            'lastAlerted' => $ranger->reports()->latest('report_ranger.alerted_at')->first()?->pivot->alerted_at,
        ];
    }

    public function render(): View
    {
        $ranger = $this->getRanger();

        if (! $ranger) {
            session()->forget(['ranger_id', 'ranger_name']);

            return view('livewire.ranger-dashboard', [
                'ranger' => null,
                'stats' => [],
                'reports' => collect(),
            ]);
        }

        $reports = $ranger->reports()
            ->latest('report_ranger.alerted_at')
            ->get();

        return view('livewire.ranger-dashboard-view', [
            'ranger' => $ranger,
            'stats' => $this->getStats($ranger),
            'reports' => $reports,
        ]);
    }
}
