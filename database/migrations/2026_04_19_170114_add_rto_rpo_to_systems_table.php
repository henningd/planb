<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('systems', function (Blueprint $table) {
            $table->unsignedInteger('rto_minutes')->nullable()->after('system_priority_id');
            $table->unsignedInteger('rpo_minutes')->nullable()->after('rto_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('systems', function (Blueprint $table) {
            $table->dropColumn(['rto_minutes', 'rpo_minutes']);
        });
    }
};
