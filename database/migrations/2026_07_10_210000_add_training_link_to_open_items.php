<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('open_items') && Schema::hasTable('training_records')
            && ! Schema::hasColumn('open_items', 'training_record_id')) {
            Schema::table('open_items', function (Blueprint $table) {
                $table->foreignUuid('training_record_id')->nullable()
                    ->after('business_process_id')
                    ->constrained('training_records')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('open_items') && Schema::hasColumn('open_items', 'training_record_id')) {
            Schema::table('open_items', function (Blueprint $table) {
                $table->dropConstrainedForeignId('training_record_id');
            });
        }
    }
};
