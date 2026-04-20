<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        if (config('database.default') === 'pgsql') {
            // Xóa constraint cũ và thêm constraint mới bao gồm 'reverted'
            DB::statement("ALTER TABLE payments DROP CONSTRAINT IF EXISTS payments_status_check");
            DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_status_check CHECK (status IN ('not_paid', 'submitted', 'verified', 'reverted', 'rejected'))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        if (config('database.default') === 'pgsql') {
            DB::statement("ALTER TABLE payments DROP CONSTRAINT IF EXISTS payments_status_check");
            DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_status_check CHECK (status IN ('not_paid', 'submitted', 'verified'))");
        }
    }
};
