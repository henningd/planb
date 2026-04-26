<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\HandbookVersion;
use App\Models\HandbookVersionAcknowledgement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HandbookVersionAcknowledgement>
 */
class HandbookVersionAcknowledgementFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'handbook_version_id' => HandbookVersion::factory(),
            'employee_id' => Employee::factory(),
            'acknowledged_at' => now(),
            'notes' => null,
        ];
    }
}
