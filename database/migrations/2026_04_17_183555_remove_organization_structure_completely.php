<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::disableForeignKeyConstraints();
        }

        $tables = [
            'users',
            'collaborators',
            'students',
            'payments',
            'commissions',
            'commission_policies',
            'collaborator_registrations',
            'intakes',
            'quotas',
            'annual_quotas'
        ];

        if (DB::getDriverName() === 'sqlite') {
            foreach ($tables as $table) {
                if (Schema::hasColumn($table, 'organization_id')) {
                    Schema::table($table, function (Blueprint $t) {
                        $t->unsignedBigInteger('organization_id')->nullable()->change();
                    });
                }
            }
        }

        foreach ($tables as $table) {
            if (Schema::hasColumn($table, 'organization_id')) {
                if (DB::getDriverName() === 'sqlite') {
                    // Skip dropping on sqlite to avoid foreign key alteration constraints
                } else {
                    // Use CASCADE to automatically drop dependent constraints and indexes
                    DB::statement("ALTER TABLE \"$table\" DROP COLUMN IF EXISTS organization_id CASCADE");
                }
            }
        }

        // Drop pivot tables
        Schema::dropIfExists('major_organization_program');
        Schema::dropIfExists('major_organization');
        Schema::dropIfExists('organization_program');

        // Drop the main table
        Schema::dropIfExists('organizations');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No turning back
    }
};
