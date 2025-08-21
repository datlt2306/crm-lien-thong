<?php

namespace Database\Seeders;

use App\Models\CommissionPolicy;
use App\Models\Organization;
use App\Models\Collaborator;
use Illuminate\Database\Seeder;

class CommissionPolicySeeder extends Seeder {
    public function run(): void {
        $organization = Organization::first();
        if (!$organization) {
            $this->command->error('Chưa có Organization nào. Chạy OrganizationSeeder trước.');
            return;
        }

        $collaborators = Collaborator::where('status', 'active')->get();
        if ($collaborators->isEmpty()) {
            $this->command->error('Chưa có Collaborator active nào. Chạy CollaboratorSeeder trước.');
            return;
        }

        // Global policies (áp dụng cho tất cả)
        $globalPolicies = [
            [
                'organization_id' => null,
                'collaborator_id' => null,
                'program_type' => null,
                'role' => 'PRIMARY',
                'type' => 'PASS_THROUGH',
                'amount_vnd' => null,
                'percent' => null,
                'trigger' => 'ON_VERIFICATION',
                'visibility' => 'INTERNAL',
                'priority' => 0,
                'active' => true,
                'meta' => json_encode(['description' => 'CTV chính nhận 100% số tiền thanh toán']),
            ],
            [
                'organization_id' => null,
                'collaborator_id' => null,
                'program_type' => 'REGULAR',
                'role' => 'SUB',
                'type' => 'FIXED',
                'amount_vnd' => 700000,
                'percent' => null,
                'trigger' => 'ON_VERIFICATION',
                'visibility' => 'INTERNAL',
                'priority' => 0,
                'active' => true,
                'meta' => json_encode(['description' => 'CTV phụ chương trình chính quy: 700k']),
            ],
            [
                'organization_id' => null,
                'collaborator_id' => null,
                'program_type' => 'PART_TIME',
                'role' => 'SUB',
                'type' => 'FIXED',
                'amount_vnd' => 700000,
                'percent' => null,
                'trigger' => 'ON_ENROLLMENT',
                'visibility' => 'INTERNAL',
                'priority' => 0,
                'active' => true,
                'meta' => json_encode(['description' => 'CTV phụ chương trình bán thời gian: 700k khi nhập học']),
            ],
        ];

        // Organization-specific policies
        $orgPolicies = [
            [
                'organization_id' => $organization->id,
                'collaborator_id' => null,
                'program_type' => 'REGULAR',
                'role' => 'SUB',
                'type' => 'FIXED',
                'amount_vnd' => 800000,
                'percent' => null,
                'trigger' => 'ON_VERIFICATION',
                'visibility' => 'ORG_ONLY',
                'priority' => 10,
                'active' => true,
                'meta' => json_encode(['description' => 'CTV phụ GTVT chương trình chính quy: 800k']),
            ],
        ];

        // Collaborator-specific policies
        $collabPolicies = [
            [
                'organization_id' => $organization->id,
                'collaborator_id' => $collaborators->first()->id,
                'program_type' => null,
                'role' => 'PRIMARY',
                'type' => 'PERCENT',
                'amount_vnd' => null,
                'percent' => 15,
                'trigger' => 'ON_VERIFICATION',
                'visibility' => 'INTERNAL',
                'priority' => 20,
                'active' => true,
                'meta' => json_encode(['description' => 'CTV ' . $collaborators->first()->full_name . ' nhận 15%']),
            ],
        ];

        $allPolicies = array_merge($globalPolicies, $orgPolicies, $collabPolicies);

        foreach ($allPolicies as $policyData) {
            CommissionPolicy::create($policyData);
        }

        $this->command->info('Đã tạo ' . count($allPolicies) . ' chính sách hoa hồng mẫu.');
    }
}
