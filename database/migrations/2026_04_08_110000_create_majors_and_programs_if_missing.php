<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('majors')) {
            Schema::create('majors', function (Blueprint $table) {
                $table->id();
                $table->string('code')->nullable();
                $table->string('name');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('programs')) {
            Schema::create('programs', function (Blueprint $table) {
                $table->id();
                $table->string('code')->nullable();
                $table->string('name');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Không drop để tránh mất dữ liệu danh mục nếu bảng đã tồn tại từ trước.
    }
};

