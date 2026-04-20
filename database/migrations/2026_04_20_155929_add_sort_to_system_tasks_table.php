<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_tasks', function (Blueprint $table) {
            $table->unsignedInteger('sort')->default(0)->after('completed_at');
        });

        // Initialise sort from creation order per system so existing
        // records keep a stable ordering.
        foreach (DB::table('system_tasks')->pluck('system_id')->unique() as $systemId) {
            $i = 0;
            DB::table('system_tasks')
                ->where('system_id', $systemId)
                ->orderBy('created_at')
                ->orderBy('id')
                ->get(['id'])
                ->each(function ($row) use (&$i) {
                    DB::table('system_tasks')->where('id', $row->id)->update(['sort' => $i++]);
                });
        }
    }

    public function down(): void
    {
        Schema::table('system_tasks', function (Blueprint $table) {
            $table->dropColumn('sort');
        });
    }
};
