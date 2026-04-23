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
                'name' => 'Đợt 1 - Học kỳ I 2026',
                'description' => 'Đợt tuyển sinh đầu tiên cho học kỳ I năm 2026',
                'start_date' => '2026-01-01',
                'end_date' => '2026-03-31',
                'enrollment_deadline' => '2026-04-15',
                'status' => Intake::STATUS_ACTIVE,
            ],
            [
                'name' => 'Đợt 2 - Học kỳ I 2026',
                'description' => 'Đợt tuyển sinh thứ hai cho học kỳ I năm 2026',
                'start_date' => '2026-04-01',
                'end_date' => '2026-06-30',
                'enrollment_deadline' => '2026-07-15',
                'status' => Intake::STATUS_UPCOMING,
            ],
            [
                'name' => 'Đợt 1 - Học kỳ II 2026',
                'description' => 'Đợt tuyển sinh cho học kỳ II năm 2026',
                'start_date' => '2026-07-01',
                'end_date' => '2026-09-30',
                'enrollment_deadline' => '2026-10-15',
                'status' => Intake::STATUS_UPCOMING,
            ],
        ];

        $createdIntakes = [];
        foreach ($intakes as $intakeData) {
            $intake = Intake::updateOrCreate(
                ['name' => $intakeData['name']],
                $intakeData
            );
            $createdIntakes[] = $intake;
            echo "✓ Đã xử lý đợt tuyển sinh: {$intake->name}\n";
        }

        // Tạo chỉ tiêu năm làm nguồn tổng, sau đó phân bổ cho từng đợt.
        // Tỷ lệ phân bổ mẫu: 40% - 35% - 25%.
        $allocRatios = [0.40, 0.35, 0.25];
        $year = (int) ($createdIntakes[0]->start_date?->format('Y') ?? now()->format('Y'));

        $programTypes = ['REGULAR', 'PART_TIME', 'DISTANCE'];

        foreach ($majors as $major) {
            foreach ($programTypes as $programType) {
                $annualTarget = rand(50, 150);
                $annual = AnnualQuota::updateOrCreate(
                    [
                        'major_name' => $major->name,
                        'program_name' => $programType,
                        'year' => $year,
                    ],
                    [
                        'name' => $major->name . ' - ' . $programType,
                        'target_quota' => $annualTarget,
                        'current_quota' => 0,
                        'status' => AnnualQuota::STATUS_ACTIVE,
                    ]
                );

                $allocated = 0;
                foreach ($createdIntakes as $idx => $intake) {
                    $isLast = $idx === count($createdIntakes) - 1;
                    $target = $isLast
                        ? ($annualTarget - $allocated)
                        : (int) floor($annualTarget * $allocRatios[$idx]);
                    $allocated += $target;

                    $quota = Quota::updateOrCreate(
                        [
                            'intake_id' => $intake->id,
                            'major_name' => $major->name,
                            'program_name' => $programType,
                        ],
                        [
                            'name' => $major->name . ' - ' . $programType,
                            'target_quota' => $target,
                            'current_quota' => 0,
                            'pending_quota' => rand(1, 5),
                            'reserved_quota' => rand(0, 2),
                            'tuition_fee' => match($programType) {
                                'REGULAR' => 15000000,
                                'PART_TIME' => 12000000,
                                'DISTANCE' => 9000000,
                                default => 10000000,
                            },
                            'status' => Quota::STATUS_ACTIVE,
                        ]
                    );

                    echo "  ✓ Đợt {$intake->name} - {$major->name} ({$programType}): {$quota->target_quota}/{$annual->target_quota}\n";
                }
            }
        }

        echo "\n=== HOÀN THÀNH ===\n";
        echo "Intakes: " . Intake::count() . "\n";
        echo "Quotas: " . Quota::count() . "\n";
    }
}
