<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('commission_policies', function (Blueprint $table) {
            $table->json('payout_rules')->nullable()->after('amount_vnd');
            $table->string('target_program_id')->nullable()->after('program_type');
        });
    }

    public function down(): void {
        Schema::table('commission_policies', function (Blueprint $table) {
            $table->dropColumn(['payout_rules', 'target_program_id']);
        });
    }
};
