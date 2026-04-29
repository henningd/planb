<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\FallbackProcess;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FallbackProcess>
 */
class FallbackProcessFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'trigger' => null,
            'responsible_role_id' => null,
            'responsible_employee_id' => null,
            'max_duration_hours' => null,
            'handover_notes' => null,
            'priority' => 2,
            'notes' => null,
            'sort' => 0,
        ];
    }
}
