<?php

use App\Enums\ServiceProviderType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_providers', function (Blueprint $table) {
            $table->string('type')->default(ServiceProviderType::Other->value)->after('name');
            $table->decimal('direct_order_limit', 12, 2)->nullable()->after('sla');

            $table->index(['company_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::table('service_providers', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'type']);
            $table->dropColumn(['type', 'direct_order_limit']);
        });
    }
};
