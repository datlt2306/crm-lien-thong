<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Thêm cột uuid (tạm thời để nullable để fill dữ liệu cũ)
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'uuid')) {
                $table->uuid('uuid')->nullable()->after('id')->index();
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'uuid')) {
                $table->uuid('uuid')->nullable()->after('id')->index();
            }
        });

        // 2. Điền UUID cho các bản ghi cũ
        DB::table('students')->whereNull('uuid')->get()->each(function ($student) {
            DB::table('students')->where('id', $student->id)->update(['uuid' => (string) Str::uuid()]);
        });

        DB::table('payments')->whereNull('uuid')->get()->each(function ($payment) {
            DB::table('payments')->where('id', $payment->id)->update(['uuid' => (string) Str::uuid()]);
        });

        // 3. Chuyển uuid sang NOT NULL
        // Lưu ý: Một số DB như SQLite không hỗ trợ change() cột nullable thành non-nullable dễ dàng 
        // nhưng với MySQL/PostgreSQL thì ổn.
        try {
            Schema::table('students', function (Blueprint $table) {
                $table->uuid('uuid')->nullable(false)->change();
            });
            Schema::table('payments', function (Blueprint $table) {
                $table->uuid('uuid')->nullable(false)->change();
            });
        } catch (\Exception $e) {
            // Fallback nếu không change được (ví dụ SQLite)
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};
