<?php

namespace Database\Seeders;

use App\Models\Collaborator;
use App\Models\CommissionPolicy;
use Illuminate\Database\Seeder;

class CommissionExampleSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Tạo các cộng tác viên trong ví dụ
        $me = Collaborator::updateOrCreate(
            ['email' => 'admin@example.com'],
            ['full_name' => 'Tôi (Quản lý)', 'phone' => '0911111111', 'status' => 'active']
        );

        $long = Collaborator::updateOrCreate(
            ['email' => 'long@example.com'],
            ['full_name' => 'Thằng Long', 'phone' => '0922222222', 'status' => 'active']
        );

        $son = Collaborator::updateOrCreate(
            ['email' => 'son@example.com'],
            ['full_name' => 'Thầy Sơn', 'phone' => '0933333333', 'status' => 'active']
        );

        // 2. Chính sách hoa hồng cho "Tôi" (Tự giới thiệu)
        // Nhận 1.750.000đ cho mỗi hồ sơ
        CommissionPolicy::create([
            'collaborator_id' => $me->id,
            'program_type' => 'REGULAR',
            'priority' => 10,
            'active' => true,
            'type' => 'FIXED', // Thêm để tránh lỗi NOT NULL
            'payout_rules' => [
                [
                    'recipient_type' => 'direct_ctv',
                    'amount_vnd' => 1750000,
                    'payout_trigger' => 'payment_verified',
                    'description' => 'Hoa hồng trực tiếp cho Quản lý'
                ]
            ],
        ]);

        // 3. Chính sách hoa hồng cho "Thằng Long"
        // Long nhận 750k, "Tôi" nhận 250k
        CommissionPolicy::create([
            'collaborator_id' => $long->id,
            'program_type' => 'REGULAR',
            'priority' => 10,
            'active' => true,
            'type' => 'FIXED', // Thêm để tránh lỗi NOT NULL
            'payout_rules' => [
                [
                    'recipient_type' => 'direct_ctv',
                    'amount_vnd' => 750000,
                    'payout_trigger' => 'payment_verified',
                    'description' => 'Hoa hồng trực tiếp cho Long'
                ],
                [
                    'recipient_type' => 'specific_ctv',
                    'recipient_id' => $me->id,
                    'amount_vnd' => 250000,
                    'payout_trigger' => 'payment_verified',
                    'description' => 'Tiền cắt phế từ Long chuyển cho Quản lý'
                ]
            ],
        ]);

        // 4. Chính sách hoa hồng cho "Thầy Sơn"
        // Thầy Sơn nhận 750k, "Tôi" nhận 150k
        CommissionPolicy::create([
            'collaborator_id' => $son->id,
            'program_type' => 'REGULAR',
            'priority' => 10,
            'active' => true,
            'type' => 'FIXED', // Thêm để tránh lỗi NOT NULL
            'payout_rules' => [
                [
                    'recipient_type' => 'direct_ctv',
                    'amount_vnd' => 750000,
                    'payout_trigger' => 'payment_verified',
                    'description' => 'Hoa hồng trực tiếp cho Thầy Sơn'
                ],
                [
                    'recipient_type' => 'specific_ctv',
                    'recipient_id' => $me->id,
                    'amount_vnd' => 150000,
                    'payout_trigger' => 'payment_verified',
                    'description' => 'Tiền cắt phế từ Thầy Sơn chuyển cho Quản lý'
                ]
            ],
        ]);

        echo "Đã tạo xong Seeder kịch bản hoa hồng của " . $me->full_name . "\n";
    }
}
