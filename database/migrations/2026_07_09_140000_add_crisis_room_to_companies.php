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
        if (Schema::hasColumn('companies', 'crisis_room_primary')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            $table->string('crisis_room_primary')->nullable()->after('budget_management');
            $table->string('crisis_room_secondary')->nullable()->after('crisis_room_primary');
            $table->string('crisis_room_digital_link')->nullable()->after('crisis_room_secondary');
            $table->json('crisis_room_equipment')->nullable()->after('crisis_room_digital_link');
            $table->text('crisis_room_access')->nullable()->after('crisis_room_equipment');
            $table->text('crisis_room_preparation')->nullable()->after('crisis_room_access');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'crisis_room_primary',
                'crisis_room_secondary',
                'crisis_room_digital_link',
                'crisis_room_equipment',
                'crisis_room_access',
                'crisis_room_preparation',
            ]);
        });
    }
};
