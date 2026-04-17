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

        foreach ($tables as $table) {
            if (Schema::hasColumn($table, 'organization_id')) {
                // Use CASCADE to automatically drop dependent constraints and indexes
                DB::statement("ALTER TABLE \"$table\" DROP COLUMN IF EXISTS organization_id CASCADE");
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
