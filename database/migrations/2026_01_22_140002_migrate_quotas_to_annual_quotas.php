<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\AnnualQuota;

/**
 * Gộp dữ liệu từ quotas (chỉ tiêu theo đợt) sang annual_quotas (chỉ tiêu năm).
 * Với mỗi (organization, major, program, year): target = SUM(quotas.target), current = SUM(quotas.current).
 * program_id = COALESCE(quotas.program_id, intakes.program_id); bỏ qua nếu vẫn null.
 */
return new class extends Migration {
    public function up(): void {
        if (!Schema::hasTable('annual_quotas') || !Schema::hasTable('quotas') || !Schema::hasTable('intakes')) {
            return;
        }

        $rows = DB::table('quotas as q')
            ->join('intakes as i', 'q.intake_id', '=', 'i.id')
            ->whereNotNull('i.start_date')
            ->select(
                'q.organization_id',
                'q.major_id',
                DB::raw('COALESCE(q.program_id, i.program_id) as program_id'),
                'i.start_date',
                'q.target_quota',
                'q.current_quota'
            )
            ->get();

        $groups = [];
        foreach ($rows as $r) {
            $programId = $r->program_id;
            if ($programId === null) {
                continue;
            }
            $year = (int) \Illuminate\Support\Carbon::parse($r->start_date)->format('Y');
            $k = "{$r->organization_id}_{$r->major_id}_{$programId}_{$year}";
            if (!isset($groups[$k])) {
                $groups[$k] = [
                    'organization_id' => $r->organization_id,
                    'major_id' => $r->major_id,
                    'program_id' => $programId,
                    'year' => $year,
                    'target_quota' => 0,
                    'current_quota' => 0,
                ];
            }
            $groups[$k]['target_quota'] += (int) $r->target_quota;
            $groups[$k]['current_quota'] += (int) $r->current_quota;
        }

        foreach ($groups as $g) {
            $current = $g['current_quota'];
            $target = $g['target_quota'];
            AnnualQuota::firstOrCreate(
                [
                    'organization_id' => $g['organization_id'],
                    'major_id' => $g['major_id'],
                    'program_id' => $g['program_id'],
                    'year' => $g['year'],
                ],
                [
                    'target_quota' => $target,
                    'current_quota' => $current,
                    'status' => ($target > 0 && $current >= $target) ? AnnualQuota::STATUS_FULL : AnnualQuota::STATUS_ACTIVE,
                ]
            );
        }
    }

    public function down(): void {
        // Không revert: không thể tách chắc chắn dữ liệu đã gộp; xóa hết annual_quotas sẽ mất cả bản ghi người dùng tạo.
    }
};
