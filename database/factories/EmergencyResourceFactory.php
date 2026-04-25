<?php

namespace Database\Factories;

use App\Enums\EmergencyResourceType;
use App\Models\Company;
use App\Models\EmergencyResource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmergencyResource>
 */
class EmergencyResourceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'type' => EmergencyResourceType::Other,
            'name' => null,
            'description' => null,
            'location' => 'Hauptsitz',
            'access_holders' => null,
            'last_check_at' => null,
            'next_check_at' => null,
            'notes' => null,
            'sort' => 0,
        ];
    }

    public function ofType(EmergencyResourceType $type): static
    {
        return $this->state(fn () => ['type' => $type]);
    }
}
