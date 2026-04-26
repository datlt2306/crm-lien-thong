<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::dropIfExists('downline_commission_configs');
        
        Schema::table('collaborators', function (Blueprint $table) {
            if (Schema::hasColumn('collaborators', 'upline_id')) {
                $table->dropForeign(['upline_id']);
                $table->dropColumn('upline_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::create('downline_commission_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('upline_collaborator_id');
            $table->unsignedBigInteger('downline_collaborator_id');
            $table->decimal('cq_amount', 10, 2)->default(0);
            $table->decimal('vhvlv_amount', 10, 2)->default(0);
            $table->enum('payment_type', ['immediate', 'on_enrollment'])->default('immediate');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['upline_collaborator_id', 'downline_collaborator_id']);
        });

        Schema::table('collaborators', function (Blueprint $table) {
            $table->foreignId('upline_id')->nullable()->constrained('collaborators')->nullOnDelete();
        });
    }
};
