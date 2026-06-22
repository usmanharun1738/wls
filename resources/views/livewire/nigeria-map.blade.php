<div class="flex flex-col lg:flex-row gap-6">
    {{-- Map --}}
    <flux:card class="flex-1 min-w-0" padding="false">
        <div class="p-4 border-b border-zinc-200 dark:border-zinc-700">
            <flux:heading size="base">Ranger Coverage Map</flux:heading>
            <flux:text class="text-xs text-zinc-500 mt-1">11 rangers across Kaduna State</flux:text>
        </div>
        <div class="relative p-2">
            {{-- Inline the SVG with modifications --}}
            <div id="wls-nigeria-map" class="w-full">
                {!! file_get_contents(public_path('ng.svg')) !!}
            </div>
        </div>
        {{-- Legend --}}
        <div class="flex items-center gap-6 px-4 py-3 border-t border-zinc-200 dark:border-zinc-700 text-xs text-zinc-500">
            <div class="flex items-center gap-1.5">
                <span class="size-3 rounded-full bg-green-500 inline-block"></span>
                Ranger base
            </div>
            <div class="flex items-center gap-1.5">
                <span class="size-3 rounded-sm bg-green-700 inline-block"></span>
                Kaduna State (coverage zone)
            </div>
        </div>
    </flux:card>

    {{-- Ranger Coverage Stats --}}
    <div class="w-full lg:w-72 flex flex-col gap-4">
        <flux:card class="space-y-1">
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Rangers Deployed</flux:text>
            <flux:heading size="xl">{{ $rangerCount ?? 11 }}</flux:heading>
        </flux:card>

        <flux:card class="space-y-2">
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Active Bases</flux:text>
            @foreach ($rangerBases ?? [] as $base)
                <div class="flex items-center justify-between text-xs">
                    <span class="truncate mr-2">{{ $base['name'] }}</span>
                    <flux:badge color="indigo" size="sm">{{ $base['count'] }}</flux:badge>
                </div>
            @endforeach
        </flux:card>
    </div>
</div>

<style>
/* Highlight Kaduna state */
#wls-nigeria-map #NGKD {
    fill: #16a34a;
    stroke: #ffffff;
    stroke-width: 0.8;
}
#wls-nigeria-map #NGKD:hover {
    fill: #15803d;
    cursor: pointer;
}

/* Dim other states */
#wls-nigeria-map #features path:not(#NGKD) {
    fill: #e5e7eb;
    stroke: #d1d5db;
}

@media (prefers-color-scheme: dark) {
    #wls-nigeria-map #features path:not(#NGKD) {
        fill: #374151;
        stroke: #4b5563;
    }
}

/* Ranger dots */
#wls-nigeria-map .ranger-dot {
    fill: #22c55e;
    stroke: #ffffff;
    stroke-width: 1.5;
    cursor: pointer;
    transition: r 0.2s;
}
#wls-nigeria-map .ranger-dot:hover {
    r: 6;
}
#wls-nigeria-map .ranger-label {
    font-size: 9px;
    fill: #374151;
    font-family: system-ui, sans-serif;
}
@media (prefers-color-scheme: dark) {
    #wls-nigeria-map .ranger-label {
        fill: #d1d5db;
    }
}

/* Scale SVG properly */
#wls-nigeria-map svg {
    width: 100%;
    height: auto;
}
</style>

{{-- Inject ranger dots via JS --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const svg = document.querySelector('#wls-nigeria-map svg');
    if (!svg) return;

    const rangerPositions = {!! json_encode($rangerPositions ?? []) !!};

    rangerPositions.forEach(function(r) {
        // Create dot
        const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        circle.setAttribute('cx', r.x);
        circle.setAttribute('cy', r.y);
        circle.setAttribute('r', '4');
        circle.setAttribute('class', 'ranger-dot');
        circle.setAttribute('data-name', r.name);
        circle.setAttribute('data-location', r.location);

        // Tooltip title
        const title = document.createElementNS('http://www.w3.org/2000/svg', 'title');
        title.textContent = r.name + ' — ' + r.location;
        circle.appendChild(title);

        svg.appendChild(circle);

        // Create label
        if (r.label) {
            const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            text.setAttribute('x', r.x + 8);
            text.setAttribute('y', r.y + 3);
            text.setAttribute('class', 'ranger-label');
            text.textContent = r.label;
            svg.appendChild(text);
        }
    });
});
</script>
