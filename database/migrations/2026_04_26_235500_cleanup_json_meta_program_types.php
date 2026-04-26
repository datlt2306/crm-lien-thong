<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::transaction(function () {
                // Chuẩn hóa program_type bên trong cột JSON meta của commission_items
                // Sử dụng jsonb_set để cập nhật giá trị bên trong JSON
                DB::statement("
                    UPDATE commission_items 
                    SET meta = jsonb_set(
                        meta::jsonb, 
                        '{program_type}', 
                        to_jsonb(LOWER(meta->>'program_type'))
                    )
                    WHERE meta->>'program_type' IS NOT NULL
                ");
                
                // Chuẩn hóa cả trường description nếu có chứa từ khóa hệ đào tạo (tùy chọn nhưng tốt)
                DB::statement("
                    UPDATE commission_items 
                    SET meta = jsonb_set(
                        meta::jsonb, 
                        '{payout_trigger_label}', 
                        to_jsonb(LOWER(meta->>'payout_trigger_label'))
                    )
                    WHERE meta->>'payout_trigger_label' IS NOT NULL
                ");
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback needed for data normalization
    }
};
