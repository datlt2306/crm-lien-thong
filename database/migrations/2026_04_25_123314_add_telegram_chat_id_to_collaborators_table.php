<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('collaborators', 'telegram_chat_id')) {
            Schema::table('collaborators', function (Blueprint $table) {
                $table->string('telegram_chat_id')->nullable()->after('email');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('collaborators', 'telegram_chat_id')) {
            Schema::table('collaborators', function (Blueprint $table) {
                $table->dropColumn('telegram_chat_id');
            });
        }
    }
};
