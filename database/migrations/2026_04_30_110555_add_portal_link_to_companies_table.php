<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('portal_api_token_hash', 64)->nullable()->unique()->after('id');
            $table->timestamp('portal_link_generated_at')->nullable()->after('portal_api_token_hash');
            $table->timestamp('portal_link_last_used_at')->nullable()->after('portal_link_generated_at');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropUnique(['portal_api_token_hash']);
            $table->dropColumn(['portal_api_token_hash', 'portal_link_generated_at', 'portal_link_last_used_at']);
        });
    }
};
