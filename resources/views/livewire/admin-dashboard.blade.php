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

    {{-- Filters --}}
    <flux:card class="space-y-4">
        <div class="flex flex-wrap items-end gap-4">
            <div class="w-full sm:w-auto sm:min-w-[160px]">
                <flux:select wire:model.live="filterStatus" label="Status">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="verified">Verified</option>
                    <option value="rejected">Rejected</option>
                </flux:select>
            </div>

            <div class="w-full sm:w-auto sm:min-w-[160px]">
                <flux:select wire:model.live="filterType" label="Incident Type">
                    <option value="">All Types</option>
                    <option value="poaching">Poaching</option>
                    <option value="snare">Snare/Trap</option>
                    <option value="injured_animal">Injured Animal</option>
                </flux:select>
            </div>

            <div class="w-full sm:w-auto sm:min-w-[150px]">
                <flux:input wire:model.live="filterDateFrom" type="date" label="Date From" />
            </div>

            <div class="w-full sm:w-auto sm:min-w-[150px]">
                <flux:input wire:model.live="filterDateTo" type="date" label="Date To" />
            </div>

            <div class="w-full sm:w-auto sm:flex-1">
                <flux:input wire:model.live="search" placeholder="Search location or ref ID..." icon="magnifying-glass" label="Search" />
            </div>

            <div class="flex items-end gap-2">
                @if ($filterStatus || $filterType || $filterDateFrom || $filterDateTo || $search)
                    <flux:button wire:click="clearFilters" variant="subtle" size="sm" icon="x-mark">
                        Clear
                    </flux:button>
                @endif
            </div>
        </div>
    </flux:card>

    {{-- Reports Table --}}
    <flux:card class="flex-1 overflow-hidden" padding="false">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-sm">
                <thead class="border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-400">Ref ID</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-400">Phone</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-400">Type</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-400">Location</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-400">Status</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-400">Date</th>
                        <th class="px-4 py-3 text-right font-medium text-zinc-600 dark:text-zinc-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($reports as $report)
                        <tr wire:key="report-{{ $report->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/30 transition-colors">
                            <td class="px-4 py-3 font-mono text-xs font-medium">
                                #{{ $report->reference_id }}
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                {{ $report->phone_number }}
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
                            <td class="px-4 py-3 text-zinc-500 dark:text-zinc-400 whitespace-nowrap">
                                {{ $report->created_at->format('M j, Y H:i') }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if ($report->isPending())
                                    <div class="flex justify-end gap-2">
                                        <flux:button
                                            wire:click="verify({{ $report->id }})"
                                            variant="primary"
                                            size="xs"
                                        >
                                            Verify
                                        </flux:button>
                                        <flux:button
                                            wire:click="reject({{ $report->id }})"
                                            variant="danger"
                                            size="xs"
                                        >
                                            Reject
                                        </flux:button>
                                    </div>
                                @else
                                    <flux:text class="text-xs text-zinc-400">
                                        {{ $report->verifier?->initials() ?? '—' }}
                                    </flux:text>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-16 text-center">
                                <div class="space-y-2">
                                    <flux:icon name="document-text" class="mx-auto size-8 text-zinc-300 dark:text-zinc-600" />
                                    <flux:text class="text-zinc-500 dark:text-zinc-400">
                                        No reports found
                                    </flux:text>
                                    @if ($filterStatus || $filterType || $filterDateFrom || $filterDateTo || $search)
                                        <flux:button wire:click="clearFilters" variant="subtle" size="sm">
                                            Clear filters
                                        </flux:button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($reports->hasPages())
            <div class="border-t border-zinc-200 dark:border-zinc-700 px-4 py-3">
                {{ $reports->links() }}
            </div>
        @endif
    </flux:card>
</div>
