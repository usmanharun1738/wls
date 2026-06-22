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

        $phone = $this->normalizePhone($this->phoneNumber);

        $ranger = Ranger::where('phone_number', $phone)
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

    /**
     * Normalize Nigerian phone numbers to international format.
     * 08119106475 → +2348119106475
     */
    protected function normalizePhone(string $phone): string
    {
        $phone = trim($phone);

        // Already international format
        if (str_starts_with($phone, '+')) {
            return $phone;
        }

        // Strip any non-digit characters
        $digits = preg_replace('/[^0-9]/', '', $phone);

        // 234-prefixed without + (2348119106475 → +2348119106475)
        if (str_starts_with($digits, '234') && strlen($digits) === 13) {
            return '+'.$digits;
        }

        // Local format with leading 0 (08119106475 → +2348119106475)
        if (str_starts_with($digits, '0') && strlen($digits) === 11) {
            return '+234'.substr($digits, 1);
        }

        // Assume it's already valid
        return '+'.$digits;
    }

    public function render(): View
    {
        return view('livewire.ranger-login');
    }
}
