<?php

namespace Database\Seeders;

use App\Models\CommissionPolicy;
use App\Models\Collaborator;
use Illuminate\Database\Seeder;

class CommissionPolicySeeder extends Seeder {
    public function run(): void {
        // Clear old policies to avoid duplicates and conflicts
        CommissionPolicy::truncate();

        $dat = Collaborator::where('email', 'datletrong2306@gmail.com')->first();
        $long = Collaborator::where('email', 'tahailongseo@gmail.com')->first();
        $son = Collaborator::where('email', 'sondt32@fpt.edu.vn')->first();

        if (!$dat || !$long || !$son) {
            $this->command->error('Missing collaborators (Dat, Long, or Son). Please run CollaboratorSeeder first.');
            return;
        }

        // 1. CHÍNH SÁCH MẶC ĐỊNH (Fallback)
        CommissionPolicy::create([
            'program_type' => ['REGULAR', 'PART_TIME', 'DISTANCE'],
            'role' => 'PRIMARY',
            'type' => 'FIXED',
            'payout_rules' => [
                'REGULAR' => [
                    ['recipient_type' => 'direct_ctv', 'amount_vnd' => 1750000, 'payout_trigger' => 'payment_verified', 'description' => 'Hoa hồng trực tiếp Chính quy']
                ],
                'PART_TIME' => [
                    ['recipient_type' => 'direct_ctv', 'amount_vnd' => 750000, 'payout_trigger' => 'payment_verified', 'description' => 'Lệ phí hồ sơ (Mùng 5)'],
                    ['recipient_type' => 'direct_ctv', 'amount_vnd' => 2100000, 'payout_trigger' => 'student_enrolled', 'description' => 'Hoa hồng sau khi nhập học']
                ],
                'DISTANCE' => [
                    ['recipient_type' => 'direct_ctv', 'amount_vnd' => 200000, 'payout_trigger' => 'payment_verified', 'description' => 'Lệ phí hồ sơ (Mùng 5)'],
                    ['recipient_type' => 'direct_ctv', 'amount_vnd' => 2100000, 'payout_trigger' => 'student_enrolled', 'description' => 'Hoa hồng sau khi nhập học']
                ]
            ],
            'trigger' => 'ON_VERIFICATION',
            'visibility' => 'INTERNAL',
            'priority' => 0,
            'active' => true,
            'meta' => ['description' => 'Chính sách mặc định 2026 cho tất cả CTV'],
        ]);

        // 2. CHÍNH SÁCH RIÊNG CHO ĐẠT (CTV CHÍNH)
        CommissionPolicy::create([
            'collaborator_id' => $dat->id,
            'program_type' => ['REGULAR', 'PART_TIME', 'DISTANCE'],
            'role' => 'PRIMARY',
            'type' => 'FIXED',
            'payout_rules' => [
                'REGULAR' => [
                    ['recipient_type' => 'direct_ctv', 'amount_vnd' => 1750000, 'payout_trigger' => 'payment_verified', 'description' => 'Hoa hồng trực tiếp Chính quy']
                ],
                'PART_TIME' => [
                    ['recipient_type' => 'direct_ctv', 'amount_vnd' => 750000, 'payout_trigger' => 'payment_verified', 'description' => 'Lệ phí hồ sơ (Mùng 5)'],
                    ['recipient_type' => 'direct_ctv', 'amount_vnd' => 2100000, 'payout_trigger' => 'student_enrolled', 'description' => 'Hoa hồng sau khi nhập học']
                ],
                'DISTANCE' => [
                    ['recipient_type' => 'direct_ctv', 'amount_vnd' => 200000, 'payout_trigger' => 'payment_verified', 'description' => 'Lệ phí hồ sơ (Mùng 5)'],
                    ['recipient_type' => 'direct_ctv', 'amount_vnd' => 2100000, 'payout_trigger' => 'student_enrolled', 'description' => 'Hoa hồng sau khi nhập học']
                ]
            ],
            'trigger' => 'ON_VERIFICATION',
            'visibility' => 'INTERNAL',
            'priority' => 10,
            'active' => true,
            'meta' => ['description' => 'Chính sách riêng cho Đạt'],
        ]);

        // 3. CHÍNH SÁCH RIÊNG CHO LONG (Có upline là Đạt)
        CommissionPolicy::create([
            'collaborator_id' => $long->id,
            'program_type' => ['REGULAR', 'PART_TIME', 'DISTANCE'],
            'role' => 'PRIMARY',
            'type' => 'FIXED',
            'payout_rules' => [
                'REGULAR' => [
                    ['recipient_type' => 'direct_ctv', 'amount_vnd' => 750000, 'payout_trigger' => 'payment_verified', 'description' => 'Hoa hồng trực tiếp cho Long'],
                    ['recipient_type' => 'specific_ctv', 'recipient_id' => $dat->id, 'amount_vnd' => 1000000, 'payout_trigger' => 'payment_verified', 'description' => 'Hoa hồng quản lý cho Đạt']
                ],
                'PART_TIME' => [
                    ['recipient_type' => 'direct_ctv', 'amount_vnd' => 750000, 'payout_trigger' => 'payment_verified', 'description' => 'Lệ phí hồ sơ (Mùng 5)'],
                    ['recipient_type' => 'direct_ctv', 'amount_vnd' => 950000, 'payout_trigger' => 'student_enrolled', 'description' => 'Hoa hồng nhập học cho Long'],
                    ['recipient_type' => 'specific_ctv', 'recipient_id' => $dat->id, 'amount_vnd' => 1150000, 'payout_trigger' => 'student_enrolled', 'description' => 'Hoa hồng quản lý sau nhập học cho Đạt']
                ],
                'DISTANCE' => [
                    ['recipient_type' => 'direct_ctv', 'amount_vnd' => 200000, 'payout_trigger' => 'payment_verified', 'description' => 'Lệ phí hồ sơ (Mùng 5)'],
                    ['recipient_type' => 'direct_ctv', 'amount_vnd' => 950000, 'payout_trigger' => 'student_enrolled', 'description' => 'Hoa hồng nhập học cho Long'],
                    ['recipient_type' => 'specific_ctv', 'recipient_id' => $dat->id, 'amount_vnd' => 1150000, 'payout_trigger' => 'student_enrolled', 'description' => 'Hoa hồng quản lý sau nhập học cho Đạt']
                ]
            ],
            'trigger' => 'ON_VERIFICATION',
            'visibility' => 'INTERNAL',
            'priority' => 10,
            'active' => true,
            'meta' => ['description' => 'Chính sách riêng cho Long (Upline: Đạt)'],
        ]);

        // 4. CHÍNH SÁCH RIÊNG CHO SƠN (Có upline là Đạt)
        CommissionPolicy::create([
            'collaborator_id' => $son->id,
            'program_type' => ['REGULAR', 'PART_TIME', 'DISTANCE'],
            'role' => 'PRIMARY',
            'type' => 'FIXED',
            'payout_rules' => [
                'REGULAR' => [
                    ['recipient_type' => 'direct_ctv', 'amount_vnd' => 750000, 'payout_trigger' => 'payment_verified', 'description' => 'Hoa hồng trực tiếp cho Sơn'],
                    ['recipient_type' => 'specific_ctv', 'recipient_id' => $dat->id, 'amount_vnd' => 1000000, 'payout_trigger' => 'payment_verified', 'description' => 'Hoa hồng quản lý cho Đạt']
                ],
                'PART_TIME' => [
                    ['recipient_type' => 'direct_ctv', 'amount_vnd' => 750000, 'payout_trigger' => 'payment_verified', 'description' => 'Lệ phí hồ sơ (Mùng 5)'],
                    ['recipient_type' => 'direct_ctv', 'amount_vnd' => 950000, 'payout_trigger' => 'student_enrolled', 'description' => 'Hoa hồng nhập học cho Sơn'],
                    ['recipient_type' => 'specific_ctv', 'recipient_id' => $dat->id, 'amount_vnd' => 1150000, 'payout_trigger' => 'student_enrolled', 'description' => 'Hoa hồng quản lý sau nhập học cho Đạt']
                ],
                'DISTANCE' => [
                    ['recipient_type' => 'direct_ctv', 'amount_vnd' => 200000, 'payout_trigger' => 'payment_verified', 'description' => 'Lệ phí hồ sơ (Mùng 5)'],
                    ['recipient_type' => 'direct_ctv', 'amount_vnd' => 950000, 'payout_trigger' => 'student_enrolled', 'description' => 'Hoa hồng nhập học cho Sơn'],
                    ['recipient_type' => 'specific_ctv', 'recipient_id' => $dat->id, 'amount_vnd' => 1150000, 'payout_trigger' => 'student_enrolled', 'description' => 'Hoa hồng quản lý sau nhập học cho Đạt']
                ]
            ],
            'trigger' => 'ON_VERIFICATION',
            'visibility' => 'INTERNAL',
            'priority' => 10,
            'active' => true,
            'meta' => ['description' => 'Chính sách riêng cho Sơn (Upline: Đạt)'],
        ]);
    }
}
