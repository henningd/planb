<?php

use App\Models\Location;
use App\Support\PhoneFormat;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Standort')] class extends Component {
    public Location $location;

    public function mount(Location $location): void
    {
        abort_if($location->company_id !== Auth::user()->currentCompany()?->id, 403);

        $this->location = $location;
    }
}; ?>

@php
    $hasCoords = $location->hasCoordinates();
    if ($hasCoords) {
        // '.' als Dezimaltrenner erzwingen (locale-unabhängig) für die URLs.
        $lat = sprintf('%.6f', $location->lat);
        $lng = sprintf('%.6f', $location->lng);
        $delta = 0.008; // ~800 m Kartenausschnitt um den Marker
        $bbox = implode(',', [
            sprintf('%.6f', $location->lng - $delta),
            sprintf('%.6f', $location->lat - $delta),
            sprintf('%.6f', $location->lng + $delta),
            sprintf('%.6f', $location->lat + $delta),
        ]);
        $embedUrl = 'https://www.openstreetmap.org/export/embed.html?bbox='.rawurlencode($bbox).'&layer=mapnik&marker='.rawurlencode($lat.','.$lng);
        $externalUrl = 'https://www.openstreetmap.org/?mlat='.$lat.'&mlon='.$lng.'#map=17/'.$lat.'/'.$lng;
    }
@endphp

<section class="mx-auto w-full max-w-3xl">
    <div class="mb-2">
        <flux:link :href="route('locations.index')" wire:navigate class="text-sm">
            ← {{ __('Alle Standorte') }}
        </flux:link>
    </div>

    <div class="mb-6 flex items-start justify-between gap-4">
        <div class="min-w-0">
            <flux:heading size="xl">{{ $location->name }}</flux:heading>
            @if ($location->is_headquarters)
                <flux:badge color="sky" size="sm" class="mt-2">{{ __('Hauptsitz') }}</flux:badge>
            @endif
        </div>
        <flux:button size="sm" variant="filled" icon="qr-code" :href="route('locations.aushang', ['location' => $location->id])" target="_blank">
            {{ __('Notfallaushang') }}
        </flux:button>
    </div>

    <div class="space-y-6">
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="base" class="mb-3">{{ __('Adresse & Kontakt') }}</flux:heading>
            <div class="space-y-2 text-sm">
                <div class="flex items-start gap-2 text-zinc-700 dark:text-zinc-200">
                    <flux:icon.map-pin class="mt-0.5 h-4 w-4 shrink-0 text-zinc-400" />
                    <span>{{ $location->street }}<br>{{ $location->postal_code }} {{ $location->city }}<br>{{ $location->country }}</span>
                </div>
                @if ($location->phone)
                    <div class="flex items-center gap-2">
                        <flux:icon.phone class="h-4 w-4 shrink-0 text-zinc-400" />
                        <a href="tel:{{ PhoneFormat::tel($location->phone) }}" class="hover:underline">{{ PhoneFormat::display($location->phone) }}</a>
                    </div>
                @endif
                @if ($hasCoords)
                    <div class="flex items-center gap-2 text-zinc-500 dark:text-zinc-400">
                        <flux:icon.globe-alt class="h-4 w-4 shrink-0 text-zinc-400" />
                        <span>{{ number_format($location->lat, 5, ',', '') }}, {{ number_format($location->lng, 5, ',', '') }}</span>
                    </div>
                @endif
            </div>
        </div>

        @if ($location->building_areas)
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="base" class="mb-2">{{ __('Gebäude / Bereiche / Etagen') }}</flux:heading>
                <flux:text class="whitespace-pre-line text-sm text-zinc-700 dark:text-zinc-200">{{ $location->building_areas }}</flux:text>
            </div>
        @endif

        @if ($location->notes)
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="base" class="mb-2">{{ __('Notizen') }}</flux:heading>
                <flux:text class="whitespace-pre-line text-sm text-zinc-700 dark:text-zinc-200">{{ $location->notes }}</flux:text>
            </div>
        @endif

        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mb-3 flex items-center justify-between gap-2">
                <flux:heading size="base">{{ __('Karte') }}</flux:heading>
                @if ($hasCoords)
                    <flux:link href="{{ $externalUrl }}" target="_blank" rel="noopener noreferrer" class="text-sm">
                        {{ __('Größere Karte öffnen') }} →
                    </flux:link>
                @endif
            </div>

            @if ($hasCoords)
                <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                    <iframe
                        title="{{ __('Karte für') }} {{ $location->name }}"
                        src="{{ $embedUrl }}"
                        class="block h-80 w-full"
                        loading="lazy"
                        referrerpolicy="no-referrer"
                    ></iframe>
                </div>
                <flux:text class="mt-2 text-xs text-zinc-400 dark:text-zinc-500">{{ __('Kartendaten © OpenStreetMap-Mitwirkende') }}</flux:text>
            @else
                <div class="rounded-lg border border-dashed border-zinc-300 px-4 py-8 text-center dark:border-zinc-700">
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('Für diesen Standort sind noch keine Koordinaten hinterlegt. Sie werden aus der Adresse automatisch ermittelt — oder in der Standort-Übersicht über „Koordinaten neu ermitteln“ angestoßen.') }}
                    </flux:text>
                </div>
            @endif
        </div>
    </div>
</section>
