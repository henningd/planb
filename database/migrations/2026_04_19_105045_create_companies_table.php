<?php

use App\Enums\Industry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();
            $table->string('name');
            $table->string('industry')->default(Industry::Sonstiges->value);
            $table->unsignedInteger('employee_count')->nullable();
            $table->unsignedInteger('locations_count')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
