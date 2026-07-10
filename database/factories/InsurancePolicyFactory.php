<?php

namespace Database\Factories;

use App\Enums\InsuranceType;
use App\Models\Company;
use App\Models\InsurancePolicy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InsurancePolicy>
 */
class InsurancePolicyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'type' => fake()->randomElement(InsuranceType::cases()),
            'insurer' => fake()->company(),
            'policy_number' => fake()->bothify('POL-#######'),
            'hotline' => '0800 '.fake()->numerify('#######'),
            'email' => 'schaden@'.fake()->domainName(),
            'reporting_window' => fake()->randomElement(['unverzüglich', '24 Stunden', '72 Stunden']),
            'deductible' => fake()->randomElement(['1.000 €', '2.500 €', '5.000 €']),
            'coverage_amount' => fake()->randomElement(['1 Mio €', '5 Mio €', '10 Mio €']),
            'contact_name' => fake()->name(),
            'approval_required' => false,
        ];
    }
}
