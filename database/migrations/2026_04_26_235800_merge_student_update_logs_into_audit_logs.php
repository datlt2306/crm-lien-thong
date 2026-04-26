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
        if (Schema::hasTable('student_update_logs')) {
            // 1. Chuyển dữ liệu sang audit_logs
            $logs = DB::table('student_update_logs')->get();
            
            foreach ($logs as $log) {
                DB::table('audit_logs')->insert([
                    'id' => \Illuminate\Support\Str::ulid(),
                    'event_group' => 'SYSTEM',
                    'event_type' => 'UPDATED',
                    'auditable_type' => 'App\Models\Student',
                    'auditable_id' => $log->student_id,
                    'user_id' => $log->user_id,
                    'student_id' => $log->student_id,
                    'new_values' => $log->changes,
                    'created_at' => $log->created_at,
                    'metadata' => json_encode(['migrated_from' => 'student_update_logs']),
                ]);
            }

            // 2. Xóa bảng cũ
            Schema::dropIfExists('student_update_logs');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Không khuyến khích rollback việc xóa bảng và gộp dữ liệu
    }
};
