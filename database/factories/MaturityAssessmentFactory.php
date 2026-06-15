<?php

namespace Database\Factories;

use App\Enums\BcmsStage;
use App\Models\MaturityAssessment;
use App\Support\Bcms\MaturityCatalog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaturityAssessment>
 */
class MaturityAssessmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $answers = collect(MaturityCatalog::allKeys())
            ->mapWithKeys(fn (string $key) => [$key => fake()->randomElement(array_keys(MaturityCatalog::ANSWER_SCORES))])
            ->all();

        $score = collect($answers)->sum(fn (string $answer) => MaturityCatalog::ANSWER_SCORES[$answer]);
        $max = MaturityCatalog::maxScore();

        return [
            'answers' => $answers,
            'score' => $score,
            'stage' => MaturityCatalog::stageForScore($score, $max),
            'assessed_at' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Vollständig positiv beantwortetes Assessment (Standard-BCMS).
     */
    public function standard(): self
    {
        return $this->allAnswers('yes');
    }

    /**
     * Durchgängig negativ beantwortetes Assessment (Reaktiv-BCMS).
     */
    public function reaktiv(): self
    {
        return $this->allAnswers('no');
    }

    /**
     * Alle Fragen mit derselben Antwort belegen und Punktzahl/Stufe ableiten.
     */
    protected function allAnswers(string $answer): self
    {
        $answers = collect(MaturityCatalog::allKeys())
            ->mapWithKeys(fn (string $key) => [$key => $answer])
            ->all();

        $score = collect($answers)->sum(fn (string $value) => MaturityCatalog::ANSWER_SCORES[$value]);

        return $this->state([
            'answers' => $answers,
            'score' => $score,
            'stage' => MaturityCatalog::stageForScore($score, MaturityCatalog::maxScore()),
        ]);
    }

    public function stage(BcmsStage $stage): self
    {
        return $this->state(['stage' => $stage]);
    }
}
