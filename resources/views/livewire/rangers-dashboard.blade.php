<div>
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

    {{-- Search & Filters --}}
    <flux:card class="space-y-4">
        <div class="flex flex-wrap items-end gap-4">
            <div class="w-full sm:flex-1">
                <flux:input
                    wire:model.live="search"
                    placeholder="Search by name, location, or phone..."
                    icon="magnifying-glass"
                    label="Search"
                />
            </div>

            <div class="w-full sm:w-auto sm:min-w-[140px]">
                <flux:select wire:model.live="filterStatus" label="Status">
                    <option value="">All Rangers</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </flux:select>
            </div>

            <div class="flex items-end gap-2">
                <flux:button wire:click="openCreate" variant="primary" icon="plus">
                    Add Ranger
                </flux:button>
            </div>
            </div>
        </div>
    </flux:card>

    {{-- Rangers Table --}}
    <flux:card class="flex-1 overflow-hidden" padding="false">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[950px] text-sm">
                <thead class="border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-400">Name</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-400">Phone</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-400">Email</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-400">Base Location</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-400">Status</th>
                        <th class="px-4 py-3 text-center font-medium text-zinc-600 dark:text-zinc-400">Total Alerts</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-400">Last Alerted</th>
                        <th class="px-4 py-3 text-right font-medium text-zinc-600 dark:text-zinc-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($rangers as $ranger)
                        <tr wire:key="ranger-{{ $ranger->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/30 transition-colors">
                            <td class="px-4 py-3 font-medium">
                                <button wire:click="openEdit({{ $ranger->id }})" class="text-blue-600 dark:text-blue-400 hover:underline cursor-pointer text-left">
                                    {{ $ranger->name }}
                                </button>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs">
                                <a href="tel:{{ $ranger->phone_number }}" class="text-blue-600 dark:text-blue-400 hover:underline">
                                    {{ $ranger->phone_number }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-xs text-zinc-600 dark:text-zinc-400">
                                {{ $ranger->email ?? '—' }}
                            </td>
                            <td class="px-4 py-3 max-w-[180px] truncate text-zinc-600 dark:text-zinc-400" title="{{ $ranger->base_location }}">
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
                            <td class="px-4 py-3 text-center">
                                @if ($ranger->reports_count > 0)
                                    <flux:badge color="indigo" size="sm">
                                        {{ $ranger->reports_count }}
                                    </flux:badge>
                                @else
                                    <flux:text class="text-xs text-zinc-400">—</flux:text>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-zinc-500 dark:text-zinc-400 whitespace-nowrap">
                                @if ($ranger->last_alerted_at)
                                    {{ \Illuminate\Support\Carbon::parse($ranger->last_alerted_at)->diffForHumans() }}
                                @else
                                    <flux:text class="text-xs text-zinc-400">Never</flux:text>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end items-center gap-1">
                                    <flux:button
                                        wire:click="toggleActive({{ $ranger->id }})"
                                        :variant="$ranger->is_active ? 'danger' : 'primary'"
                                        size="xs"
                                    >
                                        {{ $ranger->is_active ? 'Deactivate' : 'Activate' }}
                                    </flux:button>
                                    <flux:button
                                        wire:click="openDelete({{ $ranger->id }})"
                                        variant="ghost"
                                        size="xs"
                                        icon="trash"
                                    />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-16 text-center">
                                <div class="space-y-2">
                                    <flux:icon name="users" class="mx-auto size-8 text-zinc-300 dark:text-zinc-600" />
                                    <flux:text class="text-zinc-500 dark:text-zinc-400">
                                        No rangers found matching your search.
                                    </flux:text>
                                    @if ($search || $filterStatus)
                                        <flux:button wire:click="$set('search', ''); $set('filterStatus', '')" variant="subtle" size="sm">
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

        <div class="border-t border-zinc-200 dark:border-zinc-700 px-4 py-3">
            <flux:text class="text-xs text-zinc-400">
                {{ $stats['active'] }} active rangers receive location-matched SMS alerts.
            </flux:text>
        </div>
    </flux:card>

    {{-- Create Modal --}}
    <flux:modal wire:model.self="showCreateModal" class="min-w-[28rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Add Ranger</flux:heading>
                <flux:text class="mt-2">Register a new ranger in the system.</flux:text>
            </div>

            <div class="space-y-4">
                <flux:input wire:model="editName" label="Full Name" placeholder="Ibrahim Musa" required />
                <flux:input wire:model="editPhone" label="Phone Number" placeholder="+2347012345678" required />
                <flux:input wire:model="editEmail" label="Email" placeholder="ranger@wildlife.gov.ng" />
                <flux:input wire:model="editLocation" label="Base Location" placeholder="Kamuku National Park HQ" />
                <flux:input wire:model="editPin" label="PIN" maxlength="4" placeholder="0000" required />
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button wire:click="$set('showCreateModal', false)" variant="ghost">Cancel</flux:button>
                <flux:button wire:click="create" variant="primary">Create Ranger</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Edit Modal --}}
    <flux:modal wire:model.self="showEditModal" class="min-w-[28rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Edit Ranger</flux:heading>
                <flux:text class="mt-2">Update ranger information.</flux:text>
            </div>

            <div class="space-y-4">
                <flux:input wire:model="editName" label="Full Name" placeholder="Ibrahim Musa" required />
                <flux:input wire:model="editPhone" label="Phone Number" placeholder="+2347012345678" required />
                <flux:input wire:model="editEmail" label="Email" placeholder="ranger@wildlife.gov.ng" />
                <flux:input wire:model="editLocation" label="Base Location" placeholder="Kamuku National Park HQ" />
                <flux:input wire:model="editPin" label="PIN" maxlength="4" placeholder="0000" required />
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button wire:click="$set('showEditModal', false)" variant="ghost">Cancel</flux:button>
                <flux:button wire:click="update" variant="primary">Save Changes</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Delete Confirmation Modal --}}
    <flux:modal wire:model.self="showDeleteModal" class="min-w-[22rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Remove Ranger</flux:heading>
                <flux:text class="mt-2">
                    Are you sure you want to remove <strong>{{ $editName }}</strong>? This action cannot be undone.
                </flux:text>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button wire:click="$set('showDeleteModal', false)" variant="ghost">
                    Cancel
                </flux:button>
                <flux:button wire:click="delete" variant="danger">
                    Remove Ranger
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
</div>
