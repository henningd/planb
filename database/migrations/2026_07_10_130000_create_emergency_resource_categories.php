<?php

use App\Enums\EmergencyResourceType;
use App\Models\Company;
use App\Models\EmergencyResource;
use App\Models\EmergencyResourceCategory;
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
        if (! Schema::hasTable('emergency_resource_categories')) {
            Schema::create('emergency_resource_categories', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('company_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->unsignedInteger('sort')->default(0);
                $table->timestamps();

                $table->unique(['company_id', 'name']);
            });
        }

        if (! Schema::hasColumn('emergency_resources', 'category_id')) {
            Schema::table('emergency_resources', function (Blueprint $table) {
                $table->foreignUuid('category_id')->nullable()->after('company_id')
                    ->constrained('emergency_resource_categories')->nullOnDelete();
            });
        }

        // Bestehende Firmen mit Standard-Kategorien versorgen und vorhandene
        // Ressourcen anhand ihres bisherigen Typs der passenden Kategorie zuordnen.
        $labelByValue = [];
        foreach (EmergencyResourceType::cases() as $case) {
            $labelByValue[$case->value] = $case->label();
        }

        Company::query()->withoutGlobalScopes()->get()->each(function (Company $company) use ($labelByValue) {
            $idByName = [];
            $sort = 1;
            foreach (EmergencyResourceCategory::defaultNames() as $name) {
                $category = EmergencyResourceCategory::withoutGlobalScopes()->firstOrCreate(
                    ['company_id' => $company->id, 'name' => $name],
                    ['sort' => $sort],
                );
                $idByName[$name] = $category->id;
                $sort++;
            }

            EmergencyResource::withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->whereNull('category_id')
                ->get()
                ->each(function (EmergencyResource $resource) use ($labelByValue, $idByName) {
                    $label = $labelByValue[$resource->getRawOriginal('type')] ?? null;
                    if ($label !== null && isset($idByName[$label])) {
                        $resource->category_id = $idByName[$label];
                        $resource->saveQuietly();
                    }
                });
        });

        // Alt-Spalte `type` ist durch die konfigurierbaren Kategorien abgelöst
        // und wird nicht mehr befüllt → nullable machen.
        Schema::table('emergency_resources', function (Blueprint $table) {
            $table->string('type')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emergency_resources', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });

        Schema::dropIfExists('emergency_resource_categories');
    }
};
