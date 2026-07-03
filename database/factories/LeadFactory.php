<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Support\Marketing\Nis2QuickCheckCatalog;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $answers = collect(Nis2QuickCheckCatalog::allKeys())
            ->mapWithKeys(fn (string $key) => [$key => fake()->randomElement(array_keys(Nis2QuickCheckCatalog::ANSWER_SCORES))])
            ->all();

        $score = Nis2QuickCheckCatalog::scoreFor($answers);

        return [
            'email' => fake()->unique()->safeEmail(),
            'company_name' => fake()->optional()->company(),
            'contact_name' => fake()->optional()->name(),
            'source' => 'nis2-quick-check',
            'answers' => $answers,
            'score' => $score,
            'readiness' => Nis2QuickCheckCatalog::readinessForScore($score, Nis2QuickCheckCatalog::maxScore()),
            'consent_marketing' => fake()->boolean(),
            'consent_at' => Carbon::now(),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'confirmed_at' => null,
            'report_sent_at' => null,
        ];
    }

    /**
     * Lead mit bestätigter E-Mail-Adresse (Double-Opt-In abgeschlossen).
     */
    public function confirmed(): self
    {
        return $this->state(['confirmed_at' => Carbon::now()]);
    }
}
