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
        Schema::table('ref_codes', function (Blueprint $table) {
            $table->decimal('commission_regular', 15, 2)->nullable()->default(0)->comment('Tiền chia cho Proxy - Chính quy');
            $table->decimal('commission_part_time', 15, 2)->nullable()->default(0)->comment('Tiền chia cho Proxy - VHVL');
            $table->decimal('commission_distance', 15, 2)->nullable()->default(0)->comment('Tiền chia cho Proxy - Từ xa');
        });
    }

    public function down(): void
    {
        Schema::table('ref_codes', function (Blueprint $table) {
            $table->dropColumn(['commission_regular', 'commission_part_time', 'commission_distance']);
        });
    }
};
