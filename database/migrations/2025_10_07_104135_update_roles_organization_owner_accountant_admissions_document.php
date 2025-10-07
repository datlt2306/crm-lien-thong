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
        // Với SQLite, cần tạo lại bảng để sửa CHECK constraint
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
                role varchar check ("role" in (\'super_admin\', \'organization_owner\', \'ctv\', \'accountant\', \'admissions\', \'document\')) not null default \'ctv\',
                phone varchar,
                avatar varchar
            )');

            // Copy dữ liệu từ bảng cũ sang bảng mới và cập nhật roles
            DB::statement('INSERT INTO users_new SELECT id, name, email, email_verified_at, password, remember_token, created_at, updated_at, 
                CASE 
                    WHEN role = "owner" THEN "organization_owner"
                    WHEN role = "kế toán" THEN "accountant"
                    ELSE role 
                END, phone, avatar FROM users');

            // Xóa bảng cũ
            DB::statement('DROP TABLE users');

            // Đổi tên bảng mới thành tên cũ
            DB::statement('ALTER TABLE users_new RENAME TO users');

            // Tạo lại index
            DB::statement('CREATE UNIQUE INDEX "users_email_unique" on "users" ("email")');

            // Cập nhật dữ liệu trong bảng roles (Spatie Permission)
            // Tìm ID của các role cũ
            $oldOwnerRoleId = DB::table('roles')->where('name', 'owner')->first()?->id;
            $oldAccountantRoleId = DB::table('roles')->where('name', 'kế toán')->first()?->id;

            // Cập nhật tên roles
            if ($oldOwnerRoleId) {
                DB::table('roles')->where('name', 'owner')->update(['name' => 'organization_owner']);
            }
            if ($oldAccountantRoleId) {
                DB::table('roles')->where('name', 'kế toán')->update(['name' => 'accountant']);
            }

            // Tạo các role mới nếu chưa có
            DB::table('roles')->insertOrIgnore([
                ['name' => 'admissions', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'document', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]
            ]);
        } else {
            // Với MySQL/PostgreSQL, cập nhật dữ liệu trước
            DB::table('users')->where('role', 'owner')->update(['role' => 'organization_owner']);
            DB::table('users')->where('role', 'kế toán')->update(['role' => 'accountant']);

            // Cập nhật dữ liệu trong bảng roles (Spatie Permission)
            DB::table('roles')->where('name', 'owner')->update(['name' => 'organization_owner']);
            DB::table('roles')->where('name', 'kế toán')->update(['name' => 'accountant']);

            // Tạo các role mới nếu chưa có
            DB::table('roles')->insertOrIgnore([
                ['name' => 'admissions', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'document', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]
            ]);

            // Sau đó sử dụng cách thông thường
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });

            Schema::table('users', function (Blueprint $table) {
                $table->enum('role', ['super_admin', 'organization_owner', 'ctv', 'accountant', 'admissions', 'document'])->default('ctv');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        // Với SQLite, rollback
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
                role varchar check ("role" in (\'super_admin\', \'owner\', \'ctv\', \'kế toán\')) not null default \'ctv\',
                phone varchar,
                avatar varchar
            )');

            // Copy dữ liệu và rollback roles
            DB::statement('INSERT INTO users_old SELECT id, name, email, email_verified_at, password, remember_token, created_at, updated_at, 
                CASE 
                    WHEN role = "organization_owner" THEN "owner"
                    WHEN role = "accountant" THEN "kế toán"
                    ELSE role 
                END, phone, avatar FROM users');

            DB::statement('DROP TABLE users');
            DB::statement('ALTER TABLE users_old RENAME TO users');
            DB::statement('CREATE UNIQUE INDEX "users_email_unique" on "users" ("email")');

            // Rollback roles
            DB::table('roles')->where('name', 'organization_owner')->update(['name' => 'owner']);
            DB::table('roles')->where('name', 'accountant')->update(['name' => 'kế toán']);

            // Xóa các role mới
            DB::table('roles')->whereIn('name', ['admissions', 'document'])->delete();
        } else {
            // Với MySQL/PostgreSQL, rollback
            DB::table('users')->where('role', 'organization_owner')->update(['role' => 'owner']);
            DB::table('users')->where('role', 'accountant')->update(['role' => 'kế toán']);

            DB::table('roles')->where('name', 'organization_owner')->update(['name' => 'owner']);
            DB::table('roles')->where('name', 'accountant')->update(['name' => 'kế toán']);

            // Xóa các role mới
            DB::table('roles')->whereIn('name', ['admissions', 'document'])->delete();

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });

            Schema::table('users', function (Blueprint $table) {
                $table->enum('role', ['super_admin', 'owner', 'ctv', 'kế toán'])->default('ctv');
            });
        }
    }
};
