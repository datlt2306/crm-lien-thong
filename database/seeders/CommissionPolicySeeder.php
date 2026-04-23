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

        // Global policies (Chính sách mặc định cho toàn hệ thống)
        $globalPolicies = [
            [
                'collaborator_id' => null,
                'program_type' => ['REGULAR', 'PART_TIME', 'DISTANCE'], // Áp dụng cho các hệ chuẩn
                'role' => 'PRIMARY',
                'type' => 'FIXED',
                'payout_rules' => [
                    'REGULAR' => [
                        [
                            'recipient_type' => 'direct_ctv',
                            'amount_vnd' => 1750000,
                            'payout_trigger' => 'payment_verified',
                            'description' => 'Hoa hồng hệ chính quy (Đợt 1)'
                        ]
                    ],
                    'PART_TIME' => [
                        [
                            'recipient_type' => 'direct_ctv',
                            'amount_vnd' => 750000,
                            'payout_trigger' => 'payment_verified',
                            'description' => 'Hoa hồng hệ vừa học vừa làm'
                        ]
                    ],
                    'DISTANCE' => [
                        [
                            'recipient_type' => 'direct_ctv',
                            'amount_vnd' => 200000,
                            'payout_trigger' => 'payment_verified',
                            'description' => 'Hoa hồng hệ đào tạo từ xa'
                        ]
                    ],
                    'default' => [
                        [
                            'recipient_type' => 'direct_ctv',
                            'amount_vnd' => 0,
                            'payout_trigger' => 'payment_verified',
                            'description' => 'Chưa cấu hình'
                        ]
                    ]
                ],
                'trigger' => 'ON_VERIFICATION',
                'visibility' => 'INTERNAL',
                'priority' => 0,
                'active' => true,
                'meta' => ['description' => 'Chính sách hoa hồng mặc định 2026 cho tất cả CTV'],
            ],
        ];

        // Collaborator-specific policies (Ví dụ chính sách riêng cho 1 CTV đặc biệt)
        $collabPolicies = [
            [
                'collaborator_id' => $collaborators->first()->id,
                'program_type' => ['REGULAR'],
                'role' => 'PRIMARY',
                'type' => 'FIXED',
                'amount_vnd' => 2000000, // CTV này được ưu đãi 2tr cho hệ CQ
                'trigger' => 'ON_VERIFICATION',
                'visibility' => 'INTERNAL',
                'priority' => 20,
                'active' => true,
                'meta' => ['description' => 'Chính sách ưu đãi cho CTV VIP: ' . $collaborators->first()->full_name],
            ],
        ];

        $allPolicies = array_merge($globalPolicies, $collabPolicies);

        foreach ($allPolicies as $policyData) {
            CommissionPolicy::create($policyData);
        }

        $this->command->info('Đã tạo mới ' . count($allPolicies) . ' chính sách hoa hồng chuẩn 2026.');
    }
}
