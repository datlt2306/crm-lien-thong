<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Major;
use App\Models\Intake;
use App\Models\Quota;
use App\Models\AnnualQuota;

class IntakeQuotaSeeder extends Seeder {
    /**
     * Run the database seeds.
     */
    public function run(): void {
        echo "=== TẠO DỮ LIỆU MẪU CHO INTAKE & QUOTA ===\n";


        $majors = Major::all();
        if ($majors->isEmpty()) {
            echo "❌ Không tìm thấy major nào. Vui lòng chạy MajorSeeder trước.\n";
            return;
        }

        // Tạo các đợt tuyển sinh
        $intakes = [
            [
                'name' => 'Đợt 1 - Học kỳ I 2025',
                'description' => 'Đợt tuyển sinh đầu tiên cho học kỳ I năm 2025',
                'start_date' => '2025-01-01',
                'end_date' => '2025-03-31',
                'enrollment_deadline' => '2025-04-15',
                'status' => Intake::STATUS_ACTIVE,
            ],
            [
                'name' => 'Đợt 2 - Học kỳ I 2025',
                'description' => 'Đợt tuyển sinh thứ hai cho học kỳ I năm 2025',
                'start_date' => '2025-04-01',
                'end_date' => '2025-06-30',
                'enrollment_deadline' => '2025-07-15',
                'status' => Intake::STATUS_UPCOMING,
            ],
            [
                'name' => 'Đợt 1 - Học kỳ II 2025',
                'description' => 'Đợt tuyển sinh cho học kỳ II năm 2025',
                'start_date' => '2025-07-01',
                'end_date' => '2025-09-30',
                'enrollment_deadline' => '2025-10-15',
                'status' => Intake::STATUS_UPCOMING,
            ],
        ];

        $createdIntakes = [];
        foreach ($intakes as $intakeData) {
            $intake = Intake::create($intakeData);
            $createdIntakes[] = $intake;
            echo "✓ Đã tạo đợt tuyển sinh: {$intake->name}\n";
        }

        // Tạo chỉ tiêu năm làm nguồn tổng, sau đó phân bổ cho từng đợt.
        // Tỷ lệ phân bổ mẫu: 40% - 35% - 25%.
        $allocRatios = [0.40, 0.35, 0.25];
        $year = (int) ($createdIntakes[0]->start_date?->format('Y') ?? now()->format('Y'));

        foreach ($majors as $major) {
            $annualTarget = rand(120, 300);
            $annual = AnnualQuota::create([
                
                'name' => $major->name . ' - REGULAR',
                'major_name' => $major->name,
                'program_name' => 'REGULAR',
                'year' => $year,
                'target_quota' => $annualTarget,
                'current_quota' => 0,
                'status' => AnnualQuota::STATUS_ACTIVE,
            ]);

            $allocated = 0;
            foreach ($createdIntakes as $idx => $intake) {
                $isLast = $idx === count($createdIntakes) - 1;
                $target = $isLast
                    ? ($annualTarget - $allocated)
                    : (int) floor($annualTarget * $allocRatios[$idx]);
                $allocated += $target;

                $quota = Quota::create([
                    'intake_id' => $intake->id,
                    
                    'name' => $major->name . ' - REGULAR',
                    'major_name' => $major->name,
                    'program_name' => 'REGULAR',
                    'target_quota' => $target,
                    'current_quota' => 0,
                    'pending_quota' => rand(2, 8),
                    'reserved_quota' => rand(0, 3),
                    'tuition_fee' => rand(5000000, 15000000),
                    'status' => Quota::STATUS_ACTIVE,
                ]);

                echo "  ✓ Đợt {$intake->name} - {$major->name}: {$quota->target_quota}/{$annual->target_quota}\n";
            }
        }

        echo "\n=== HOÀN THÀNH ===\n";
        echo "Intakes: " . Intake::count() . "\n";
        echo "Quotas: " . Quota::count() . "\n";
    }
}
