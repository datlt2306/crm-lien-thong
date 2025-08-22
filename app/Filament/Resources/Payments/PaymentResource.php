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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

class PaymentResource extends Resource {
    protected static ?string $model = Payment::class;
    protected static string|\UnitEnum|null $navigationGroup = 'Thanh toán & Hoa hồng';
    protected static ?string $navigationLabel = 'Thanh toán';
    protected static ?int $navigationSort = 1;
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    public static function shouldRegisterNavigation(): bool {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        // Super admin và chủ đơn vị có thể xem payments
        if (in_array($user->role, ['super_admin', 'chủ đơn vị'])) {
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
                    ->sortable(),

                \Filament\Tables\Columns\TextColumn::make('primaryCollaborator.full_name')
                    ->label('CTV cấp 1')
                    ->searchable()
                    ->sortable(),

                \Filament\Tables\Columns\TextColumn::make('program_type')
                    ->label('Loại chương trình')
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
                Action::make('create_payment')
                    ->label('Tạo Payment')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->form([
                        \Filament\Forms\Components\Select::make('student_id')
                            ->label('Học viên')
                            ->options(function () {
                                $user = Auth::user();
                                if ($user->role !== 'ctv') return [];

                                $collaborator = Collaborator::where('email', $user->email)->first();
                                if (!$collaborator) return [];

                                // Lấy học viên chưa có payment
                                $studentsWithPayment = Payment::pluck('student_id');
                                return \App\Models\Student::where('collaborator_id', $collaborator->id)
                                    ->whereNotIn('id', $studentsWithPayment)
                                    ->pluck('full_name', 'id')
                                    ->toArray();
                            })
                            ->required()
                            ->searchable()
                            ->helperText('Chọn học viên chưa có payment'),
                        \Filament\Forms\Components\TextInput::make('amount')
                            ->label('Số tiền')
                            ->numeric()
                            ->required()
                            ->helperText('Nhập số tiền dự kiến'),
                        \Filament\Forms\Components\Select::make('program_type')
                            ->label('Hệ liên thông')
                            ->options([
                                'REGULAR' => 'Chính quy',
                                'PART_TIME' => 'Vừa học vừa làm',
                            ])
                            ->required()
                            ->helperText('Chọn hệ liên thông của sinh viên'),
                    ])
                    ->visible(fn(): bool => Auth::user()->role === 'ctv')
                    ->action(function (array $data) {
                        $user = Auth::user();
                        $collaborator = Collaborator::where('email', $user->email)->first();

                        if (!$collaborator) {
                            \Filament\Notifications\Notification::make()
                                ->title('Lỗi')
                                ->body('Không tìm thấy thông tin cộng tác viên.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $student = \App\Models\Student::find($data['student_id']);
                        if (!$student) {
                            \Filament\Notifications\Notification::make()
                                ->title('Lỗi')
                                ->body('Không tìm thấy thông tin học viên.')
                                ->danger()
                                ->send();
                            return;
                        }

                        // Tạo payment record
                        Payment::create([
                            'organization_id' => $student->organization_id,
                            'student_id' => $student->id,
                            'primary_collaborator_id' => $collaborator->id,
                            'sub_collaborator_id' => $collaborator->upline_id,
                            'program_type' => $data['program_type'],
                            'amount' => $data['amount'],
                            'status' => Payment::STATUS_NOT_PAID,
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Đã tạo payment thành công')
                            ->body('Bạn có thể upload bill cho payment này.')
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
                Action::make('verify')
                    ->label('Xác nhận')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Xác nhận thanh toán')
                    ->modalDescription('Xác nhận đã nhận tiền và chọn hệ đào tạo cho sinh viên. Hệ thống sẽ tự động tạo commission cho CTV.')
                    ->modalSubmitActionLabel('Xác nhận')
                    ->modalCancelActionLabel('Hủy')
                    ->visible(
                        fn(Payment $record): bool =>
                        $record->status === Payment::STATUS_SUBMITTED &&
                            in_array(Auth::user()->role, ['super_admin', 'chủ đơn vị'])
                    )
                    ->form([
                        \Filament\Forms\Components\Select::make('program_type')
                            ->label('Hệ đào tạo')
                            ->options([
                                'REGULAR' => 'Chính quy',
                                'PART_TIME' => 'VHVLV',
                            ])
                            ->required(),
                    ])
                    ->fillForm(fn(Payment $record): array => [
                        'program_type' => strtoupper($record->program_type),
                    ])
                    ->action(function (Payment $record, array $data) {
                        $record->markAsVerified(\Illuminate\Support\Facades\Auth::id());
                        $record->update([
                            'program_type' => $data['program_type'],
                        ]);

                        // Tạo commission
                        $commissionService = new \App\Services\CommissionService();
                        $commissionService->createCommissionFromPayment($record);

                        \Filament\Notifications\Notification::make()
                            ->title('Đã xác nhận thanh toán')
                            ->body('Commission đã được tạo tự động theo hệ đào tạo đã chọn.')
                            ->success()
                            ->send();
                    }),
                Action::make('view_bill')
                    ->label('Xem Bill')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->url(fn(Payment $record) => $record->bill_path ? route('files.bill.view', $record->id) : '#')
                    ->openUrlInNewTab()
                    ->visible(fn(Payment $record): bool => $record->bill_path),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array {
        return [
            'index' => ListPayments::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if (!$user) {
            return $query;
        }

        // Super admin thấy tất cả
        if ($user->role === 'super_admin') {
            return $query;
        }

        // Chủ đơn vị thấy payments của tổ chức mình
        if ($user->role === 'chủ đơn vị') {
            $org = Organization::where('owner_id', $user->id)->first();
            if ($org) {
                return $query->where('organization_id', $org->id);
            }
        }

        // CTV thấy payments của mình và có thể tạo payment mới cho học viên chưa có payment
        if ($user->role === 'ctv') {
            $collaborator = Collaborator::where('email', $user->email)->first();
            if ($collaborator) {
                // Lấy danh sách student IDs mà CTV này giới thiệu
                $studentIds = \App\Models\Student::where('collaborator_id', $collaborator->id)->pluck('id');

                // Thấy payments của mình và students chưa có payment
                return $query->where(function ($q) use ($collaborator, $studentIds) {
                    $q->where('primary_collaborator_id', $collaborator->id)
                        ->orWhereIn('student_id', $studentIds);
                });
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

        // Chỉ CTV trực tiếp giới thiệu học viên mới được upload bill
        return $payment->primary_collaborator_id === $collaborator->id;
    }
}
