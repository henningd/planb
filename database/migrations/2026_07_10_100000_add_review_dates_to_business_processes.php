<?php

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
        Schema::table('business_processes', function (Blueprint $table) {
            if (! Schema::hasColumn('business_processes', 'last_reviewed_at')) {
                $table->date('last_reviewed_at')->nullable()->after('fallback_process');
            }
            if (! Schema::hasColumn('business_processes', 'next_review_at')) {
                $table->date('next_review_at')->nullable()->after('last_reviewed_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_processes', function (Blueprint $table) {
            $table->dropColumn(['last_reviewed_at', 'next_review_at']);
        });
    }
};
