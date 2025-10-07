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
        if (DB::getDriverName() === 'sqlite') {
            // Với SQLite, cần tạo lại bảng để rename column
            DB::statement('CREATE TABLE organizations_new (
                id integer primary key autoincrement not null,
                name varchar not null,
                code varchar,
                status varchar,
                organization_owner_id bigint,
                created_at datetime,
                updated_at datetime
            )');

            // Copy dữ liệu từ bảng cũ sang bảng mới
            DB::statement('INSERT INTO organizations_new (id, name, code, status, organization_owner_id, created_at, updated_at) 
                          SELECT id, name, code, status, owner_id, created_at, updated_at FROM organizations');

            // Xóa bảng cũ
            DB::statement('DROP TABLE organizations');

            // Đổi tên bảng mới thành tên cũ
            DB::statement('ALTER TABLE organizations_new RENAME TO organizations');
        } else {
            Schema::table('organizations', function (Blueprint $table) {
                $table->renameColumn('owner_id', 'organization_owner_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        if (DB::getDriverName() === 'sqlite') {
            // Rollback cho SQLite
            DB::statement('CREATE TABLE organizations_old (
                id integer primary key autoincrement not null,
                name varchar not null,
                code varchar,
                status varchar,
                owner_id bigint,
                created_at datetime,
                updated_at datetime
            )');

            DB::statement('INSERT INTO organizations_old (id, name, code, status, owner_id, created_at, updated_at) 
                          SELECT id, name, code, status, organization_owner_id, created_at, updated_at FROM organizations');

            DB::statement('DROP TABLE organizations');
            DB::statement('ALTER TABLE organizations_old RENAME TO organizations');
        } else {
            Schema::table('organizations', function (Blueprint $table) {
                $table->renameColumn('organization_owner_id', 'owner_id');
            });
        }
    }
};
