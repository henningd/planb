<x-layouts::app.sidebar :title="$title ?? null">
    @auth
        @if (config('features.incident_mode'))
            <livewire:incident-mode-banner />
        @endif
    @endauth
    <flux:main>
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
