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
        Schema::table('audit_logs', function (Blueprint $table) {
            // Index cho việc truy vấn lịch sử chi tiết theo đối tượng
            $table->index(['auditable_type', 'auditable_id'], 'idx_audit_logs_auditable');
            
            // Index bổ sung cho created_at để sắp xếp nhanh hơn
            $table->index('created_at', 'idx_audit_logs_created_at');
        });

        // Gộp StudentUpdateLog vào AuditLog (Tương lai)
        // Hiện tại chỉ đánh index để tăng tốc
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('idx_audit_logs_auditable');
            $table->dropIndex('idx_audit_logs_created_at');
        });
    }
};
