<x-layouts::app.sidebar :title="$title ?? null">
    @auth
        @if (config('features.incident_mode'))
            <livewire:incident-mode-banner />
        @endif
        {{-- Firmenweiter Echtzeit-Alarm bei ausgelöstem Notfall (nur mit Firmenprofil). --}}
        @if (auth()->user()?->currentCompany())
            <livewire:incident-alert />
        @endif
    @endauth
    <flux:main>
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
