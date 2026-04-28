<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('display_name')->nullable()->after('name');
            $table->string('logo_path')->nullable()->after('display_name');
            $table->string('primary_color', 7)->nullable()->after('logo_path');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['display_name', 'logo_path', 'primary_color']);
        });
    }
};
