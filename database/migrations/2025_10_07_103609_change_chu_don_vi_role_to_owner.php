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
        // Với SQLite, cần tạo lại bảng để sửa CHECK constraint trước
        if (DB::getDriverName() === 'sqlite') {
            // Tạo bảng tạm với cấu trúc mới
            DB::statement('CREATE TABLE users_new (
                id integer primary key autoincrement not null,
                name varchar not null,
                email varchar not null,
                email_verified_at datetime,
                password varchar not null,
                remember_token varchar,
                created_at datetime,
                updated_at datetime,
                role varchar check ("role" in (\'super_admin\', \'owner\', \'ctv\', \'kế toán\')) not null default \'ctv\',
                phone varchar,
                avatar varchar
            )');

            // Copy dữ liệu từ bảng cũ sang bảng mới và thay đổi role
            DB::statement('INSERT INTO users_new SELECT id, name, email, email_verified_at, password, remember_token, created_at, updated_at, 
                CASE WHEN role = "chủ đơn vị" THEN "owner" ELSE role END, phone, avatar FROM users');

            // Xóa bảng cũ
            DB::statement('DROP TABLE users');

            // Đổi tên bảng mới thành tên cũ
            DB::statement('ALTER TABLE users_new RENAME TO users');

            // Tạo lại index
            DB::statement('CREATE UNIQUE INDEX "users_email_unique" on "users" ("email")');

            // Cập nhật dữ liệu trong bảng roles (Spatie Permission)
            // Tìm ID của role cũ trước khi cập nhật
            $oldRoleId = DB::table('roles')->where('name', 'chủ đơn vị')->first()?->id;

            // Cập nhật tên role
            DB::table('roles')->where('name', 'chủ đơn vị')->update(['name' => 'owner']);

            // Cập nhật dữ liệu trong bảng model_has_roles
            if ($oldRoleId) {
                DB::table('model_has_roles')->where('role_id', $oldRoleId)->update(['role_id' => $oldRoleId]);
            }
        } else {
            // Với MySQL/PostgreSQL, cập nhật dữ liệu trước
            DB::table('users')->where('role', 'chủ đơn vị')->update(['role' => 'owner']);

            // Cập nhật dữ liệu trong bảng roles (Spatie Permission)
            DB::table('roles')->where('name', 'chủ đơn vị')->update(['name' => 'owner']);

            // Cập nhật dữ liệu trong bảng model_has_roles
            DB::table('model_has_roles')->where('role_id', function ($query) {
                $query->select('id')->from('roles')->where('name', 'chủ đơn vị');
            })->update(['role_id' => function ($query) {
                $query->select('id')->from('roles')->where('name', 'owner');
            }]);

            // Sau đó sử dụng cách thông thường
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });

            Schema::table('users', function (Blueprint $table) {
                $table->enum('role', ['super_admin', 'owner', 'ctv', 'kế toán'])->default('ctv');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        // Cập nhật dữ liệu trong bảng users (rollback)
        DB::table('users')->where('role', 'owner')->update(['role' => 'chủ đơn vị']);

        // Cập nhật dữ liệu trong bảng roles (Spatie Permission) - rollback
        DB::table('roles')->where('name', 'owner')->update(['name' => 'chủ đơn vị']);

        // Cập nhật dữ liệu trong bảng model_has_roles - rollback
        DB::table('model_has_roles')->where('role_id', function ($query) {
            $query->select('id')->from('roles')->where('name', 'owner');
        })->update(['role_id' => function ($query) {
            $query->select('id')->from('roles')->where('name', 'chủ đơn vị');
        }]);

        if (DB::getDriverName() === 'sqlite') {
            // Tạo lại bảng với constraint cũ
            DB::statement('CREATE TABLE users_old (
                id integer primary key autoincrement not null,
                name varchar not null,
                email varchar not null,
                email_verified_at datetime,
                password varchar not null,
                remember_token varchar,
                created_at datetime,
                updated_at datetime,
                role varchar check ("role" in (\'super_admin\', \'chủ đơn vị\', \'ctv\', \'kế toán\')) not null default \'ctv\',
                phone varchar,
                avatar varchar
            )');

            DB::statement('INSERT INTO users_old SELECT * FROM users');
            DB::statement('DROP TABLE users');
            DB::statement('ALTER TABLE users_old RENAME TO users');
            DB::statement('CREATE UNIQUE INDEX "users_email_unique" on "users" ("email")');
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });

            Schema::table('users', function (Blueprint $table) {
                $table->enum('role', ['super_admin', 'chủ đơn vị', 'ctv', 'kế toán'])->default('ctv');
            });
        }
    }
};
