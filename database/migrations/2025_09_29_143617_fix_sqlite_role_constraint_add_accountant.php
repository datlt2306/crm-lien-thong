<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
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
                role varchar check ("role" in (\'super_admin\', \'chủ đơn vị\', \'ctv\', \'kế toán\')) not null default \'ctv\',
                phone varchar,
                avatar varchar
            )');

            // Copy dữ liệu từ bảng cũ sang bảng mới
            DB::statement('INSERT INTO users_new SELECT * FROM users');

            // Xóa bảng cũ
            DB::statement('DROP TABLE users');

            // Đổi tên bảng mới thành tên cũ
            DB::statement('ALTER TABLE users_new RENAME TO users');

            // Tạo lại index
            DB::statement('CREATE UNIQUE INDEX "users_email_unique" on "users" ("email")');
        } else {
            // Với MySQL/PostgreSQL, sử dụng cách thông thường
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });

            Schema::table('users', function (Blueprint $table) {
                $table->enum('role', ['super_admin', 'chủ đơn vị', 'ctv', 'kế toán'])->default('ctv');
            });
        }
    }

    public function down(): void {
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
                role varchar check ("role" in (\'super_admin\', \'chủ đơn vị\', \'ctv\')) not null default \'ctv\',
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
                $table->enum('role', ['super_admin', 'chủ đơn vị', 'ctv'])->default('ctv');
            });
        }
    }
};
