<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Intake;
use App\Models\Quota;
use App\Models\AnnualQuota;

class IntakeQuotaSeeder extends Seeder {
    /**
     * Run the database seeds.
     */
    public function run(): void {
        echo "=== CẬP NHẬT CHỈ TIÊU TUYỂN SINH 2026 ===\n";

        // 1. Tạo/Cập nhật các đợt tuyển sinh
        $intakes = [
            [
                'name' => 'Đợt 1/2026',
                'description' => 'Đợt tuyển sinh đầu tiên năm 2026',
                'start_date' => '2026-01-01',
                'end_date' => '2026-03-31',
                'enrollment_deadline' => '2026-04-15',
                'status' => Intake::STATUS_ACTIVE,
            ],
            [
                'name' => 'Đợt 2/2026',
                'description' => 'Đợt tuyển sinh thứ hai năm 2026',
                'start_date' => '2026-04-01',
                'end_date' => '2026-06-30',
                'enrollment_deadline' => '2026-07-15',
                'status' => Intake::STATUS_UPCOMING,
            ],
        ];

        $createdIntakes = [];
        foreach ($intakes as $intakeData) {
            $intake = Intake::updateOrCreate(
                ['name' => $intakeData['name']],
                $intakeData
            );
            $createdIntakes[$intake->name] = $intake;
            echo "✓ Đã xử lý đợt tuyển sinh: {$intake->name}\n";
        }

        // 2. Định nghĩa dữ liệu chỉ tiêu từ ảnh
        $majorName = 'Công nghệ thông tin';
        $year = 2026;

        // Xóa các chỉ tiêu cũ của năm 2026 để làm sạch dữ liệu
        Quota::whereHas('intake', function($q) use ($year) {
            $q->whereYear('start_date', $year);
        })->delete();
        AnnualQuota::where('year', $year)->delete();

        $quotaData = [
            'Đợt 1/2026' => [
                'regular' => 30,
                'part_time' => 58,
                'distance' => 49,
            ],
            'Đợt 2/2026' => [
                'regular' => 45,
                'part_time' => 87,
                'distance' => 75,
            ],
        ];

        // 3. Xóa các chỉ tiêu cũ không nằm trong danh sách (nếu muốn dọn dẹp sạch sẽ)
        // Quota::truncate(); 
        // AnnualQuota::truncate();

        foreach (['regular', 'part_time', 'distance'] as $programType) {
            // Tính tổng chỉ tiêu năm
            $annualTarget = ($quotaData['Đợt 1/2026'][$programType] ?? 0) + ($quotaData['Đợt 2/2026'][$programType] ?? 0);

            // Tạo chỉ tiêu năm
            $annual = AnnualQuota::updateOrCreate(
                [
                    'major_name' => $majorName,
                    'program_name' => $programType,
                    'year' => $year,
                ],
                [
                    'name' => $majorName . ' - ' . $programType,
                    'target_quota' => $annualTarget,
                    'current_quota' => 0,
                    'status' => AnnualQuota::STATUS_ACTIVE,
                ]
            );

            // Tạo chỉ tiêu cho từng đợt
            foreach ($quotaData as $intakeName => $programs) {
                $intake = $createdIntakes[$intakeName];
                $target = $programs[$programType];

                $quota = Quota::updateOrCreate(
                    [
                        'intake_id' => $intake->id,
                        'major_name' => $majorName,
                        'program_name' => $programType,
                    ],
                    [
                        'name' => $majorName . ' - ' . $programType,
                        'target_quota' => $target,
                        'current_quota' => 0,
                        'pending_quota' => 0,
                        'reserved_quota' => 0,
                        'tuition_fee' => match($programType) {
                            'regular' => 1750000, // Khớp với phí trong StudentSeeder
                            'part_time' => 750000,
                            'distance' => 200000,
                            default => 1000000,
                        },
                        'status' => Quota::STATUS_ACTIVE,
                    ]
                );

                echo "  ✓ {$intakeName} - {$majorName} ({$programType}): {$target}\n";
            }
        }

        echo "\n=== HOÀN THÀNH ===\n";
    }
}
