<?php

namespace Database\Seeders;

use App\Models\CommissionPolicy;
use App\Models\Collaborator;
use Illuminate\Database\Seeder;

class CommissionPolicySeeder extends Seeder {
    public function run(): void {
        // Xóa sạch để nạp mới hoàn toàn
        CommissionPolicy::truncate();

        // Lấy ID của các nhân vật chính
        $dat = Collaborator::where('email', 'datletrong2306@gmail.com')->first();
        $long = Collaborator::where('email', 'tahailongseo@gmail.com')->first();
        $son = Collaborator::where('email', 'sondt32@fpt.edu.vn')->first();

        if (!$dat || !$long || !$son) {
            $this->command->error('Thiếu Collaborator (Đạt/Long/Sơn). Vui lòng chạy CollaboratorSeeder trước.');
            return;
        }

        // 1. CHÍNH SÁCH CHUNG (Mặc định cho toàn hệ thống)
        CommissionPolicy::create([
            'collaborator_id' => null,
            'program_type' => ['REGULAR', 'PART_TIME', 'DISTANCE'],
            'role' => 'PRIMARY',
            'type' => 'FIXED',
            'payout_rules' => [
                'REGULAR' => [
                    [
                        'recipient_type' => 'direct_ctv',
                        'amount_vnd' => 1750000,
                        'payout_trigger' => 'payment_verified', // Mùng 5
                        'description' => 'Hoa hồng trực tiếp hệ Chính quy'
                    ]
                ],
                'PART_TIME' => [
                    [
                        'recipient_type' => 'direct_ctv',
                        'amount_vnd' => 750000,
                        'payout_trigger' => 'payment_verified', // Mùng 5
                        'description' => 'Hoa hồng đợt 1 (Mùng 5)'
                    ],
                    [
                        'recipient_type' => 'direct_ctv',
                        'amount_vnd' => 2200000,
                        'payout_trigger' => 'student_enrolled', // Nhập học
                        'description' => 'Hoa hồng sau khi nhập học'
                    ]
                ],
                'DISTANCE' => [
                    [
                        'recipient_type' => 'direct_ctv',
                        'amount_vnd' => 200000,
                        'payout_trigger' => 'payment_verified', // Mùng 5
                        'description' => 'Hoa hồng đợt 1 (Mùng 5)'
                    ],
                    [
                        'recipient_type' => 'direct_ctv',
                        'amount_vnd' => 2200000,
                        'payout_trigger' => 'student_enrolled', // Nhập học
                        'description' => 'Hoa hồng sau khi nhập học'
                    ]
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
                    [
                        'recipient_type' => 'direct_ctv',
                        'amount_vnd' => 1750000,
                        'payout_trigger' => 'payment_verified',
                        'description' => 'Hoa hồng trực tiếp hệ Chính quy'
                    ]
                ],
                'PART_TIME' => [
                    [
                        'recipient_type' => 'direct_ctv',
                        'amount_vnd' => 750000,
                        'payout_trigger' => 'payment_verified',
                        'description' => 'Hoa hồng đợt 1 (Mùng 5)'
                    ],
                    [
                        'recipient_type' => 'direct_ctv',
                        'amount_vnd' => 2000000, // Cập nhật theo yêu cầu mới
                        'payout_trigger' => 'student_enrolled',
                        'description' => 'Hoa hồng sau khi nhập học'
                    ]
                ],
                'DISTANCE' => [
                    [
                        'recipient_type' => 'direct_ctv',
                        'amount_vnd' => 200000,
                        'payout_trigger' => 'payment_verified',
                        'description' => 'Hoa hồng đợt 1 (Mùng 5)'
                    ],
                    [
                        'recipient_type' => 'direct_ctv',
                        'amount_vnd' => 2000000, // Cập nhật theo yêu cầu mới
                        'payout_trigger' => 'student_enrolled',
                        'description' => 'Hoa hồng sau khi nhập học'
                    ]
                ]
            ],
            'trigger' => 'ON_VERIFICATION',
            'visibility' => 'INTERNAL',
            'priority' => 20,
            'active' => true,
            'meta' => ['description' => 'Chính sách riêng cho Đạt: 2000k sau nhập học'],
        ]);

        // 3. CHÍNH SÁCH RIÊNG CHO LONG
        CommissionPolicy::create([
            'collaborator_id' => $long->id,
            'program_type' => ['REGULAR', 'PART_TIME', 'DISTANCE'],
            'role' => 'PRIMARY',
            'type' => 'FIXED',
            'payout_rules' => [
                'REGULAR' => [
                    [
                        'recipient_type' => 'direct_ctv',
                        'amount_vnd' => 750000,
                        'payout_trigger' => 'payment_verified',
                        'description' => 'Hoa hồng trực tiếp cho Long'
                    ],
                    [
                        'recipient_type' => 'specific_ctv',
                        'recipient_id' => $dat->id,
                        'amount_vnd' => 1000000,
                        'payout_trigger' => 'payment_verified',
                        'description' => 'Hoa hồng quản lý chia cho Đạt'
                    ]
                ],
                'PART_TIME' => [
                    [
                        'recipient_type' => 'direct_ctv',
                        'amount_vnd' => 750000,
                        'payout_trigger' => 'payment_verified',
                        'description' => 'Hoa hồng đợt 1 (Mùng 5)'
                    ],
                    [
                        'recipient_type' => 'direct_ctv',
                        'amount_vnd' => 950000,
                        'payout_trigger' => 'student_enrolled',
                        'description' => 'Hoa hồng nhập học cho Long'
                    ],
                    [
                        'recipient_type' => 'specific_ctv',
                        'recipient_id' => $dat->id,
                        'amount_vnd' => 1150000,
                        'payout_trigger' => 'student_enrolled',
                        'description' => 'Hoa hồng quản lý sau nhập học cho Đạt'
                    ]
                ],
                'DISTANCE' => [
                    [
                        'recipient_type' => 'direct_ctv',
                        'amount_vnd' => 200000,
                        'payout_trigger' => 'payment_verified',
                        'description' => 'Hoa hồng đợt 1 (Mùng 5)'
                    ],
                    [
                        'recipient_type' => 'direct_ctv',
                        'amount_vnd' => 950000,
                        'payout_trigger' => 'student_enrolled',
                        'description' => 'Hoa hồng nhập học cho Long'
                    ],
                    [
                        'recipient_type' => 'specific_ctv',
                        'recipient_id' => $dat->id,
                        'amount_vnd' => 1150000,
                        'payout_trigger' => 'student_enrolled',
                        'description' => 'Hoa hồng quản lý sau nhập học cho Đạt'
                    ]
                ]
            ],
            'trigger' => 'ON_VERIFICATION',
            'visibility' => 'INTERNAL',
            'priority' => 10,
            'active' => true,
            'meta' => ['description' => 'Chính sách riêng cho Long: Chia 1150k cho Đạt sau nhập học'],
        ]);

        // 4. CHÍNH SÁCH RIÊNG CHO SƠN
        CommissionPolicy::create([
            'collaborator_id' => $son->id,
            'program_type' => ['REGULAR', 'PART_TIME', 'DISTANCE'],
            'role' => 'PRIMARY',
            'type' => 'FIXED',
            'payout_rules' => [
                'REGULAR' => [
                    [
                        'recipient_type' => 'direct_ctv',
                        'amount_vnd' => 750000,
                        'payout_trigger' => 'payment_verified',
                        'description' => 'Hoa hồng trực tiếp cho Sơn'
                    ],
                    [
                        'recipient_type' => 'specific_ctv',
                        'recipient_id' => $dat->id,
                        'amount_vnd' => 1000000,
                        'payout_trigger' => 'payment_verified',
                        'description' => 'Hoa hồng quản lý chia cho Đạt'
                    ]
                ],
                'PART_TIME' => [
                    [
                        'recipient_type' => 'direct_ctv',
                        'amount_vnd' => 750000,
                        'payout_trigger' => 'payment_verified',
                        'description' => 'Hoa hồng đợt 1 (Mùng 5)'
                    ],
                    [
                        'recipient_type' => 'direct_ctv',
                        'amount_vnd' => 1350000,
                        'payout_trigger' => 'student_enrolled',
                        'description' => 'Hoa hồng nhập học cho Sơn'
                    ],
                    [
                        'recipient_type' => 'specific_ctv',
                        'recipient_id' => $dat->id,
                        'amount_vnd' => 650000,
                        'payout_trigger' => 'student_enrolled',
                        'description' => 'Hoa hồng quản lý sau nhập học cho Đạt'
                    ]
                ],
                'DISTANCE' => [
                    [
                        'recipient_type' => 'direct_ctv',
                        'amount_vnd' => 200000,
                        'payout_trigger' => 'payment_verified',
                        'description' => 'Hoa hồng đợt 1 (Mùng 5)'
                    ],
                    [
                        'recipient_type' => 'direct_ctv',
                        'amount_vnd' => 1350000,
                        'payout_trigger' => 'student_enrolled',
                        'description' => 'Hoa hồng nhập học cho Sơn'
                    ],
                    [
                        'recipient_type' => 'specific_ctv',
                        'recipient_id' => $dat->id,
                        'amount_vnd' => 650000,
                        'payout_trigger' => 'student_enrolled',
                        'description' => 'Hoa hồng quản lý sau nhập học cho Đạt'
                    ]
                ]
            ],
            'trigger' => 'ON_VERIFICATION',
            'visibility' => 'INTERNAL',
            'priority' => 10,
            'active' => true,
            'meta' => ['description' => 'Chính sách riêng cho Sơn: Chia 650k cho Đạt sau nhập học'],
        ]);

        $this->command->info('Đã cập nhật xong hoa hồng nhập học (VHVL/DTTX) cho Đạt, Long, Sơn.');
    }
}
