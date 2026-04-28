<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Idempotenz: ein vorheriger Lauf hat ggf. die Tabelle erzeugt, ist
        // aber bei einem späteren Schritt gescheitert. Tabelle ist in dem
        // Fall leer (oder enthält Demo-Daten, die ohnehin neu geseedet
        // werden) — bewusst verworfen.
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'department_id')) {
                $table->dropForeign(['department_id']);
                $table->dropColumn('department_id');
            }
        });
        Schema::dropIfExists('departments');

        Schema::create('departments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->unique(['company_id', 'name']);
            $table->index(['company_id', 'sort']);
        });

        // Department-FK auf employees ergänzen, anschließend bestehende
        // Department-Strings in die neue Tabelle überführen.
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignUuid('department_id')->nullable()->after('department')->constrained()->nullOnDelete();
        });

        DB::table('employees')
            ->whereNotNull('department')
            ->where('department', '!=', '')
            ->orderBy('id')
            ->chunk(500, function ($rows) {
                foreach ($rows as $row) {
                    $name = trim((string) $row->department);
                    if ($name === '') {
                        continue;
                    }

                    $deptId = DB::table('departments')
                        ->where('company_id', $row->company_id)
                        ->where('name', $name)
                        ->value('id');

                    if ($deptId === null) {
                        $deptId = (string) Str::uuid();
                        DB::table('departments')->insert([
                            'id' => $deptId,
                            'company_id' => $row->company_id,
                            'name' => $name,
                            'description' => null,
                            'sort' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    DB::table('employees')->where('id', $row->id)->update(['department_id' => $deptId]);
                }
            });

        Schema::table('employees', function (Blueprint $table) {
            // Index, der die Department-String-Spalte referenziert, muss vor
            // dem dropColumn entfernt werden — SQLite ist darin streng.
            $table->dropIndex('employees_company_id_department_index');
            $table->dropColumn('department');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->index(['company_id', 'department_id']);
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('department')->nullable()->after('position');
        });

        DB::table('employees')
            ->whereNotNull('department_id')
            ->orderBy('id')
            ->chunk(500, function ($rows) {
                foreach ($rows as $row) {
                    $name = DB::table('departments')->where('id', $row->department_id)->value('name');
                    if ($name !== null) {
                        DB::table('employees')->where('id', $row->id)->update(['department' => $name]);
                    }
                }
            });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn('department_id');
        });

        Schema::dropIfExists('departments');
    }
};
