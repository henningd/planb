<?php

namespace Database\Factories;

use App\Enums\IncidentType;
use App\Models\Company;
use App\Models\IncidentReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IncidentReport>
 */
class IncidentReportFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'scenario_run_id' => null,
            'title' => fake()->sentence(3),
            'type' => IncidentType::CyberAttack,
            'occurred_at' => now(),
            'notes' => null,
        ];
    }
}
