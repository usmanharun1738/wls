<div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
    {{-- Stats Cards --}}
    <div class="grid auto-rows-min gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <flux:card class="space-y-1">
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Total Reports</flux:text>
            <flux:heading size="xl">{{ $stats['total'] }}</flux:heading>
        </flux:card>
        <flux:card class="space-y-1">
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Pending</flux:text>
            <div class="flex items-center gap-2">
                <flux:heading size="xl">{{ $stats['pending'] }}</flux:heading>
                @if ($stats['pending'] > 0)
                    <flux:badge color="amber" size="sm">{{ $stats['pending'] }}</flux:badge>
                @endif
            </div>
        </flux:card>
        <flux:card class="space-y-1">
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Verified</flux:text>
            <flux:heading size="xl">{{ $stats['verified'] }}</flux:heading>
        </flux:card>
        <flux:card class="space-y-1">
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Rejected</flux:text>
            <flux:heading size="xl">{{ $stats['rejected'] }}</flux:heading>
        </flux:card>
        <flux:card class="space-y-1">
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Today</flux:text>
            <flux:heading size="xl">{{ $stats['today'] }}</flux:heading>
        </flux:card>
    </div>
 {{-- Location Hotspots + Incident Breakdown --}}
    <div class="grid gap-4 lg:grid-cols-2">
        <flux:card class="space-y-4">
            <div>
                <flux:heading size="base"> Location Hotspots</flux:heading>
                <flux:text class="text-xs text-zinc-500 mt-1">Top 5 report locations</flux:text>
            </div>
            <div class="space-y-3">
                @php $max = collect($hotspots)->max('count') ?: 1; @endphp
                @forelse ($hotspots as $spot)
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-sm">
                            <span class="truncate mr-2">{{ $spot['location'] }}</span>
                            <flux:badge color="indigo" size="sm">{{ $spot['count'] }} reports</flux:badge>
                        </div>
                        <div class="h-2 w-full rounded-full bg-zinc-100 dark:bg-zinc-700 overflow-hidden">
                            <div class="h-full rounded-full bg-gradient-to-r from-green-500 to-emerald-400 transition-all"
                                 style="width: {{ ($spot['count'] / $max) * 100 }}%"></div>
                        </div>
                    </div>
                @empty
                    <flux:text class="text-xs text-zinc-400">No reports yet. Submit via USSD to see hotspots.</flux:text>
                @endforelse
            </div>
        </flux:card>

        <flux:card class="space-y-4">
            <div>
                <flux:heading size="base">Incident Breakdown</flux:heading>
                <flux:text class="text-xs text-zinc-500 mt-1">Reports by type</flux:text>
            </div>
            <div class="space-y-3">
                @php
                    $incidents = [
                        ['label' => 'Poaching', 'count' => $stats['poaching'], 'color' => 'red'],
                        ['label' => 'Snare/Trap', 'count' => $stats['snare'], 'color' => 'amber'],
                        ['label' => 'Injured Animal', 'count' => $stats['injured_animal'], 'color' => 'blue'],
                    ];
                    $maxIncident = max(collect($incidents)->max('count'), 1);
                @endphp
                @foreach ($incidents as $item)
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-sm">
                            <div class="flex items-center gap-2">
                                <flux:badge :color="$item['color']" size="sm">{{ $item['label'] }}</flux:badge>
                            </div>
                            <flux:badge size="sm">{{ $item['count'] }}</flux:badge>
                        </div>
                        <div class="h-2 w-full rounded-full bg-zinc-100 dark:bg-zinc-700 overflow-hidden">
                            <div class="h-full rounded-full transition-all"
                                 style="width: {{ ($item['count'] / $maxIncident) * 100 }}%;
                                        background-color: {{ match($item['color']) {
                                            'red' => '#ef4444', 'amber' => '#f59e0b', 'blue' => '#3b82f6',
                                            default => '#6b7280'
                                        } }}"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </flux:card>
    </div>
    {{-- Nigeria Ranger Coverage Map --}}
    @include('livewire.nigeria-map')


</div>
