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
        // Sửa lỗi index trong commission_items
        // Kiểm tra xem index có tồn tại không trước khi xóa
        $indexExists = false;

        if (DB::getDriverName() === 'pgsql') {
            $indexes = DB::select("SELECT indexname FROM pg_indexes WHERE schemaname = 'public' AND tablename = 'commission_items'");
            foreach ($indexes as $index) {
                if ($index->indexname === 'commission_items_status_recipient_id_index') {
                    $indexExists = true;
                    break;
                }
            }
        } else {
            $indexes = DB::select("PRAGMA index_list(commission_items)");
            foreach ($indexes as $index) {
                if ($index->name === 'commission_items_status_recipient_id_index') {
                    $indexExists = true;
                    break;
                }
            }
        }

        if ($indexExists) {
            Schema::table('commission_items', function (Blueprint $table) {
                $table->dropIndex('commission_items_status_recipient_id_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        // Không cần rollback
    }
};
