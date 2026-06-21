<div class="w-full max-w-md mx-auto">
    <flux:card class="space-y-6">
        <div>
            <flux:heading size="lg">Ranger Login</flux:heading>
            <flux:text class="mt-2">Enter your phone number and PIN to view your alerts.</flux:text>
        </div>

        <form wire:submit="login" class="space-y-6">
            <flux:input
                wire:model="phoneNumber"
                label="Phone Number"
                type="tel"
                placeholder="+2347012345678"
                required
                autofocus
            />

            <flux:input
                wire:model="pin"
                label="PIN"
                type="password"
                placeholder="0000"
                maxlength="4"
                required
                autocomplete="off"
            />

            <flux:button type="submit" variant="primary" class="w-full">
                Sign In
            </flux:button>
        </form>

        <flux:text class="text-xs text-zinc-400 text-center">
            Default PIN is 0000. Contact admin to change it.
        </flux:text>
    </flux:card>
</div>
