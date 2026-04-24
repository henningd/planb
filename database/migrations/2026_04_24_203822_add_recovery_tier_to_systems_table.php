<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('systems', function (Blueprint $table) {
            $table->foreignUuid('emergency_level_id')
                ->nullable()
                ->after('runbook_reference')
                ->constrained('emergency_levels')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('systems', function (Blueprint $table) {
            $table->dropConstrainedForeignId('emergency_level_id');
        });
    }
};
