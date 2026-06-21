<?php

namespace App\Livewire;

use App\Models\Ranger;
use Flux\Flux;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Ranger Login')]
#[Layout('layouts.auth')]
class RangerLogin extends Component
{
    public string $phoneNumber = '';

    public string $pin = '';

    public function login(): void
    {
        $this->validate([
            'phoneNumber' => ['required', 'string', 'min:10'],
            'pin' => ['required', 'string', 'size:4'],
        ]);

        $ranger = Ranger::where('phone_number', $this->phoneNumber)
            ->where('pin', $this->pin)
            ->where('is_active', true)
            ->first();

        if (! $ranger) {
            Flux::toast(variant: 'error', text: 'Invalid phone number or PIN.');

            return;
        }

        session(['ranger_id' => $ranger->id, 'ranger_name' => $ranger->name]);

        $this->redirect(route('ranger.dashboard'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.ranger-login');
    }
}
