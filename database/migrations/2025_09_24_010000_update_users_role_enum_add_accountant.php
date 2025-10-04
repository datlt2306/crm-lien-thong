<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // Migration này đã được xử lý bởi migration 2025_09_29_143617_fix_sqlite_role_constraint_add_accountant
        // Không cần thực hiện gì thêm
    }

    public function down(): void {
        // Không cần rollback vì migration này đã được xử lý bởi migration khác
    }
};
