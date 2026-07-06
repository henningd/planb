<?php

namespace App\Console\Commands;

use App\Jobs\GeocodeLocation;
use App\Models\Location;
use App\Scopes\CurrentCompanyScope;
use App\Services\Geocoding\NominatimGeocoder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Sleep;

/**
 * Bestands-Geocoding: ermittelt Koordinaten für alle Standorte ohne lat/lng
 * über Nominatim. Drosselt auf 1 Request/Sekunde (Nominatim Usage Policy).
 * Neue/geänderte Adressen werden laufend über {@see GeocodeLocation}
 * versorgt — dieses Command holt den Altbestand nach.
 */
#[Signature('planb:geocode-locations')]
#[Description('Ermittelt Koordinaten (Nominatim) für alle Standorte ohne lat/lng — gedrosselt auf 1 Request/Sekunde.')]
class GeocodeLocations extends Command
{
    public function handle(NominatimGeocoder $geocoder): int
    {
        $locations = Location::query()
            ->withoutGlobalScope(CurrentCompanyScope::class)
            ->whereNull('lat')
            ->orderBy('created_at')
            ->get();

        if ($locations->isEmpty()) {
            $this->info('Alle Standorte haben bereits Koordinaten.');

            return self::SUCCESS;
        }

        $resolved = 0;

        foreach ($locations as $index => $location) {
            if ($index > 0) {
                // Nominatim-Policy: höchstens 1 Request pro Sekunde.
                Sleep::for(1)->second();
            }

            $result = $geocoder->geocode($location->geocodingQuery());

            if ($result === null) {
                $this->warn(sprintf('Kein Treffer: %s (%s)', $location->name, $location->geocodingQuery()));

                continue;
            }

            $location->forceFill([
                'lat' => $result['lat'],
                'lng' => $result['lng'],
            ])->saveQuietly();

            $resolved++;
            $this->line(sprintf('%s → %s, %s', $location->name, $result['lat'], $result['lng']));
        }

        $this->info(sprintf('%d von %d Standorten geokodiert.', $resolved, $locations->count()));

        return self::SUCCESS;
    }
}
