<div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
    {{-- Stats Cards --}}
    <div class="grid auto-rows-min gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <flux:card class="space-y-1">
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Total Rangers</flux:text>
            <flux:heading size="xl">{{ $stats['total'] }}</flux:heading>
        </flux:card>

        <flux:card class="space-y-1">
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Active</flux:text>
            <div class="flex items-center gap-2">
                <flux:heading size="xl">{{ $stats['active'] }}</flux:heading>
                <flux:badge color="green" size="sm">{{ $stats['active'] }}</flux:badge>
            </div>
        </flux:card>

        <flux:card class="space-y-1">
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Inactive</flux:text>
            <flux:heading size="xl">{{ $stats['inactive'] }}</flux:heading>
        </flux:card>

        <flux:card class="space-y-1">
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Total Reports</flux:text>
            <flux:heading size="xl">{{ $stats['totalReports'] }}</flux:heading>
        </flux:card>

        <flux:card class="space-y-1">
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Reports Today</flux:text>
            <flux:heading size="xl">{{ $stats['reportsToday'] }}</flux:heading>
        </flux:card>
    </div>

    {{-- Rangers Table --}}
    <flux:card class="flex-1 overflow-hidden" padding="false">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[800px] text-sm">
                <thead class="border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-400">Name</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-400">Phone</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-400">Email</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-400">Base Location</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-400">Status</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-400">Pending Alerts</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($rangers as $ranger)
                        <tr wire:key="ranger-{{ $ranger->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/30 transition-colors">
                            <td class="px-4 py-3 font-medium">
                                {{ $ranger->name }}
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-zinc-600 dark:text-zinc-400">
                                {{ $ranger->phone_number }}
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                {{ $ranger->email ?? '—' }}
                            </td>
                            <td class="px-4 py-3 max-w-[200px] truncate text-zinc-600 dark:text-zinc-400" title="{{ $ranger->base_location }}">
                                {{ $ranger->base_location ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <flux:badge
                                    :color="$ranger->is_active ? 'green' : 'zinc'"
                                    size="sm"
                                >
                                    {{ $ranger->is_active ? 'Active' : 'Inactive' }}
                                </flux:badge>
                            </td>
                            <td class="px-4 py-3">
                                @if ($ranger->reports_count > 0)
                                    <flux:badge color="amber" size="sm">
                                        {{ $ranger->reports_count }}
                                    </flux:badge>
                                @else
                                    <flux:text class="text-xs text-zinc-400">—</flux:text>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-16 text-center">
                                <div class="space-y-2">
                                    <flux:icon name="users" class="mx-auto size-8 text-zinc-300 dark:text-zinc-600" />
                                    <flux:text class="text-zinc-500 dark:text-zinc-400">No rangers registered yet.</flux:text>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if (count($rangers) > 0)
            <div class="border-t border-zinc-200 dark:border-zinc-700 px-4 py-3">
                <flux:text class="text-xs text-zinc-400">
                    {{ $stats['active'] }} active rangers receive SMS alerts for every new report.
                </flux:text>
            </div>
        @endif
    </flux:card>
</div>
