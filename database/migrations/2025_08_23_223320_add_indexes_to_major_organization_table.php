<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::table('major_organization', function (Blueprint $table) {
            // Thêm index cho organization_id và major_id để tối ưu query quota
            $table->index(['organization_id', 'major_id'], 'major_org_quota_index');

            // Thêm index cho quota để tối ưu query ngành còn quota
            $table->index('quota', 'major_org_quota_value_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::table('major_organization', function (Blueprint $table) {
            $table->dropIndex('major_org_quota_index');
            $table->dropIndex('major_org_quota_value_index');
        });
    }
};
