<?php

namespace Database\Factories;

use App\Enums\AiRiskClass;
use App\Enums\AiSystemRole;
use App\Models\AiSystem;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiSystem>
 */
class AiSystemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->words(2, true),
            'purpose' => fake()->sentence(),
            'provider_name' => fake()->company(),
            'role' => AiSystemRole::Deployer,
            'risk_class' => fake()->randomElement(AiRiskClass::cases()),
            'sort' => 0,
        ];
    }

    public function highRisk(): static
    {
        return $this->state(fn () => ['risk_class' => AiRiskClass::High]);
    }
}
