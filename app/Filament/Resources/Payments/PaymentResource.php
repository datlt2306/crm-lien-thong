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
use App\Models\Organization;
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
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        // Super admin, admin, organization_owner và accountant có thể xem payments
        if (in_array($user->role, ['super_admin', 'admin', 'organization_owner']) || self::isAccountant()) {
            return true;
        }

        // CTV có thể xem payments của mình
        if ($user->role === 'ctv') {
            return true;
        }

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
                    ->visible(fn(): bool => in_array(Auth::user()->role, ['super_admin', 'organization_owner']))
                    ->formatStateUsing(function ($record) {
                        // Chủ đơn vị thấy CTV cấp 1 (không có upline)
                        $studentCtv = $record->student->collaborator;
                        if ($studentCtv && $studentCtv->upline_id) {
                            // Nếu student CTV có upline, lấy upline (CTV cấp 1)
                            return $studentCtv->upline->full_name;
                        }
                        return $studentCtv ? $studentCtv->full_name : '—';
                    }),
                \Filament\Tables\Columns\TextColumn::make('student.collaborator.full_name')
                    ->label('Cộng tác viên')
                    ->searchable()
                    ->sortable()
                    ->visible(fn(): bool => Auth::user()->role === 'ctv' && self::isPrimaryCollaborator()),

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
                            ->disk('local')
                            ->directory('bills')
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

                        // Xóa bill cũ nếu có
                        if ($record->bill_path && Storage::disk('local')->exists($record->bill_path)) {
                            Storage::disk('local')->delete($record->bill_path);
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
                    ->requiresConfirmation()
                    ->modalHeading('Xác nhận thanh toán')
                    ->modalDescription('Xác nhận đã nhận tiền sinh viên nộp đúng hệ đã đăng ký. Hệ thống sẽ tự động tạo commission cho CTV.')
                    ->modalSubmitActionLabel('Xác nhận đã nhận tiền')
                    ->modalCancelActionLabel('Hủy')
                    ->visible(
                        fn(Payment $record): bool =>
                        $record->status === Payment::STATUS_SUBMITTED &&
                            in_array(Auth::user()->role, ['super_admin', 'organization_owner'])
                    )
                    ->action(function (Payment $record) {
                        $record->markAsVerified(\Illuminate\Support\Facades\Auth::id());

                        // Tạo commission
                        $commissionService = new \App\Services\CommissionService();
                        $commissionService->createCommissionFromPayment($record);

                        \Filament\Notifications\Notification::make()
                            ->title('Đã xác nhận thanh toán')
                            ->body('Commission đã được tạo tự động.')
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
            return $query;
        }

        // Super admin thấy tất cả
        if (in_array($user->role, ['super_admin', 'admin'])) {
            return $query;
        }

        // Chủ đơn vị thấy payments của tổ chức mình
        if ($user->role === 'organization_owner') {
            $org = Organization::where('organization_owner_id', $user->id)->first();
            if ($org) {
                return $query
                    ->where('organization_id', $org->id)
                    ->whereIn('status', [Payment::STATUS_SUBMITTED, Payment::STATUS_VERIFIED]); // Thấy bill đã nộp & đã xác nhận
            }
        }

        // Kế toán thấy các payment cần xác minh và đã xác nhận (để xác minh và upload phiếu thu)
        if (self::isAccountant()) {
            return $query->whereIn('status', [Payment::STATUS_SUBMITTED, Payment::STATUS_VERIFIED]);
        }

        // CTV thấy payments của mình và downline
        if ($user->role === 'ctv') {
            $collaborator = Collaborator::where('email', $user->email)->first();
            if ($collaborator) {
                // Lấy danh sách student IDs mà CTV này giới thiệu
                $studentIds = \App\Models\Student::where('collaborator_id', $collaborator->id)->pluck('id');

                // Lấy danh sách downline IDs
                $downlineIds = self::getDownlineIds($collaborator->id);
                $downlineStudentIds = \App\Models\Student::whereIn('collaborator_id', $downlineIds)->pluck('id');

                // Gộp tất cả student IDs
                $allStudentIds = $studentIds->merge($downlineStudentIds);

                return $query->whereIn('student_id', $allStudentIds);
            }
        }

        // Fallback: không thấy gì
        return $query->whereNull('id');
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
     * Lấy danh sách ID của tất cả downline trong nhánh
     */
    private static function getDownlineIds(int $collaboratorId): array {
        $downlineIds = [];

        // Lấy tất cả downline trực tiếp
        $directDownlines = Collaborator::where('upline_id', $collaboratorId)->get();

        foreach ($directDownlines as $downline) {
            $downlineIds[] = $downline->id;

            // Đệ quy lấy downline của downline
            $subDownlineIds = self::getDownlineIds($downline->id);
            $downlineIds = array_merge($downlineIds, $subDownlineIds);
        }

        return $downlineIds;
    }

    /**
     * Kiểm tra xem CTV hiện tại có phải là CTV cấp 1 (không có upline) không
     */
    private static function isPrimaryCollaborator(): bool {
        $user = Auth::user();
        if ($user->role !== 'ctv') {
            return false;
        }

        $collaborator = Collaborator::where('email', $user->email)->first();
        if (!$collaborator) {
            return false;
        }

        // CTV cấp 1 là CTV không có upline_id
        return $collaborator->upline_id === null;
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
