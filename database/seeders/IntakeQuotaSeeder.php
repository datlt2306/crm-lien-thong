<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Organization;
use App\Models\Major;
use App\Models\Intake;
use App\Models\Quota;

class IntakeQuotaSeeder extends Seeder {
    /**
     * Run the database seeds.
     */
    public function run(): void {
        echo "=== TẠO DỮ LIỆU MẪU CHO INTAKE & QUOTA ===\n";

        $organization = Organization::first();
        if (!$organization) {
            echo "❌ Không tìm thấy organization nào. Vui lòng chạy OrganizationSeeder trước.\n";
            return;
        }

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

        foreach ($intakes as $intakeData) {
            $intakeData['organization_id'] = $organization->id;
            $intake = Intake::create($intakeData);
            echo "✓ Đã tạo đợt tuyển sinh: {$intake->name}\n";

            // Tạo quota cho từng major trong đợt tuyển sinh này
            foreach ($majors as $major) {
                $quota = Quota::create([
                    'intake_id' => $intake->id,
                    'major_id' => $major->id,
                    'organization_id' => $organization->id,
                    'target_quota' => rand(50, 200), // Chỉ tiêu mục tiêu ngẫu nhiên
                    'current_quota' => rand(0, 20), // Chỉ tiêu hiện tại ngẫu nhiên
                    'pending_quota' => rand(5, 30), // Chỉ tiêu đang chờ ngẫu nhiên
                    'reserved_quota' => rand(0, 10), // Chỉ tiêu đã đặt cọc ngẫu nhiên
                    'tuition_fee' => rand(5000000, 15000000), // Học phí ngẫu nhiên
                    'status' => Quota::STATUS_ACTIVE,
                ]);
                echo "  ✓ Đã tạo quota cho ngành: {$major->name} (chỉ tiêu: {$quota->target_quota})\n";
            }
        }

        echo "\n=== HOÀN THÀNH ===\n";
        echo "Intakes: " . Intake::count() . "\n";
        echo "Quotas: " . Quota::count() . "\n";
    }
}
