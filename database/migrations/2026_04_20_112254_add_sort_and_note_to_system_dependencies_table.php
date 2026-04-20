<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_dependencies', function (Blueprint $table) {
            $table->unsignedInteger('sort')->default(0);
            $table->string('note', 500)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('system_dependencies', function (Blueprint $table) {
            $table->dropColumn(['sort', 'note']);
        });
    }
};
