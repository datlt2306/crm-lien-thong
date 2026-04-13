<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('programs')) {
            return;
        }

        Schema::table('programs', function (Blueprint $table) {
            if (!Schema::hasColumn('programs', 'direct_commission_amount')) {
                $table->decimal('direct_commission_amount', 12, 2)->default(0)->after('is_active');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('programs') || !Schema::hasColumn('programs', 'direct_commission_amount')) {
            return;
        }

        Schema::table('programs', function (Blueprint $table) {
            $table->dropColumn('direct_commission_amount');
        });
    }
};
