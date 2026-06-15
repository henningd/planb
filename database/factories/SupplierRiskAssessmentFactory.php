<?php

namespace Database\Factories;

use App\Enums\PreventiveMeasureInterval;
use App\Enums\SecurityAssessmentStatus;
use App\Enums\SupplierCriticality;
use App\Models\ServiceProvider;
use App\Models\SupplierRiskAssessment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupplierRiskAssessment>
 */
class SupplierRiskAssessmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'criticality' => fake()->randomElement(SupplierCriticality::cases()),
            'security_status' => SecurityAssessmentStatus::NotAssessed,
            'last_assessed_at' => null,
            'interval' => null,
            'next_assessment_at' => null,
            'alternative_provider' => fake()->optional()->company(),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function forProvider(ServiceProvider $provider): self
    {
        return $this->state([
            'service_provider_id' => $provider->id,
            'company_id' => $provider->company_id,
        ]);
    }

    /**
     * Wiederkehrende Bewertung mit Intervall und fälliger Wiederbewertung in der Vergangenheit.
     */
    public function overdue(): self
    {
        return $this->state([
            'interval' => PreventiveMeasureInterval::Yearly,
            'last_assessed_at' => now()->subMonths(13)->format('Y-m-d'),
            'next_assessment_at' => now()->subMonth()->format('Y-m-d'),
        ]);
    }
}
