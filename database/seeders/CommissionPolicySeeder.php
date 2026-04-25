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
            'program_type' => ['REGULAR', 'PART_TIME', 'DISTANCE'],
            'role' => 'PRIMARY',
            'type' => 'FIXED',
            'payout_rules' => [
                'REGULAR' => [
                    ['recipient_type' => 'direct_ctv', 'amount_vnd' => 1750000, 'payout_trigger' => 'payment_verified', 'description' => 'Hoa hồng tuyển sinh']
                ],
                'PART_TIME' => [
                    ['recipient_type' => 'direct_ctv', 'amount_vnd' => 2850000, 'payout_trigger' => 'payment_verified', 'description' => 'Hoa hồng tuyển sinh (Gộp)']
                ],
                'DISTANCE' => [
                    ['recipient_type' => 'direct_ctv', 'amount_vnd' => 2300000, 'payout_trigger' => 'payment_verified', 'description' => 'Hoa hồng tuyển sinh (Gộp)']
                ]
            ],
            'trigger' => 'ON_VERIFICATION',
            'visibility' => 'INTERNAL',
            'priority' => 10,
            'active' => true,
            'meta' => ['description' => 'Chính sách gộp cho Master'],
        ]);

        // Chính sách mặc định cho các CTV khác (nếu có)
        CommissionPolicy::create([
            'program_type' => ['REGULAR', 'PART_TIME', 'DISTANCE'],
            'role' => 'PRIMARY',
            'type' => 'FIXED',
            'payout_rules' => [
                'REGULAR' => [['recipient_type' => 'direct_ctv', 'amount_vnd' => 1000000, 'payout_trigger' => 'payment_verified', 'description' => 'Hoa hồng mặc định']],
                'PART_TIME' => [['recipient_type' => 'direct_ctv', 'amount_vnd' => 1500000, 'payout_trigger' => 'payment_verified', 'description' => 'Hoa hồng mặc định']],
                'DISTANCE' => [['recipient_type' => 'direct_ctv', 'amount_vnd' => 1200000, 'payout_trigger' => 'payment_verified', 'description' => 'Hoa hồng mặc định']]
            ],
            'trigger' => 'ON_VERIFICATION',
            'visibility' => 'INTERNAL',
            'priority' => 0,
            'active' => true,
        ]);
    }
}
