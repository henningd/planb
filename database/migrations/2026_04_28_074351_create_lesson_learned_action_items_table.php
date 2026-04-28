<?php

use App\Enums\LessonLearnedActionItemStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_learned_action_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('lesson_learned_id')->constrained('lessons_learned')->cascadeOnDelete();
            $table->string('description');
            $table->foreignUuid('responsible_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->date('due_date')->nullable();
            $table->string('status')->default(LessonLearnedActionItemStatus::Open->value);
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['lesson_learned_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_learned_action_items');
    }
};
