<?php

use App\Enums\BcmPolicyStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bcm_policies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained()->cascadeOnDelete();
            $table->text('scope')->nullable();
            $table->longText('content')->nullable();
            $table->string('version')->default('1.0');
            $table->string('status')->default(BcmPolicyStatus::Draft->value);
            $table->string('approved_by')->nullable();
            $table->date('approved_at')->nullable();
            $table->date('review_due_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bcm_policies');
    }
};
