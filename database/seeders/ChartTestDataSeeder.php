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
use App\Models\Quota;
use Carbon\Carbon;

class ChartTestDataSeeder extends Seeder {
    public function run(): void {
        // Tạo dữ liệu test cho 30 ngày gần đây
        $startDate = Carbon::now()->subDays(30);

        // Lấy dữ liệu nền để tạo chart
        $collaborators = Collaborator::take(5)->get();
        $quotas = Quota::with('intake')->get();

        if ($collaborators->isEmpty() || $quotas->isEmpty()) {
            $this->command->info('Cần có dữ liệu Collaborator, Quota/Intake trước khi chạy seeder này.');
            return;
        }

        // Tạo thêm một số student
        $studentCounter = 100;
        for ($i = 1; $i <= 30; $i++) {
            $email = "student_test_{$studentCounter}@test.com";
            $phone = "0999" . str_pad((string) $studentCounter, 6, '0', STR_PAD_LEFT);
            
            $quota = $quotas->random();
            $collaborator = $collaborators->random();
            
            Student::updateOrCreate(
                ['email' => $email],
                [
                    'full_name' => 'Student Test ' . $studentCounter,
                    'phone' => $phone,
                    'collaborator_id' => $collaborator->id,
                    'quota_id' => $quota->id,
                    'intake_id' => $quota->intake_id,
                    'target_university' => 'Test University',
                    'major' => $quota->major_name,
                    'program_type' => $quota->program_name,
                    'source' => 'other',
                    'status' => $this->getRandomStatus(['new', 'contacted', 'submitted', 'approved', 'enrolled']),
                    'created_at' => $startDate->copy()->addDays(rand(0, 30))->addHours(rand(0, 23)),
                ]
            );
            $studentCounter++;
        }

        // Tạo dữ liệu Payment & Commission
        $students = Student::all();
        foreach ($students->take(20) as $student) {
            $date = $startDate->copy()->addDays(rand(0, 30));
            
            $payment = Payment::updateOrCreate(
                ['student_id' => $student->id],
                [
                    'primary_collaborator_id' => $student->collaborator_id,
                    'sub_collaborator_id' => $student->collaborator_id,
                    'program_type' => $student->program_type ?? 'REGULAR',
                    'amount' => rand(5000000, 15000000),
                    'status' => 'verified',
                    'created_at' => $date,
                ]
            );

            $commission = \App\Models\Commission::updateOrCreate(
                ['payment_id' => $payment->id],
                [
                    'student_id' => $student->id,
                    'rule' => ['type' => 'percentage', 'value' => 10],
                ]
            );

            CommissionItem::updateOrCreate(
                [
                    'commission_id' => $commission->id,
                    'recipient_collaborator_id' => $student->collaborator_id,
                ],
                [
                    'role' => 'PRIMARY',
                    'amount' => rand(1000000, 2000000),
                    'status' => $this->getRandomStatus(['pending', 'payable', 'paid']),
                    'trigger' => 'ON_VERIFICATION',
                    'created_at' => $date,
                ]
            );
        }

        // Tạo dữ liệu WalletTransaction
        $wallets = Wallet::all();
        if ($wallets->isNotEmpty()) {
            foreach ($wallets as $wallet) {
                for ($j = 0; $j < 3; $j++) {
                    $date = $startDate->copy()->addDays(rand(0, 30));
                    $amount = rand(500000, 2000000);
                    $type = rand(0, 1) ? 'deposit' : 'withdrawal';

                    WalletTransaction::create([
                        'wallet_id' => $wallet->id,
                        'type' => $type,
                        'amount' => $amount,
                        'balance_before' => $wallet->balance,
                        'balance_after' => $type === 'deposit' ? $wallet->balance + $amount : $wallet->balance - $amount,
                        'description' => $type === 'deposit' ? 'Thu hoa hồng test' : 'Chi hoa hồng test',
                        'created_at' => $date,
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
