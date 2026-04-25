<?php

namespace Database\Factories;

use App\Enums\Industry;
use App\Enums\KritisRelevance;
use App\Enums\LegalForm;
use App\Enums\Nis2Classification;
use App\Models\Company;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'name' => fake()->company(),
            'industry' => fake()->randomElement(Industry::cases()),
            'legal_form' => LegalForm::GmbH,
            'kritis_relevant' => KritisRelevance::No,
            'nis2_classification' => Nis2Classification::NotAffected,
            'valid_from' => now()->toDateString(),
            'cyber_insurance_deductible' => '1.500 €',
            'budget_it_lead' => 500.00,
            'budget_emergency_officer' => 2000.00,
            'budget_management' => 20000.00,
            'data_protection_authority_name' => null,
            'data_protection_authority_phone' => null,
            'data_protection_authority_website' => null,
            'employee_count' => fake()->numberBetween(3, 250),
            'locations_count' => fake()->numberBetween(1, 5),
        ];
    }
}
