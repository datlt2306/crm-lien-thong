<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('major_organization_program', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('major_organization_id');
            $table->unsignedBigInteger('program_id');
            $table->timestamps();

            $table->foreign('major_organization_id')->references('id')->on('major_organization')->onDelete('cascade');
            $table->foreign('program_id')->references('id')->on('programs')->onDelete('cascade');

            // Đảm bảo không có duplicate
            $table->unique(['major_organization_id', 'program_id']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('major_organization_program');
    }
};
