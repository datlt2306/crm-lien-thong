<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CommissionItem;
use App\Models\Payment;
use App\Models\Student;
use App\Models\WalletTransaction;
use App\Models\Wallet;
use App\Models\Collaborator;
use App\Models\Organization;
use Carbon\Carbon;

class ChartTestDataSeeder extends Seeder {
    public function run(): void {
        // Tạo dữ liệu test cho 30 ngày gần đây
        $startDate = Carbon::now()->subDays(30);

        // Lấy một số collaborator và organization để tạo dữ liệu
        $collaborators = Collaborator::take(5)->get();
        $organizations = Organization::take(3)->get();

        if ($collaborators->isEmpty() || $organizations->isEmpty()) {
            $this->command->info('Cần có dữ liệu Collaborator và Organization trước khi chạy seeder này.');
            return;
        }

        // Tạo thêm một số student để tránh unique constraint
        $studentCounter = 2;
        for ($i = 2; $i <= 50; $i++) {
            Student::create([
                'full_name' => 'Student Test ' . $studentCounter,
                'phone' => '012345678' . (1000 + $studentCounter),
                'email' => 'student' . $studentCounter . '@test.com',
                'organization_id' => $organizations->random()->id,
                'collaborator_id' => $collaborators->random()->id,
                'target_university' => 'Test University',
                'major' => 'Computer Science',
                'intake_month' => '2024-09',
                'program_type' => 'REGULAR',
                'source' => 'website',
                'status' => 'new',
            ]);
            $studentCounter++;
        }

        // Tạo một payment mẫu để commission có thể tham chiếu
        $samplePayment = \App\Models\Payment::create([
            'organization_id' => $organizations->first()->id,
            'student_id' => 1,
            'primary_collaborator_id' => $collaborators->first()->id,
            'sub_collaborator_id' => $collaborators->first()->id,
            'program_type' => 'REGULAR',
            'amount' => 10000000,
            'status' => 'verified',
            'created_at' => now(),
        ]);

        // Tạo commission với payment_id hợp lệ
        $commission = \App\Models\Commission::create([
            'student_id' => 1,
            'organization_id' => $organizations->first()->id,
            'payment_id' => $samplePayment->id,
            'rule' => json_encode(['type' => 'percentage', 'value' => 10]),
        ]);

        // Tạo dữ liệu CommissionItem
        for ($i = 0; $i < 30; $i++) {
            $date = $startDate->copy()->addDays($i);
            $count = rand(1, 5);

            for ($j = 0; $j < $count; $j++) {
                CommissionItem::create([
                    'commission_id' => $commission->id,
                    'recipient_collaborator_id' => $collaborators->random()->id,
                    'role' => rand(0, 1) ? 'direct' : 'downline',
                    'amount' => rand(100000, 5000000),
                    'status' => $this->getRandomStatus(['pending', 'payable', 'paid']),
                    'trigger' => 'enrollment',
                    'created_at' => $date->copy()->addHours(rand(0, 23)),
                ]);
            }
        }



        // Tạo dữ liệu Student
        for ($i = 0; $i < 30; $i++) {
            $date = $startDate->copy()->addDays($i);
            $count = rand(1, 4);

            for ($j = 0; $j < $count; $j++) {
                Student::create([
                    'full_name' => 'Student Test ' . $studentCounter,
                    'phone' => '012345678' . (1000 + $studentCounter),
                    'email' => 'student' . $studentCounter . '@test.com',
                    'organization_id' => $organizations->random()->id,
                    'collaborator_id' => $collaborators->random()->id,
                    'target_university' => 'Test University',
                    'major' => 'Computer Science',
                    'intake_month' => '2024-09',
                    'program_type' => 'REGULAR',
                    'source' => 'website',
                    'status' => $this->getRandomStatus(['new', 'contacted', 'submitted', 'approved', 'enrolled', 'rejected']),
                    'created_at' => $date->copy()->addHours(rand(0, 23)),
                ]);
                $studentCounter++;
            }
        }

        // Tạo dữ liệu WalletTransaction
        $wallets = Wallet::all();
        if ($wallets->isNotEmpty()) {
            for ($i = 0; $i < 30; $i++) {
                $date = $startDate->copy()->addDays($i);
                $count = rand(1, 3);

                for ($j = 0; $j < $count; $j++) {
                    $wallet = $wallets->random();
                    $amount = rand(100000, 2000000);
                    $type = rand(0, 1) ? 'deposit' : 'withdrawal';

                    WalletTransaction::create([
                        'wallet_id' => $wallet->id,
                        'type' => $type,
                        'amount' => $amount,
                        'balance_before' => $wallet->balance,
                        'balance_after' => $type === 'deposit' ? $wallet->balance + $amount : $wallet->balance - $amount,
                        'description' => $type === 'deposit' ? 'Thu hoa hồng' : 'Chi hoa hồng',
                        'created_at' => $date->copy()->addHours(rand(0, 23)),
                    ]);
                }
            }
        }

        $this->command->info('Đã tạo dữ liệu test cho charts thành công!');
    }

    private function getRandomStatus(array $statuses): string {
        return $statuses[array_rand($statuses)];
    }
}
