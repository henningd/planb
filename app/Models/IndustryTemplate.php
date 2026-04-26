<?php

namespace App\Models;

use App\Enums\Industry;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'industry', 'description', 'payload', 'is_active', 'sort'])]
class IndustryTemplate extends Model
{
    use HasUuids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'industry' => Industry::class,
            'is_active' => 'boolean',
            'sort' => 'integer',
            'payload' => 'array',
        ];
    }

    /**
     * Anzahl Datensätze, die das Template insgesamt enthält
     * (alle Bereiche + nested zusammen). Hilft im UI bei der Einschätzung.
     */
    public function payloadCount(): int
    {
        $areas = $this->payload['areas'] ?? [];
        $count = 0;
        foreach ($areas as $rows) {
            if (is_array($rows)) {
                $count += count($rows);
            }
        }

        return $count;
    }
}
