<div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl" wire:poll.30s>
    {{-- Stats Cards --}}
    <div class="grid auto-rows-min gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <flux:card class="space-y-1 cursor-pointer hover:ring-2 hover:ring-zinc-300 dark:hover:ring-zinc-600 transition-all" wire:click="filterByStatus('')">
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Total Reports</flux:text>
            <flux:heading size="xl">{{ $stats['total'] }}</flux:heading>
        </flux:card>

        <flux:card class="space-y-1 cursor-pointer hover:ring-2 hover:ring-amber-300 transition-all {{ $filterStatus === 'pending' ? 'ring-2 ring-amber-500' : '' }}" wire:click="filterByStatus('pending')">
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Pending</flux:text>
            <div class="flex items-center gap-2">
                <flux:heading size="xl">{{ $stats['pending'] }}</flux:heading>
                @if ($stats['pending'] > 0)
                    <flux:badge color="amber" size="sm">{{ $stats['pending'] }}</flux:badge>
                @endif
            </div>
        </flux:card>

        <flux:card class="space-y-1 cursor-pointer hover:ring-2 hover:ring-green-300 transition-all {{ $filterStatus === 'verified' ? 'ring-2 ring-green-500' : '' }}" wire:click="filterByStatus('verified')">
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Verified</flux:text>
            <flux:heading size="xl">{{ $stats['verified'] }}</flux:heading>
        </flux:card>

        <flux:card class="space-y-1 cursor-pointer hover:ring-2 hover:ring-red-300 transition-all {{ $filterStatus === 'rejected' ? 'ring-2 ring-red-500' : '' }}" wire:click="filterByStatus('rejected')">
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Rejected</flux:text>
            <flux:heading size="xl">{{ $stats['rejected'] }}</flux:heading>
        </flux:card>

        <flux:card class="space-y-1">
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Today</flux:text>
            <flux:heading size="xl">{{ $stats['today'] }}</flux:heading>
        </flux:card>
    </div>

    {{-- Incident Breakdown + Quick Date Presets + Export --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-2 text-xs">
            <flux:text class="text-zinc-500 dark:text-zinc-400 whitespace-nowrap">Incidents:</flux:text>
            <flux:badge color="red" size="sm">{{ $stats['poaching'] }} Poaching</flux:badge>
            <flux:badge color="amber" size="sm">{{ $stats['snare'] }} Snare</flux:badge>
            <flux:badge color="blue" size="sm">{{ $stats['injured_animal'] }} Injured</flux:badge>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <flux:button wire:click="setDatePreset('today')" variant="subtle" size="xs">Today</flux:button>
            <flux:button wire:click="setDatePreset('yesterday')" variant="subtle" size="xs">Yesterday</flux:button>
            <flux:button wire:click="setDatePreset('week')" variant="subtle" size="xs">This Week</flux:button>
            <flux:button wire:click="setDatePreset('month')" variant="subtle" size="xs">This Month</flux:button>
            <flux:separator vertical />
            <flux:button wire:click="export" variant="primary" size="xs" icon="arrow-down-tray">
                Export CSV
            </flux:button>
        </div>
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

    {{-- Reports Table (Desktop) --}}
    <flux:card class="flex-1 overflow-hidden hidden md:block" padding="false">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1050px] text-sm">
                <thead class="border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-400">Ref ID</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-400">Reporter Contact</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-400">Incident Type</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-400">Incident Location</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-400">Additional Info</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-400">Status</th>
                        <th class="px-4 py-3 text-center font-medium text-zinc-600 dark:text-zinc-400">Rangers Alerted</th>
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
                            <td class="px-4 py-3 max-w-[200px] truncate text-zinc-600 dark:text-zinc-400" title="{{ $report->additional_info ?? '' }}">
                                {{ $report->additional_info ?? '—' }}
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
                                @if ($report->isRejected() && $report->rejection_reason)
                                    <flux:tooltip content="{{ $report->rejection_reason }}">
                                        <flux:icon name="information-circle" variant="micro" class="ml-1 text-zinc-400 cursor-help" />
                                    </flux:tooltip>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if ($report->rangers_count > 0)
                                    <flux:tooltip content="{{ $report->rangers->pluck('name')->join(', ') }}">
                                        <flux:badge color="indigo" size="sm">
                                            {{ $report->rangers_count }}
                                        </flux:badge>
                                    </flux:tooltip>
                                @else
                                    <flux:text class="text-xs text-zinc-400">—</flux:text>
                                @endif
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
                                            wire:click="startReject({{ $report->id }})"
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
                            <td colspan="9" class="px-4 py-16 text-center">
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

    {{-- Reports Cards (Mobile) --}}
    <div class="flex flex-col gap-4 md:hidden">
        @forelse ($reports as $report)
            <flux:card class="space-y-3" wire:key="report-mobile-{{ $report->id }}">
                <div class="flex items-center justify-between">
                    <flux:text class="font-mono text-xs font-medium">#{{ $report->reference_id }}</flux:text>
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
                </div>

                <div class="flex items-center gap-2">
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
                    <flux:text class="text-xs text-zinc-600 dark:text-zinc-400">{{ $report->location }}</flux:text>
                </div>

                <div class="flex items-center justify-between text-xs text-zinc-500 dark:text-zinc-400">
                    <span>{{ $report->phone_number }}</span>
                    <div class="flex items-center gap-3">
                        @if ($report->rangers_count > 0)
                            <flux:badge color="indigo" size="sm">{{ $report->rangers_count }} rangers</flux:badge>
                        @endif
                        <span>{{ $report->created_at->diffForHumans() }}</span>
                    </div>
                </div>

                @if ($report->isPending())
                    <div class="flex gap-2">
                        <flux:button wire:click="verify({{ $report->id }})" variant="primary" size="xs" class="flex-1">
                            Verify
                        </flux:button>
                        <flux:button wire:click="startReject({{ $report->id }})" variant="danger" size="xs" class="flex-1">
                            Reject
                        </flux:button>
                    </div>
                @endif

                @if ($report->additional_info)
                    <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                        <span class="font-medium">Info:</span> {{ $report->additional_info }}
                    </flux:text>
                @endif
            </flux:card>
        @empty
            <flux:card class="py-16 text-center">
                <div class="space-y-2">
                    <flux:icon name="document-text" class="mx-auto size-8 text-zinc-300 dark:text-zinc-600" />
                    <flux:text class="text-zinc-500 dark:text-zinc-400">No reports found</flux:text>
                </div>
            </flux:card>
        @endforelse

        @if ($reports->hasPages())
            <div class="py-3">
                {{ $reports->links() }}
            </div>
        @endif
    </div>

    {{-- Reject Modal --}}
    <flux:modal wire:model="showRejectModal" class="min-w-[24rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Reject Report</flux:heading>
                <flux:text class="mt-2">
                    Please provide a reason for rejecting this report.
                </flux:text>
            </div>

            <flux:field>
                <flux:label>Reason (optional)</flux:label>
                <flux:textarea
                    wire:model="rejectionReason"
                    rows="3"
                    placeholder="e.g., Duplicate report, insufficient details..."
                />
            </flux:field>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button wire:click="cancelReject" variant="ghost">Cancel</flux:button>
                <flux:button wire:click="confirmReject" variant="danger">Confirm Rejection</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
