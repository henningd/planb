<?php

namespace App\Jobs;

use App\Models\Location;
use App\Scopes\CurrentCompanyScope;
use App\Services\Geocoding\NominatimGeocoder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Ermittelt die Koordinaten eines Standorts asynchron über Nominatim, damit
 * das Speichern der Adresse im UI nicht auf den externen HTTP-Call warten
 * muss. Wird beim Anlegen bzw. bei Adressänderungen von der Standorte-Seite
 * dispatcht. Kein Treffer/Fehler → Koordinaten bleiben unverändert (null).
 */
class GeocodeLocation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly string $locationId) {}

    public function handle(NominatimGeocoder $geocoder): void
    {
        $location = Location::query()
            ->withoutGlobalScope(CurrentCompanyScope::class)
            ->find($this->locationId);

        if ($location === null) {
            return;
        }

        $result = $geocoder->geocode($location->geocodingQuery());

        if ($result === null) {
            return;
        }

        // saveQuietly: kein Audit-Log-Eintrag und keine Model-Events für die
        // rein technische Koordinaten-Ergänzung.
        $location->forceFill([
            'lat' => $result['lat'],
            'lng' => $result['lng'],
        ])->saveQuietly();
    }
}
