<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_access_codes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('company_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('code_hash');
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->foreignUuid('api_token_id')->nullable()->constrained('api_tokens')->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index('email');
            $table->index(['company_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_access_codes');
    }
};
