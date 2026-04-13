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
        // 1. Add new columns to quotas
        Schema::table('quotas', function (Blueprint $table) {
            $table->string('name')->nullable()->after('organization_id');
            $table->string('major_name')->nullable()->after('name');
            $table->string('program_name')->nullable()->after('major_name');
        });

        // 2. Add new columns to annual_quotas
        if (Schema::hasTable('annual_quotas')) {
            Schema::table('annual_quotas', function (Blueprint $table) {
                $table->string('name')->nullable()->after('program_id');
                $table->string('major_name')->nullable()->after('name');
                $table->string('program_name')->nullable()->after('major_name');
            });
        }

        // 3. Migrate data for quotas and annual_quotas
        // Quotas
        $quotas = DB::table('quotas')->get();
        foreach ($quotas as $quota) {
            $major = DB::table('majors')->where('id', $quota->major_id)->first();
            $program = DB::table('programs')->where('id', $quota->program_id)->first();
            $majorName = $major ? $major->name : null;
            $programName = $program ? $program->name : null;
            $name = trim($majorName . ($programName ? ' - ' . $programName : ''));
            DB::table('quotas')->where('id', $quota->id)->update([
                'name' => $name ?: 'Chương trình tuyển sinh ' . $quota->id,
                'major_name' => $majorName,
                'program_name' => $programName,
            ]);
        }

        // Annual Quotas
        if (Schema::hasTable('annual_quotas')) {
            $annualQuotas = DB::table('annual_quotas')->get();
            foreach ($annualQuotas as $aq) {
                $major = DB::table('majors')->where('id', $aq->major_id)->first();
                $program = DB::table('programs')->where('id', $aq->program_id)->first();
                $majorName = $major ? $major->name : null;
                $programName = $program ? $program->name : null;
                $name = trim($majorName . ($programName ? ' - ' . $programName : ''));
                DB::table('annual_quotas')->where('id', $aq->id)->update([
                    'name' => $name ?: 'Chỉ tiêu năm ' . $aq->id,
                    'major_name' => $majorName,
                    'program_name' => $programName,
                ]);
            }
        }

        // 4. Drop foreign keys and columns
        Schema::table('quotas', function (Blueprint $table) {
            $table->dropForeign(['major_id']);
            $table->dropForeign(['program_id']);
            $table->dropUnique(['intake_id', 'major_id', 'organization_id']);
            $table->dropIndex(['major_id', 'organization_id']);
            $table->dropColumn(['major_id', 'program_id']);
        });

        if (Schema::hasTable('annual_quotas')) {
            Schema::table('annual_quotas', function (Blueprint $table) {
                $table->dropForeign(['major_id']);
                $table->dropForeign(['program_id']);
                $table->dropUnique(['organization_id', 'major_id', 'program_id', 'year']);
                $table->dropColumn(['major_id', 'program_id']);
            });
        }

        Schema::table('students', function (Blueprint $table) {
            // Because major_id and program_id were added via foreignId()->constrained(), we need to drop them safely
            if (Schema::hasColumn('students', 'major_id')) {
                // SQLite might have issue dropping foreign keys if we don't know the exact name. 
                // Using Laravel's array syntax will automatically guess the constraint name.
                $table->dropForeign(['major_id']);
                $table->dropColumn('major_id');
            }
            if (Schema::hasColumn('students', 'program_id')) {
                $table->dropForeign(['program_id']);
                $table->dropColumn('program_id');
            }
            
            // Add quota_id instead
            $table->foreignId('quota_id')->nullable()->constrained('quotas')->nullOnDelete();
        });

        Schema::table('intakes', function (Blueprint $table) {
            if (Schema::hasColumn('intakes', 'program_id')) {
                $table->dropForeign(['program_id']);
                $table->dropColumn('program_id');
            }
        });

        // 5. Drop pivot tables
        Schema::dropIfExists('major_organization_program');
        Schema::dropIfExists('organization_program');
        Schema::dropIfExists('major_organization');
        Schema::dropIfExists('annual_quota_intake');

        // 6. Drop main tables
        Schema::dropIfExists('majors');
        Schema::dropIfExists('programs');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverting this complex migration is skipped as it involves data loss
    }
};
