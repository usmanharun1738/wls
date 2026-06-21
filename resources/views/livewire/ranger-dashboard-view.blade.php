<div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
    {{-- Ranger Info Bar --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="lg">Welcome, {{ $ranger->name }}</flux:heading>
            <flux:text class="mt-1">{{ $ranger->phone_number }} — {{ $ranger->base_location }}</flux:text>
        </div>
        <flux:button wire:click="logout" variant="ghost" icon="arrow-right-start-on-rectangle">
            Sign Out
        </flux:button>
    </div>

    {{-- Stats --}}
    <div class="grid auto-rows-min gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <flux:card class="space-y-1">
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Total Alerts</flux:text>
            <flux:heading size="xl">{{ $stats['totalAlerts'] }}</flux:heading>
        </flux:card>

        <flux:card class="space-y-1">
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Pending</flux:text>
            <div class="flex items-center gap-2">
                <flux:heading size="xl">{{ $stats['pendingAlerts'] }}</flux:heading>
                @if ($stats['pendingAlerts'] > 0)
                    <flux:badge color="amber" size="sm">{{ $stats['pendingAlerts'] }}</flux:badge>
                @endif
            </div>
        </flux:card>

        <flux:card class="space-y-1">
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Verified</flux:text>
            <flux:heading size="xl">{{ $stats['verifiedAlerts'] }}</flux:heading>
        </flux:card>

        <flux:card class="space-y-1">
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Last Alerted</flux:text>
            <flux:heading size="lg" class="truncate">
                {{ $stats['lastAlerted'] ? \Illuminate\Support\Carbon::parse($stats['lastAlerted'])->diffForHumans() : 'Never' }}
            </flux:heading>
        </flux:card>
    </div>

    {{-- Alerts Table --}}
    <flux:card class="flex-1 overflow-hidden" padding="false">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[700px] text-sm">
                <thead class="border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-400">Ref ID</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-400">Type</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-400">Location</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-400">Status</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-400">Reported</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($reports as $report)
                        <tr wire:key="alert-{{ $report->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/30 transition-colors">
                            <td class="px-4 py-3 font-mono text-xs font-medium">
                                #{{ $report->reference_id }}
                            </td>
                            <td class="px-4 py-3">
                                <flux:badge
                                    :color="match($report->incident_type) {
                                        'poaching' => 'red',
                                        'snare' => 'amber',
                                        'injured_animal' => 'blue',
                                        default => 'zinc',
                                    }"
                                    size="sm"
                                >
                                    {{ str_replace('_', ' ', $report->incident_type) }}
                                </flux:badge>
                            </td>
                            <td class="px-4 py-3 max-w-[200px] truncate text-zinc-600 dark:text-zinc-400" title="{{ $report->location }}">
                                {{ $report->location }}
                            </td>
                            <td class="px-4 py-3">
                                <flux:badge
                                    :color="match($report->status) {
                                        'pending' => 'amber',
                                        'verified' => 'green',
                                        'rejected' => 'red',
                                        default => 'zinc',
                                    }"
                                    size="sm"
                                >
                                    {{ ucfirst($report->status) }}
                                </flux:badge>
                            </td>
                            <td class="px-4 py-3 text-xs text-zinc-500 dark:text-zinc-400 whitespace-nowrap">
                                {{ $report->created_at->diffForHumans() }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-16 text-center">
                                <div class="space-y-2">
                                    <flux:icon name="bell-slash" class="mx-auto size-8 text-zinc-300 dark:text-zinc-600" />
                                    <flux:text class="text-zinc-500 dark:text-zinc-400">
                                        No alerts yet. You'll be notified when incidents are reported in your area.
                                    </flux:text>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </flux:card>
</div>
