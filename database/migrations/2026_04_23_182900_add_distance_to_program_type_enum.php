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
        // PostgreSQL doesn't support easy enum changes via Blueprint for existing columns.
        // We need to drop the constraint and recreate it, or use raw SQL.
        
        // 1. Update students table
        if (config('database.default') === 'pgsql') {
            DB::statement('ALTER TABLE students DROP CONSTRAINT IF EXISTS students_program_type_check');
            DB::statement("ALTER TABLE students ADD CONSTRAINT students_program_type_check CHECK (program_type::text = ANY (ARRAY['REGULAR'::character varying, 'PART_TIME'::character varying, 'DISTANCE'::character varying]::text[]))");
        } else {
            Schema::table('students', function (Blueprint $table) {
                $table->enum('program_type', ['REGULAR', 'PART_TIME', 'DISTANCE'])->nullable()->change();
            });
        }

        // 2. Update payments table
        if (config('database.default') === 'pgsql') {
            DB::statement('ALTER TABLE payments DROP CONSTRAINT IF EXISTS payments_program_type_check');
            DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_program_type_check CHECK (program_type::text = ANY (ARRAY['REGULAR'::character varying, 'PART_TIME'::character varying, 'DISTANCE'::character varying]::text[]))");
        } else {
            Schema::table('payments', function (Blueprint $table) {
                $table->enum('program_type', ['REGULAR', 'PART_TIME', 'DISTANCE'])->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore to original ['REGULAR', 'PART_TIME']
        if (config('database.default') === 'pgsql') {
            DB::statement('ALTER TABLE students DROP CONSTRAINT IF EXISTS students_program_type_check');
            DB::statement("ALTER TABLE students ADD CONSTRAINT students_program_type_check CHECK (program_type::text = ANY (ARRAY['REGULAR'::character varying, 'PART_TIME'::character varying]::text[]))");
            
            DB::statement('ALTER TABLE payments DROP CONSTRAINT IF EXISTS payments_program_type_check');
            DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_program_type_check CHECK (program_type::text = ANY (ARRAY['REGULAR'::character varying, 'PART_TIME'::character varying]::text[]))");
        } else {
            Schema::table('students', function (Blueprint $table) {
                $table->enum('program_type', ['REGULAR', 'PART_TIME'])->nullable()->change();
            });
            Schema::table('payments', function (Blueprint $table) {
                $table->enum('program_type', ['REGULAR', 'PART_TIME'])->nullable()->change();
            });
        }
    }
};
