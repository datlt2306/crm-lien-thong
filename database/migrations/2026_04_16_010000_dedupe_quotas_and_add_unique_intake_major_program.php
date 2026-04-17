<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        DB::transaction(function () {
            $duplicateGroups = DB::table('quotas')
                ->select('intake_id', 'major_name', 'program_name', DB::raw('COUNT(*) as total'))
                ->groupBy('intake_id', 'major_name', 'program_name')
                ->havingRaw('COUNT(*) > 1')
                ->get();

            foreach ($duplicateGroups as $group) {
                $quotas = DB::table('quotas')
                    ->where('intake_id', $group->intake_id)
                    ->where('major_name', $group->major_name)
                    ->where('program_name', $group->program_name)
                    ->orderByDesc('id')
                    ->get();

                if ($quotas->count() < 2) {
                    continue;
                }

                $keeper = null;
                $maxStudentCount = -1;

                foreach ($quotas as $quota) {
                    $studentCount = (int) DB::table('students')->where('quota_id', $quota->id)->count();
                    if ($studentCount > $maxStudentCount) {
                        $maxStudentCount = $studentCount;
                        $keeper = $quota;
                    }
                }

                if (!$keeper) {
                    $keeper = $quotas->first();
                }

                $idsToMerge = $quotas->pluck('id')->filter(fn($id) => (int) $id !== (int) $keeper->id)->values();
                if ($idsToMerge->isEmpty()) {
                    continue;
                }

                // Gộp dữ liệu chỉ tiêu theo hướng an toàn.
                $maxTarget = (int) $quotas->max('target_quota');
                $maxCurrent = (int) $quotas->max('current_quota');
                $sumPending = (int) $quotas->sum('pending_quota');
                $sumReserved = (int) $quotas->sum('reserved_quota');

                DB::table('quotas')
                    ->where('id', $keeper->id)
                    ->update([
                        'target_quota' => $maxTarget,
                        'current_quota' => $maxCurrent,
                        'pending_quota' => $sumPending,
                        'reserved_quota' => $sumReserved,
                        'updated_at' => now(),
                    ]);

                // Re-map học viên sang quota giữ lại.
                DB::table('students')
                    ->whereIn('quota_id', $idsToMerge)
                    ->update([
                        'quota_id' => $keeper->id,
                        'updated_at' => now(),
                    ]);

                DB::table('quotas')->whereIn('id', $idsToMerge)->delete();
            }
        });

        Schema::table('quotas', function (Blueprint $table) {
            $table->unique(['intake_id', 'major_name', 'program_name'], 'quotas_intake_major_program_unique');
        });
    }

    public function down(): void {
        Schema::table('quotas', function (Blueprint $table) {
            $table->dropUnique('quotas_intake_major_program_unique');
        });
    }
};

