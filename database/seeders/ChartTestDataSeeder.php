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
        $organization = Organization::first();
        $quotas = Quota::where('organization_id', $organization?->id)->with('intake')->get();

        if (!$organization || $collaborators->isEmpty() || $quotas->isEmpty()) {
            $this->command->info('Cần có dữ liệu Organization, Collaborator, Quota/Intake trước khi chạy seeder này.');
            return;
        }

        // Tạo thêm một số student để tránh unique constraint
        $studentCounter = 2;
        for ($i = 2; $i <= 50; $i++) {
            Student::create([
                'full_name' => 'Student Test ' . $studentCounter,
                'phone' => '012345678' . (1000 + $studentCounter),
                'email' => 'student' . $studentCounter . '@test.com',
                'organization_id' => $organization->id,
                'collaborator_id' => $collaborators->random()->id,
                'target_university' => 'Test University',
                'major' => 'Computer Science',
                'intake_month' => 9,
                'program_type' => 'REGULAR',
                'source' => 'other',
                'status' => 'new',
            ]);
            $quota = $quotas->random();
            Student::where('email', 'student' . $studentCounter . '@test.com')->update([
                'quota_id' => $quota->id,
                'intake_id' => $quota->intake_id,
            ]);
            $studentCounter++;
        }

        // Mỗi học sinh chỉ một payment (unique student_id); mỗi CommissionItem cần commission + payment riêng
        $studentIds = Student::pluck('id')->all();
        $studentIdIndex = 0;
        $extraStudentCounter = 10000;
        $nextStudentId = function () use (
            &$studentIdIndex,
            &$extraStudentCounter,
            $studentIds,
            $organization,
            $collaborators,
            $quotas
        ): int {
            if ($studentIdIndex < count($studentIds)) {
                return $studentIds[$studentIdIndex++];
            }
            $extraStudentCounter++;
            $quota = $quotas->random();
            $ctv = $collaborators->first();
            $s = Student::create([
                'full_name' => 'Chart seed HS ' . $extraStudentCounter,
                'phone' => '0999' . str_pad((string) $extraStudentCounter, 7, '0', STR_PAD_LEFT),
                'email' => 'chart-seed-' . $extraStudentCounter . '@test.local',
                'organization_id' => $organization->id,
                'collaborator_id' => $ctv->id,
                'quota_id' => $quota->id,
                'intake_id' => $quota->intake_id,
                'target_university' => 'Test University',
                'major' => 'Computer Science',
                'intake_month' => 9,
                'program_type' => 'REGULAR',
                'source' => 'other',
                'status' => 'new',
            ]);
            return $s->id;
        };

        // Mỗi CommissionItem cần một commission riêng (unique commission_id + recipient_collaborator_id)
        for ($i = 0; $i < 30; $i++) {
            $date = $startDate->copy()->addDays($i);
            $count = rand(1, min(5, $collaborators->count()));
            $recipients = $collaborators->shuffle()->take($count);

            foreach ($recipients as $recipient) {
                $sid = $nextStudentId();
                $samplePayment = \App\Models\Payment::create([
                    'organization_id' => $organization->id,
                    'student_id' => $sid,
                    'primary_collaborator_id' => $recipient->id,
                    'sub_collaborator_id' => $recipient->id,
                    'program_type' => 'REGULAR',
                    'amount' => 10000000,
                    'status' => 'verified',
                    'created_at' => $date->copy()->addHours(rand(0, 23)),
                ]);

                $commission = \App\Models\Commission::create([
                    'student_id' => $sid,
                    'organization_id' => $organization->id,
                    'payment_id' => $samplePayment->id,
                    'rule' => json_encode(['type' => 'percentage', 'value' => 10]),
                ]);

                CommissionItem::create([
                    'commission_id' => $commission->id,
                    'recipient_collaborator_id' => $recipient->id,
                    'role' => rand(0, 1) ? 'PRIMARY' : 'SUB',
                    'amount' => rand(100000, 5000000),
                    'status' => $this->getRandomStatus(['pending', 'payable', 'paid']),
                    'trigger' => 'ON_ENROLLMENT',
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
                    'organization_id' => $organization->id,
                    'collaborator_id' => $collaborators->random()->id,
                    'target_university' => 'Test University',
                    'major' => 'Computer Science',
                    'intake_month' => 9,
                    'program_type' => 'REGULAR',
                    'source' => 'other',
                    'status' => $this->getRandomStatus(['new', 'contacted', 'submitted', 'approved', 'enrolled', 'rejected']),
                    'created_at' => $date->copy()->addHours(rand(0, 23)),
                ]);
                $quota = $quotas->random();
                Student::where('email', 'student' . $studentCounter . '@test.com')->update([
                    'quota_id' => $quota->id,
                    'intake_id' => $quota->intake_id,
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
