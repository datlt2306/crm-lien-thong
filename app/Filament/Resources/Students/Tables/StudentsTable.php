<?php

namespace App\Filament\Resources\Students\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Models\Student;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class StudentsTable {
    public static function configure(Table $table): Table {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->label('Họ và tên')
                    ->searchable(),
                TextColumn::make('contact')
                    ->label('Liên hệ')
                    ->state(fn($record) => $record)
                    ->formatStateUsing(function ($record) {
                        $phone = $record->phone ?: '';
                        $email = $record->email ?: '';
                        $lines = [];
                        if ($phone) {
                            $lines[] = '📞 ' . e($phone);
                        }
                        if ($email) {
                            $lines[] = '✉️ ' . e($email);
                        }
                        return implode('<br>', $lines) ?: '—';
                    })
                    ->html()
                    ->searchable(query: function ($query, $search) {
                        return $query->where(function ($q) use ($search) {
                            $q->where('phone', 'like', "%$search%")
                                ->orWhere('email', 'like', "%$search%");
                        });
                    }),
                TextColumn::make('dob')
                    ->label('Ngày sinh')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('major')
                    ->label('Ngành học')
                    ->searchable(),
                TextColumn::make('address')
                    ->label('Địa chỉ')
                    ->limit(50)
                    ->searchable(),
                TextColumn::make('intake_month')
                    ->label('Đợt tuyển')
                    ->formatStateUsing(fn($state) => $state ? "Tháng {$state}" : '—')
                    ->sortable(),
                TextColumn::make('program_type')
                    ->label('Hệ tuyển sinh')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'REGULAR' => 'Chính quy',
                        'PART_TIME' => 'Vừa học vừa làm',
                        default => '—'
                    })
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'REGULAR' => 'success',
                        'PART_TIME' => 'warning',
                        default => 'gray'
                    })
                    ->tooltip(fn($state) => match ($state) {
                        'REGULAR' => 'Hệ đào tạo chính quy, học tập toàn thời gian',
                        'PART_TIME' => 'Hệ vừa học vừa làm, linh hoạt thời gian',
                        default => ''
                    }),
                TextColumn::make('source')
                    ->label('Nguồn')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Tình trạng')
                    ->badge()
                    ->color(function (string $state): string {
                        return match ($state) {
                            Student::STATUS_NEW => 'gray',
                            Student::STATUS_CONTACTED => 'blue',
                            Student::STATUS_SUBMITTED => 'yellow',
                            Student::STATUS_APPROVED => 'orange',
                            Student::STATUS_ENROLLED => 'success',
                            Student::STATUS_REJECTED => 'danger',
                            Student::STATUS_DROPPED => 'warning',
                            default => 'gray',
                        };
                    })
                    ->formatStateUsing(function (string $state): string {
                        $statusOptions = Student::getStatusOptions();
                        return $statusOptions[$state] ?? $state;
                    })
                    ->tooltip(function (string $state): string {
                        $tooltips = [
                            Student::STATUS_NEW => 'Học viên mới đăng ký, chưa được xử lý',
                            Student::STATUS_CONTACTED => 'Đã liên hệ với học viên, đang tư vấn',
                            Student::STATUS_SUBMITTED => 'Học viên đã nộp tiền, đang chờ admin xác minh thanh toán',
                            Student::STATUS_APPROVED => 'Hồ sơ đã được duyệt, sẵn sàng nhập học',
                            Student::STATUS_ENROLLED => 'Học viên đã nhập học thành công',
                            Student::STATUS_REJECTED => 'Hồ sơ bị từ chối, không đủ điều kiện',
                            Student::STATUS_DROPPED => 'Học viên bỏ học, không tiếp tục',
                        ];

                        return $tooltips[$state] ?? '';
                    })
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Ngày cập nhật')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Chỉnh sửa')
                    ->icon('heroicon-o-pencil')
                    ->visible(fn() => in_array(Auth::user()->role, ['super_admin', 'organization_owner', 'ctv'])),
                Action::make('confirm_payment')
                    ->label('Xác nhận đã nộp tiền')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Xác nhận đã nộp tiền')
                    ->modalDescription('Xác nhận học viên đã nộp tiền. Hệ thống sẽ chuyển trạng thái thanh toán sang "Đã nộp (chờ xác minh)".')
                    ->modalSubmitActionLabel('Xác nhận')
                    ->modalCancelActionLabel('Hủy')
                    ->visible(
                        fn(Student $record): bool =>
                        $record->status !== Student::STATUS_ENROLLED &&
                            $record->status !== Student::STATUS_SUBMITTED &&
                            in_array(Auth::user()->role, ['super_admin', 'organization_owner', 'ctv'])
                    )
                    ->action(function (Student $record): void {
                        // Cập nhật payment record nếu có
                        $payment = $record->payment;
                        if ($payment) {
                            $payment->update([
                                'status' => Payment::STATUS_SUBMITTED,
                                'receipt_uploaded_by' => Auth::id(),
                                'receipt_uploaded_at' => now(),
                            ]);
                        } else {
                            // Tạo payment record mới
                            \App\Models\Payment::create([
                                'student_id' => $record->id,
                                'primary_collaborator_id' => $record->collaborator_id,
                                'organization_id' => $record->organization_id,
                                'program_type' => $record->program_type ?? 'REGULAR',
                                'amount' => 0, // Sẽ cập nhật sau
                                'status' => Payment::STATUS_SUBMITTED,
                                'receipt_uploaded_by' => Auth::id(),
                                'receipt_uploaded_at' => now(),
                            ]);
                        }

                        // Cập nhật trạng thái học viên sang "Đã nộp hồ sơ" (chờ xác minh)
                        $record->update([
                            'status' => Student::STATUS_SUBMITTED,
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Xác nhận thành công')
                            ->body('Học viên đã được xác nhận nộp tiền. Trạng thái: Đã nộp hồ sơ (chờ xác minh).')
                            ->success()
                            ->send();
                    }),
                Action::make('mark_enrolled')
                    ->label('Xác nhận SV nhập học')
                    ->icon('heroicon-o-academic-cap')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Xác nhận sinh viên nhập học')
                    ->modalDescription('Bạn có chắc chắn muốn đánh dấu sinh viên này đã nhập học? Hệ thống sẽ tự động cập nhật commission cho CTV cấp 2.')
                    ->modalSubmitActionLabel('Xác nhận')
                    ->modalCancelActionLabel('Hủy')
                    ->visible(
                        fn(Student $record): bool =>
                        $record->status !== Student::STATUS_ENROLLED &&
                            ($record->payment?->status === Payment::STATUS_VERIFIED) &&
                            in_array(Auth::user()->role, ['super_admin', 'organization_owner'])
                    )
                    ->action(function (Student $record) {
                        $record->update(['status' => Student::STATUS_ENROLLED]);

                        // Cập nhật commission khi student nhập học
                        $commissionService = new \App\Services\CommissionService();
                        $commissionService->updateCommissionsOnEnrollment($record);

                        \Filament\Notifications\Notification::make()
                            ->title('Đã xác nhận sinh viên nhập học')
                            ->body('Commission đã được cập nhật tự động.')
                            ->success()
                            ->send();
                    }),

                Action::make('mark_left_unit')
                    ->label('Sinh viên hủy đăng ký')
                    ->icon('heroicon-o-user-minus')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Xác nhận sinh viên hủy đăng ký')
                    ->modalDescription('Xác nhận sinh viên này đã hủy đăng ký. Hệ thống sẽ cập nhật trạng thái và bỏ liên kết CTV giới thiệu.')
                    ->modalSubmitActionLabel('Xác nhận')
                    ->modalCancelActionLabel('Hủy')
                    ->visible(
                        fn(Student $record): bool =>
                        $record->status !== Student::STATUS_ENROLLED &&
                            ($record->payment?->status === Payment::STATUS_VERIFIED) &&
                            in_array(Auth::user()->role, ['super_admin', 'organization_owner'])
                    )
                    ->action(function (Student $record) {
                        $record->update([
                            'status' => Student::STATUS_REJECTED,
                            'collaborator_id' => null,
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Hủy đăng ký')
                            ->body('Sinh viên đã được cập nhật trạng thái hủy đăng ký và bỏ liên kết CTV.')
                            ->success()
                            ->send();
                    }),


            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Xóa đã chọn')
                        ->modalHeading('Xóa học viên đã chọn')
                        ->modalDescription('Bạn có chắc chắn muốn xóa các học viên đã chọn? Hành động này không thể hoàn tác.')
                        ->modalSubmitActionLabel('Xóa')
                        ->modalCancelActionLabel('Hủy')
                        ->visible(fn() => in_array(Auth::user()->role, ['super_admin', 'organization_owner'])),
                ]),
            ]);
    }
}
