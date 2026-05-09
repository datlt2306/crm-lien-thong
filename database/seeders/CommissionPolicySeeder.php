<?php

namespace Database\Seeders;

use App\Models\CommissionPolicy;
use App\Models\Collaborator;
use Illuminate\Database\Seeder;

class CommissionPolicySeeder extends Seeder {
    public function run(): void {
        CommissionPolicy::truncate();

        $dat = Collaborator::where('email', 'datletrong2306@gmail.com')->first();

        if (!$dat) {
            $this->command->error('Missing Master collaborator (Dat). Please run CollaboratorSeeder first.');
            return;
        }

        // CHÍNH SÁCH TỔNG CHO ĐẠT (Kế toán chỉ thấy cái này)
        CommissionPolicy::create([
            'collaborator_id' => $dat->id,
            'program_type' => ['regular', 'part_time', 'distance'],
            'role' => 'primary',
            'type' => 'fixed',
            'payout_rules' => [
                'regular' => [
                    ['recipient_type' => 'direct_ctv', 'amount_vnd' => 1750000, 'payout_trigger' => 'payment_verified', 'description' => 'Hoa hồng tuyển sinh']
                ],
                'part_time' => [
                    ['recipient_type' => 'direct_ctv', 'amount_vnd' => 750000, 'payout_trigger' => 'payment_verified', 'description' => 'Hoa hồng đợt 1 (Xác nhận phí)'],
                    ['recipient_type' => 'direct_ctv', 'amount_vnd' => 1450000, 'payout_trigger' => 'student_enrolled', 'description' => 'Hoa hồng đợt 2 (Nhập học)']
                ],
                'distance' => [
                    ['recipient_type' => 'direct_ctv', 'amount_vnd' => 750000, 'payout_trigger' => 'payment_verified', 'description' => 'Hoa hồng đợt 1 (Xác nhận phí)'],
                    ['recipient_type' => 'direct_ctv', 'amount_vnd' => 1450000, 'payout_trigger' => 'student_enrolled', 'description' => 'Hoa hồng đợt 2 (Nhập học)']
                ]
            ],
            'trigger' => 'on_verification',
            'visibility' => 'internal',
            'priority' => 10,
            'active' => true,
            'meta' => ['description' => 'Chính sách gộp cho Master'],
        ]);

        // Chính sách mặc định cho các CTV khác (nếu có)
        CommissionPolicy::create([
            'program_type' => ['regular', 'part_time', 'distance'],
            'role' => 'primary',
            'type' => 'fixed',
            'payout_rules' => [
                'regular' => [['recipient_type' => 'direct_ctv', 'amount_vnd' => 1000000, 'payout_trigger' => 'payment_verified', 'description' => 'Hoa hồng mặc định']],
                'part_time' => [['recipient_type' => 'direct_ctv', 'amount_vnd' => 1500000, 'payout_trigger' => 'payment_verified', 'description' => 'Hoa hồng mặc định']],
                'distance' => [['recipient_type' => 'direct_ctv', 'amount_vnd' => 1200000, 'payout_trigger' => 'payment_verified', 'description' => 'Hoa hồng mặc định']]
            ],
            'trigger' => 'on_verification',
            'visibility' => 'internal',
            'priority' => 0,
            'active' => true,
        ]);
    }
}
