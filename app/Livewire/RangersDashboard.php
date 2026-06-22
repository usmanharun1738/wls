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

    // --- CRUD Modal State ---
    public bool $showCreateModal = false;

    public bool $showEditModal = false;

    public bool $showDeleteModal = false;

    public ?int $editingRangerId = null;

    public ?int $deletingRangerId = null;

    // --- Form Fields ---
    public string $editName = '';

    public string $editPhone = '';

    public string $editEmail = '';

    public string $editLocation = '';

    public string $editPin = '';

    // --- Create ---
    public function openCreate(): void
    {
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function create(): void
    {
        $this->validate([
            'editName' => ['required', 'string', 'max:100'],
            'editPhone' => ['required', 'string', 'min:10'],
            'editPin' => ['required', 'string', 'size:4'],
        ]);

        Ranger::create([
            'name' => $this->editName,
            'phone_number' => $this->normalizePhone($this->editPhone),
            'email' => $this->editEmail ?: null,
            'base_location' => $this->editLocation ?: null,
            'pin' => $this->editPin,
            'is_active' => true,
        ]);

        Flux::toast(variant: 'success', text: "{$this->editName} added successfully.");

        $this->showCreateModal = false;
        $this->resetForm();
    }

    // --- Edit ---
    public function openEdit(Ranger $ranger): void
    {
        $this->editingRangerId = $ranger->id;
        $this->editName = $ranger->name;
        $this->editPhone = $ranger->phone_number;
        $this->editEmail = $ranger->email ?? '';
        $this->editLocation = $ranger->base_location ?? '';
        $this->editPin = $ranger->pin;
        $this->showEditModal = true;
    }

    public function update(): void
    {
        $this->validate([
            'editName' => ['required', 'string', 'max:100'],
            'editPhone' => ['required', 'string', 'min:10'],
            'editPin' => ['required', 'string', 'size:4'],
        ]);

        $ranger = Ranger::findOrFail($this->editingRangerId);
        $ranger->update([
            'name' => $this->editName,
            'phone_number' => $this->normalizePhone($this->editPhone),
            'email' => $this->editEmail ?: null,
            'base_location' => $this->editLocation ?: null,
            'pin' => $this->editPin,
        ]);

        Flux::toast(variant: 'success', text: "{$this->editName} updated successfully.");

        $this->showEditModal = false;
        $this->resetForm();
    }

    // --- Delete ---
    public function openDelete(Ranger $ranger): void
    {
        $this->deletingRangerId = $ranger->id;
        $this->editName = $ranger->name;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $ranger = Ranger::findOrFail($this->deletingRangerId);
        $name = $ranger->name;
        $ranger->delete();

        Flux::toast(variant: 'success', text: "{$name} removed.");

        $this->showDeleteModal = false;
        $this->deletingRangerId = null;
    }

    // --- Toggle Active ---
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

    // --- Helpers ---
    protected function normalizePhone(string $phone): string
    {
        $phone = trim($phone);

        if (str_starts_with($phone, '+')) {
            return $phone;
        }

        $digits = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($digits, '234') && strlen($digits) === 13) {
            return '+'.$digits;
        }

        if (str_starts_with($digits, '0') && strlen($digits) === 11) {
            return '+234'.substr($digits, 1);
        }

        return '+'.$digits;
    }

    protected function resetForm(): void
    {
        $this->editingRangerId = null;
        $this->editName = '';
        $this->editPhone = '';
        $this->editEmail = '';
        $this->editLocation = '';
        $this->editPin = '';
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
