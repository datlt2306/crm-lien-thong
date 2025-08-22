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
                TextColumn::make('collaborator.full_name')
                    ->label('Người giới thiệu')
                    ->searchable()
                    ->description(fn($record) => $record->collaborator?->email)
                    ->badge()
                    ->color('info')
                    ->placeholder('Không có'),
                TextColumn::make('target_university')
                    ->label('Trường muốn học')
                    ->searchable(),
                TextColumn::make('major')
                    ->label('Ngành học')
                    ->searchable(),
                TextColumn::make('dob')
                    ->label('Ngày sinh')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('intake_month')
                    ->label('Đợt tuyển')
                    ->formatStateUsing(fn($state) => $state ? "Tháng {$state}" : '—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('program_type')
                    ->label('Hệ liên thông')
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
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('address')
                    ->label('Địa chỉ')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
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
                            default => 'gray',
                        };
                    })
                    ->formatStateUsing(fn(string $state): string => Student::getStatusOptions()[$state] ?? $state)
                    ->searchable(),
                TextColumn::make('payment.status')
                    ->label('Trạng thái thanh toán')
                    ->badge()
                    ->color(function ($state) {
                        return match ($state) {
                            \App\Models\Payment::STATUS_NOT_PAID => 'gray',
                            \App\Models\Payment::STATUS_SUBMITTED => 'warning',
                            \App\Models\Payment::STATUS_VERIFIED => 'success',
                            default => 'gray',
                        };
                    })
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            \App\Models\Payment::STATUS_NOT_PAID => 'Chưa thanh toán',
                            \App\Models\Payment::STATUS_SUBMITTED => 'Đã nộp (chờ xác minh)',
                            \App\Models\Payment::STATUS_VERIFIED => 'Đã xác nhận',
                            default => 'Chưa thanh toán',
                        };
                    })
                    ->placeholder('Chưa thanh toán')
                    ->toggleable(isToggledHiddenByDefault: true),
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
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn() => in_array(Auth::user()->role, ['super_admin', 'chủ đơn vị'])),
                Action::make('mark_enrolled')
                    ->label('Đánh dấu nhập học')
                    ->icon('heroicon-o-academic-cap')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Đánh dấu nhập học')
                    ->modalDescription('Bạn có chắc chắn muốn đánh dấu sinh viên này đã nhập học? Hệ thống sẽ tự động cập nhật commission cho CTV cấp 2.')
                    ->modalSubmitActionLabel('Xác nhận')
                    ->modalCancelActionLabel('Hủy')
                    ->visible(
                        fn(Student $record): bool =>
                        $record->status !== Student::STATUS_ENROLLED &&
                            in_array(Auth::user()->role, ['super_admin', 'chủ đơn vị'])
                    )
                    ->action(function (Student $record) {
                        $record->update(['status' => Student::STATUS_ENROLLED]);

                        // Cập nhật commission khi student nhập học
                        $commissionService = new \App\Services\CommissionService();
                        $commissionService->updateCommissionsOnEnrollment($record);

                        \Filament\Notifications\Notification::make()
                            ->title('Đã đánh dấu nhập học')
                            ->body('Commission đã được cập nhật tự động.')
                            ->success()
                            ->send();
                    }),
                Action::make('upload_bill')
                    ->label('Upload Bill')
                    ->icon('heroicon-o-document-arrow-up')
                    ->color('info')
                    ->form([
                        \Filament\Forms\Components\FileUpload::make('bill')
                            ->label('Bill thanh toán')
                            ->acceptedFileTypes(['image/*', 'application/pdf'])
                            ->maxSize(5120) // 5MB
                            ->disk('local')
                            ->directory('bills')
                            ->required()
                            ->helperText('Upload bill thanh toán (JPG, PNG, PDF, tối đa 5MB)'),
                        \Filament\Forms\Components\TextInput::make('amount')
                            ->label('Số tiền')
                            ->numeric()
                            ->required()
                            ->helperText('Nhập số tiền đã thanh toán'),
                        \Filament\Forms\Components\Select::make('program_type')
                            ->label('Hệ liên thông')
                            ->options([
                                'REGULAR' => 'Chính quy',
                                'PART_TIME' => 'Vừa học vừa làm',
                            ])
                            ->required()
                            ->helperText('Chọn hệ liên thông của sinh viên'),
                    ])
                    ->visible(
                        fn(Student $record): bool =>
                        Auth::user()->role === 'ctv' &&
                            !$record->payment && // Chưa có payment
                            self::canUploadBillForStudent($record) // Có quyền upload bill cho student này
                    )
                    ->action(function (array $data, Student $record) {
                        // Tìm collaborator của user hiện tại
                        $collaborator = \App\Models\Collaborator::where('email', Auth::user()->email)->first();

                        if (!$collaborator) {
                            \Filament\Notifications\Notification::make()
                                ->title('Lỗi')
                                ->body('Không tìm thấy thông tin cộng tác viên.')
                                ->danger()
                                ->send();
                            return;
                        }

                        // CTV upload bill cho học viên của mình
                        $primaryCollaboratorId = $collaborator->id; // CTV trực tiếp giới thiệu học viên
                        $subCollaboratorId = $collaborator->upline_id; // CTV cấp trên

                        // Tạo payment record
                        \App\Models\Payment::create([
                            'organization_id' => $record->organization_id,
                            'student_id' => $record->id,
                            'primary_collaborator_id' => $primaryCollaboratorId,
                            'sub_collaborator_id' => $subCollaboratorId,
                            'program_type' => $data['program_type'],
                            'amount' => $data['amount'],
                            'bill_path' => $data['bill'],
                            'status' => \App\Models\Payment::STATUS_SUBMITTED,
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Đã upload bill thành công')
                            ->body('Bill đã được gửi để xác minh.')
                            ->success()
                            ->send();
                    }),
                Action::make('view_bill')
                    ->label('Xem Bill')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->url(fn(Student $record) => $record->payment?->id ? route('files.bill.view', $record->payment->id) : '#')
                    ->openUrlInNewTab()
                    ->visible(
                        fn(Student $record): bool =>
                        $record->payment && $record->payment->bill_path
                    ),
                Action::make('verify_payment')
                    ->label('Xác nhận thanh toán')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Xác nhận thanh toán')
                    ->modalDescription('Bạn có chắc chắn muốn xác nhận thanh toán này?')
                    ->modalSubmitActionLabel('Xác nhận')
                    ->modalCancelActionLabel('Hủy')
                    ->visible(
                        fn(Student $record): bool =>
                        in_array(Auth::user()->role, ['super_admin', 'chủ đơn vị']) &&
                            $record->payment &&
                            $record->payment->status === \App\Models\Payment::STATUS_SUBMITTED
                    )
                    ->action(function (Student $record) {
                        $record->payment->markAsVerified(Auth::id());

                        \Filament\Notifications\Notification::make()
                            ->title('Đã xác nhận thanh toán')
                            ->body('Thanh toán đã được xác nhận thành công.')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn() => in_array(Auth::user()->role, ['super_admin', 'chủ đơn vị'])),
                ]),
            ]);
    }

    /**
     * Kiểm tra xem CTV hiện tại có thể upload bill cho student này không
     */
    private static function canUploadBillForStudent(Student $student): bool {
        $user = Auth::user();

        if ($user->role !== 'ctv') {
            return false;
        }

        $collaborator = \App\Models\Collaborator::where('email', $user->email)->first();
        if (!$collaborator) {
            return false;
        }

        // Chỉ CTV trực tiếp giới thiệu học viên mới được upload bill
        return $student->collaborator_id === $collaborator->id;
    }

    /**
     * Lấy danh sách ID của tất cả downline trong nhánh
     */
    private static function getDownlineIds(int $collaboratorId): array {
        $downlineIds = [];

        // Lấy tất cả downline trực tiếp
        $directDownlines = \App\Models\Collaborator::where('upline_id', $collaboratorId)->get();

        foreach ($directDownlines as $downline) {
            $downlineIds[] = $downline->id;

            // Đệ quy lấy downline của downline
            $subDownlineIds = self::getDownlineIds($downline->id);
            $downlineIds = array_merge($downlineIds, $subDownlineIds);
        }

        return $downlineIds;
    }
}
