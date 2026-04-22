<?php

namespace App\Filament\Resources\Payments;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Models\Payment;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Models\Collaborator;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

class PaymentResource extends Resource {
    protected static ?string $model = Payment::class;
    protected static string|\UnitEnum|null $navigationGroup = 'Tài chính';
    protected static ?string $navigationLabel = 'Thanh toán';
    protected static ?int $navigationSort = 1;
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    public static function shouldRegisterNavigation(): bool {
        // Ẩn Payments khỏi navigation - chỉ giữ backend logic
        return false;
    }

    public static function form(Schema $schema): Schema {
        return $schema;
    }

    public static function table(Table $table): Table {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('student.full_name')
                    ->label('Sinh viên')
                    ->searchable()
                    ->sortable()
                    ->action(
                        \Filament\Actions\Action::make('view_student')
                            ->label('Xem thông tin sinh viên')
                            ->icon('heroicon-o-eye')
                            ->modalContent(function (Payment $record) {
                                $student = $record->student;
                                if (!$student) {
                                    return view('components.student-info', [
                                        'student' => null,
                                        'error' => 'Không tìm thấy thông tin sinh viên'
                                    ]);
                                }
                                return view('components.student-info-modal', [
                                    'student' => $student,
                                ]);
                            })
                            ->modalWidth('4xl')
                    ),

                \Filament\Tables\Columns\TextColumn::make('primaryCollaborator.full_name')
                    ->label('Cộng tác viên')
                    ->searchable()
                    ->sortable()
                    ->visible(fn(): bool => in_array(Auth::user()->role, ['super_admin']))
                    ->formatStateUsing(function ($record) {
                        $studentCtv = $record->student->collaborator;
                        return $studentCtv ? $studentCtv->full_name : '—';
                    }),
                \Filament\Tables\Columns\TextColumn::make('student.collaborator.full_name')
                    ->label('Cộng tác viên')
                    ->searchable()
                    ->sortable()
                    ->visible(fn(): bool => Auth::user()->role === 'ctv'),

                \Filament\Tables\Columns\TextColumn::make('program_type')
                    ->label('Hệ đào tạo')
                    ->badge()
                    ->color(fn(string $state): string => match (strtoupper($state)) {
                        'REGULAR' => 'success',
                        'PART_TIME' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match (strtoupper($state)) {
                        'REGULAR' => 'Chính quy',
                        'PART_TIME' => 'VHVLV',
                        default => $state,
                    }),

                \Filament\Tables\Columns\TextColumn::make('amount')
                    ->label('Số tiền')
                    ->money('VND')
                    ->sortable(),

                \Filament\Tables\Columns\BadgeColumn::make('status')
                    ->label('Trạng thái')
                    ->color(function (string $state): string {
                        return match ($state) {
                            Payment::STATUS_NOT_PAID => 'gray',
                            Payment::STATUS_SUBMITTED => 'warning',
                            Payment::STATUS_VERIFIED => 'success',
                            default => 'gray',
                        };
                    })
                    ->formatStateUsing(fn(string $state): string => Payment::getStatusOptions()[$state] ?? $state),

                \Filament\Tables\Columns\TextColumn::make('verified_at')
                    ->label('Xác nhận lúc')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('edited_at')
                    ->label('Chỉnh sửa lúc')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                \Filament\Tables\Columns\TextColumn::make('editedBy.name')
                    ->label('Người chỉnh sửa')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options(Payment::getStatusOptions()),

                \Filament\Tables\Filters\SelectFilter::make('program_type')
                    ->label('Loại chương trình')
                    ->options([
                        'REGULAR' => 'Chính quy',
                        'PART_TIME' => 'VHVLV',
                        'DISTANCE' => 'Đào tạo từ xa',
                    ]),
            ])
            ->actions([
                Action::make('upload_bill')
                    ->label('Upload Bill')
                    ->icon('heroicon-o-document-arrow-up')
                    ->color('info')
                    ->form([
                        \Filament\Forms\Components\FileUpload::make('bill')
                            ->label('Bill thanh toán')
                            ->acceptedFileTypes(['image/*', 'application/pdf'])
                            ->maxSize(5120) // 5MB
                            ->disk('google')
                            ->getUploadedFileNameForStorageUsing(function (\Livewire\Features\SupportFileUploads\TemporaryUploadedFile $file, Payment $record) {
                                return $record->generateStandardBillPath($file->getClientOriginalExtension());
                            })
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
                        fn(Payment $record): bool =>
                        Auth::user()->role === 'ctv' &&
                            $record->status === Payment::STATUS_NOT_PAID &&
                            self::canUploadBillForPayment($record)
                    )
                    ->action(function (array $data, Payment $record) {
                        // Tìm collaborator của user hiện tại
                        $collaborator = Collaborator::where('email', Auth::user()->email)->first();

                        if (!$collaborator) {
                            \Filament\Notifications\Notification::make()
                                ->title('Lỗi')
                                ->body('Không tìm thấy thông tin cộng tác viên.')
                                ->danger()
                                ->send();
                            return;
                        }

                        // Cập nhật payment record
                        $record->update([
                            'bill_path' => $data['bill'],
                            'amount' => $data['amount'],
                            'program_type' => $data['program_type'],
                            'status' => Payment::STATUS_SUBMITTED,
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Đã upload bill thành công')
                            ->body('Bill đã được gửi để xác minh.')
                            ->success()
                            ->send();
                    }),
                Action::make('edit_bill')
                    ->label('Chỉnh sửa Bill')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->form([
                        \Filament\Forms\Components\FileUpload::make('bill')
                            ->label('Bill thanh toán mới')
                            ->acceptedFileTypes(['image/*', 'application/pdf'])
                            ->maxSize(5120) // 5MB
                            ->disk('google')
                            ->getUploadedFileNameForStorageUsing(function (\Livewire\Features\SupportFileUploads\TemporaryUploadedFile $file, Payment $record) {
                                return $record->generateStandardBillPath($file->getClientOriginalExtension());
                            })
                            ->required()
                            ->helperText('Upload bill thanh toán mới (JPG, PNG, PDF, tối đa 5MB)'),
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
                        \Filament\Forms\Components\Textarea::make('edit_reason')
                            ->label('Lý do chỉnh sửa')
                            ->required()
                            ->rows(3)
                            ->helperText('Giải thích lý do cần chỉnh sửa bill'),
                    ])
                    ->fillForm(function (Payment $record): array {
                        return [
                            'amount' => $record->amount,
                            'program_type' => $record->program_type,
                        ];
                    })
                    ->visible(
                        fn(Payment $record): bool =>
                        Auth::user()->role === 'ctv' &&
                            $record->status === Payment::STATUS_SUBMITTED &&
                            !empty($record->bill_path) &&
                            self::canEditBillForPayment($record)
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Chỉnh sửa Bill')
                    ->modalDescription('Bạn có chắc chắn muốn chỉnh sửa bill này? Bill cũ sẽ được thay thế bằng bill mới.')
                    ->modalSubmitActionLabel('Cập nhật')
                    ->modalCancelActionLabel('Hủy')
                    ->action(function (array $data, Payment $record) {
                        // Tìm collaborator của user hiện tại
                        $collaborator = Collaborator::where('email', Auth::user()->email)->first();

                        if (!$collaborator) {
                            \Filament\Notifications\Notification::make()
                                ->title('Lỗi')
                                ->body('Không tìm thấy thông tin cộng tác viên.')
                                ->danger()
                                ->send();
                            return;
                        }

                        // Xóa bill cũ nếu có trên Drive
                        if ($record->bill_path && Storage::disk('google')->exists($record->bill_path)) {
                            Storage::disk('google')->delete($record->bill_path);
                        }

                        // Cập nhật payment record với bill mới
                        $record->update([
                            'bill_path' => $data['bill'],
                            'amount' => $data['amount'],
                            'program_type' => $data['program_type'],
                            'status' => Payment::STATUS_SUBMITTED,
                            'edit_reason' => $data['edit_reason'] ?? null,
                            'edited_at' => now(),
                            'edited_by' => Auth::id(),
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Đã cập nhật bill thành công')
                            ->body('Bill đã được chỉnh sửa và gửi lại để xác minh.')
                            ->success()
                            ->send();
                    }),
                Action::make('verify')
                    ->label('Xác nhận')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('receipt_number')
                            ->label('Số phiếu thu')
                            ->required()
                            ->helperText('Nhập số phiếu thu từ Helen'),
                        \Filament\Forms\Components\FileUpload::make('receipt')
                            ->label('File phiếu thu')
                            ->acceptedFileTypes(['image/*', 'application/pdf'])
                            ->maxSize(5120) // 5MB
                            ->disk('google')
                            ->getUploadedFileNameForStorageUsing(function (\Livewire\Features\SupportFileUploads\TemporaryUploadedFile $file, Payment $record) {
                                return $record->generateStandardReceiptPath($file->getClientOriginalExtension());
                            })
                            ->required()
                            ->helperText('Upload phiếu thu từ Helen (JPG, PNG, PDF, tối đa 5MB)'),
                    ])
                    ->visible(
                        fn(Payment $record): bool =>
                        $record->status === Payment::STATUS_SUBMITTED &&
                            in_array(Auth::user()->role, ['super_admin', 'accountant', 'document'])
                    )
                    ->action(function (array $data, Payment $record) {
                        \Illuminate\Support\Facades\DB::transaction(function () use ($data, $record) {
                            $record->update([
                                'receipt_number' => $data['receipt_number'] ?? null,
                                'receipt_path' => $data['receipt'] ?? null,
                                'receipt_uploaded_by' => Auth::id(),
                                'receipt_uploaded_at' => now(),
                            ]);

                            $record->markAsVerified(\Illuminate\Support\Facades\Auth::id());

                            // Tạo commission
                            $commissionService = new \App\Services\CommissionService();
                            $commissionService->createCommissionFromPayment($record);

                            // Gửi email thông báo cho sinh viên
                            if ($record->student && $record->student->email) {
                                try {
                                    \Illuminate\Support\Facades\Mail::to($record->student->email)
                                        ->send(new \App\Mail\PaymentVerified($record));
                                } catch (\Exception $e) {
                                    \Illuminate\Support\Facades\Log::error('Failed to send payment verification email: ' . $e->getMessage());
                                }
                            }
                        });

                        \Filament\Notifications\Notification::make()
                            ->title('Đã xác nhận thanh toán')
                            ->body('Commission đã được tạo và email phiếu thu đã được gửi cho sinh viên.')
                            ->success()
                            ->send();
                    }),
                Action::make('view_bill')
                    ->label('Xem Bill')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->modalHeading('Bill thanh toán')
                    ->modalContent(function (Payment $record) {
                        if (!$record->bill_path) {
                            return view('components.no-content', [
                                'message' => 'Không có bill để hiển thị.'
                            ]);
                        }

                        $fileUrl = route('files.bill.view', $record->id);
                        $fileName = basename($record->bill_path);

                        return view('components.bill-viewer', [
                            'fileUrl' => $fileUrl,
                            'fileName' => $fileName,
                            'payment' => $record
                        ]);
                    })
                    ->modalWidth('4xl')
                    ->visible(fn(Payment $record): bool => !empty($record->bill_path)),
                Action::make('view_receipt')
                    ->label('Xem Phiếu Thu')
                    ->icon('heroicon-o-document-check')
                    ->color('success')
                    ->modalHeading('Phiếu thu từ Helen')
                    ->modalContent(function (Payment $record) {
                        if (!$record->receipt_path) {
                            return view('components.no-content', [
                                'message' => 'Không có phiếu thu để hiển thị.'
                            ]);
                        }

                        // Ở đây chúng ta cần route file cho receipt. 
                        // Vì FileController hiện tại chỉ có files.bill.view, 
                        // tôi sẽ thêm một cái tương tự cho receipt.
                        $fileUrl = route('files.receipt.view', $record->id);
                        $fileName = basename($record->receipt_path);

                        return view('components.bill-viewer', [ // Reuse bill-viewer components if it's general
                            'fileUrl' => $fileUrl,
                            'fileName' => $fileName,
                            'payment' => $record
                        ]);
                    })
                    ->modalWidth('4xl')
                    ->visible(fn(Payment $record): bool => !empty($record->receipt_path)),

            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Xóa đã chọn')
                        ->modalHeading('Xóa thanh toán đã chọn')
                        ->modalDescription('Bạn có chắc chắn muốn xóa các thanh toán đã chọn? Hành động này không thể hoàn tác.')
                        ->modalSubmitActionLabel('Xóa')
                        ->modalCancelActionLabel('Hủy'),
                ]),
            ]);
    }

    public static function getPages(): array {
        return [
            'index' => ListPayments::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string {
        try {
            return (string) Payment::count();
        } catch (\Throwable) {
            return null;
        }
    }

    public static function getNavigationBadgeTooltip(): ?string {
        return 'Tổng số thanh toán';
    }

    public static function getEloquentQuery(): Builder {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if (!$user) {
            return $query->whereNull('payments.id');
        }

        // Super admin và Admin thấy tất cả
        if (in_array($user->role, ['super_admin', 'admin'])) {
            return $query;
        }

        // Nếu có quyền xem toàn bộ hệ thống
        if ($user->can('payment_view_all')) {
            return $query;
        }

        // Cán bộ văn phòng (Kế toán, Hồ sơ) thấy các payment cần xác minh hoặc đã xác nhận
        if ($user->can('payment_view_any') || $user->hasRole(['accountant', 'document'])) {
            return $query->whereIn('status', [Payment::STATUS_SUBMITTED, Payment::STATUS_VERIFIED]);
        }

        // CTV thấy payments của mình
        if ($user->hasRole('ctv')) {
            $collaborator = Collaborator::where('email', $user->email)->first();
            if ($collaborator) {
                return $query->whereIn('student_id', function ($q) use ($collaborator) {
                    $q->select('id')->from('students')->where('collaborator_id', $collaborator->id);
                });
            }
        }

        // Mặc định không thấy gì nếu không có quyền
        return $query->whereNull('payments.id');
    }

    /**
     * Kiểm tra xem CTV hiện tại có thể upload bill cho payment này không
     */
    private static function canUploadBillForPayment(Payment $payment): bool {
        $user = Auth::user();

        if ($user->role !== 'ctv') {
            return false;
        }

        $collaborator = Collaborator::where('email', $user->email)->first();
        if (!$collaborator) {
            return false;
        }

        // Chỉ CTV có ref_id trùng với collaborator_id của sinh viên mới được upload bill
        return $payment->student->collaborator_id === $collaborator->id;
    }

    /**
     * Kiểm tra xem CTV hiện tại có thể chỉnh sửa bill cho payment này không
     */
    private static function canEditBillForPayment(Payment $payment): bool {
        $user = Auth::user();

        if ($user->role !== 'ctv') {
            return false;
        }

        $collaborator = Collaborator::where('email', $user->email)->first();
        if (!$collaborator) {
            return false;
        }

        // Chỉ CTV có ref_id trùng với collaborator_id của sinh viên mới được chỉnh sửa bill
        return $payment->student->collaborator_id === $collaborator->id;
    }



    /**
     * Kiểm tra user hiện tại có Spatie role 'accountant' không
     */
    private static function isAccountant(): bool {
        $user = Auth::user();
        if (!$user) return false;
        /** @var User $user */
        return method_exists($user, 'hasRole') && $user->hasRole('accountant');
    }
}
