<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\HandbookVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HandbookVersion>
 */
class HandbookVersionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'version' => '1.0',
            'changed_at' => now()->toDateString(),
            'changed_by_employee_id' => null,
            'change_reason' => 'Erstversion',
            'approved_at' => null,
            'approved_by_employee_id' => null,
            'approved_by_name' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'approved_at' => now()->toDateString(),
            'approved_by_name' => 'Geschäftsführung',
        ]);
    }
}
