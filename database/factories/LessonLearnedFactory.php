<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\IncidentReport;
use App\Models\LessonLearned;
use App\Models\ScenarioRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LessonLearned>
 */
class LessonLearnedFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'incident_report_id' => null,
            'scenario_run_id' => null,
            'title' => fake()->sentence(4),
            'root_cause' => fake()->paragraph(),
            'what_went_well' => fake()->paragraph(),
            'what_went_poorly' => fake()->paragraph(),
            'author_user_id' => null,
            'finalized_at' => null,
        ];
    }

    public function forIncident(IncidentReport $report): static
    {
        return $this->state(fn () => [
            'company_id' => $report->company_id,
            'incident_report_id' => $report->id,
            'scenario_run_id' => null,
        ]);
    }

    public function forScenarioRun(ScenarioRun $run): static
    {
        return $this->state(fn () => [
            'company_id' => $run->company_id,
            'incident_report_id' => null,
            'scenario_run_id' => $run->id,
        ]);
    }

    public function finalized(): static
    {
        return $this->state(fn () => ['finalized_at' => now()]);
    }
}
