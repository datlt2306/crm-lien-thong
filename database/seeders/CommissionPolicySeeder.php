<?php

namespace Database\Seeders;

use App\Models\CommissionPolicy;
use App\Models\Organization;
use App\Models\Collaborator;
use Illuminate\Database\Seeder;

class CommissionPolicySeeder extends Seeder {
    public function run(): void {
        // Truncate để tránh lỗi truy vấn JSON trên PostgreSQL khi dùng updateOrCreate
        CommissionPolicy::truncate();

        $collaborators = Collaborator::where('status', 'active')->get();
        if ($collaborators->isEmpty()) {
            $this->command->error('Chưa có Collaborator active nào. Chạy CollaboratorSeeder trước.');
            return;
        }

        // Global policies (áp dụng cho tất cả)
        $globalPolicies = [
            [
                'collaborator_id' => null,
                'program_type' => null, // null cho tất cả
                'role' => 'PRIMARY',
                'type' => 'PASS_THROUGH',
                'amount_vnd' => null,
                'percent' => null,
                'trigger' => 'ON_VERIFICATION',
                'visibility' => 'INTERNAL',
                'priority' => 0,
                'active' => true,
                'meta' => ['description' => 'CTV chính nhận 100% số tiền thanh toán'],
            ],
            [
                'collaborator_id' => null,
                'program_type' => ['REGULAR'], // Cột JSON yêu cầu array
                'role' => 'SUB',
                'type' => 'FIXED',
                'amount_vnd' => 700000,
                'percent' => null,
                'trigger' => 'ON_VERIFICATION',
                'visibility' => 'INTERNAL',
                'priority' => 0,
                'active' => true,
                'meta' => ['description' => 'CTV phụ chương trình chính quy: 700k'],
            ],
            [
                'collaborator_id' => null,
                'program_type' => ['PART_TIME'],
                'role' => 'SUB',
                'type' => 'FIXED',
                'amount_vnd' => 700000,
                'percent' => null,
                'trigger' => 'ON_ENROLLMENT',
                'visibility' => 'INTERNAL',
                'priority' => 0,
                'active' => true,
                'meta' => ['description' => 'CTV phụ chương trình bán thời gian: 700k khi nhập học'],
            ],
        ];

        // Collaborator-specific policies
        $collabPolicies = [
            [
                'collaborator_id' => $collaborators->first()->id,
                'program_type' => null,
                'role' => 'PRIMARY',
                'type' => 'FIXED',
                'amount_vnd' => 1000000,
                'percent' => null,
                'trigger' => 'ON_VERIFICATION',
                'visibility' => 'INTERNAL',
                'priority' => 20,
                'active' => true,
                'meta' => ['description' => 'CTV ' . $collaborators->first()->full_name . ' nhận 1,000,000 VND'],
            ],
        ];

        $allPolicies = array_merge($globalPolicies, $collabPolicies);

        foreach ($allPolicies as $policyData) {
            CommissionPolicy::create($policyData);
        }

        $this->command->info('Đã tạo mới ' . count($allPolicies) . ' chính sách hoa hồng mẫu.');
    }
}
