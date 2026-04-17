<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('intake_program_windows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('intake_id')->constrained('intakes')->cascadeOnDelete();
            $table->string('program_name'); // REGULAR, PART_TIME, DISTANCE
            $table->date('start_date');
            $table->date('end_date');
            $table->date('enrollment_deadline')->nullable();
            $table->timestamps();

            $table->unique(['intake_id', 'program_name']);
            $table->index(['intake_id', 'program_name']);
            $table->index(['start_date', 'end_date']);
        });

        // Backfill dữ liệu: nếu intake đã có quota theo hệ, tạo window mặc định theo start/end của intake.
        // Tránh tạo window trùng (idempotent).
        $intakeIds = DB::table('intakes')->pluck('id')->toArray();
        foreach ($intakeIds as $intakeId) {
            $intake = DB::table('intakes')
                ->select(['id', 'start_date', 'end_date', 'enrollment_deadline'])
                ->where('id', $intakeId)
                ->first();
            if (!$intake) {
                continue;
            }

            $programNames = DB::table('quotas')
                ->where('intake_id', $intakeId)
                ->whereNotNull('program_name')
                ->distinct()
                ->pluck('program_name')
                ->toArray();

            foreach ($programNames as $programName) {
                DB::table('intake_program_windows')->updateOrInsert(
                    ['intake_id' => $intakeId, 'program_name' => (string) $programName],
                    [
                        'start_date' => $intake->start_date,
                        'end_date' => $intake->end_date,
                        'enrollment_deadline' => $intake->enrollment_deadline,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }

    public function down(): void {
        Schema::dropIfExists('intake_program_windows');
    }
};

