<?php

namespace App\Filament\Resources\DownlineCommissions;

use App\Filament\Resources\DownlineCommissions\Pages\ListDownlineCommissions;
use App\Models\CommissionItem;
use App\Models\Collaborator;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Illuminate\Support\Facades\Auth;

class DownlineCommissionResource extends Resource {
    protected static ?string $model = CommissionItem::class;
    protected static string|\UnitEnum|null $navigationGroup = 'Tài chính';
    protected static ?string $navigationLabel = 'Chia hoa hồng nội bộ';
    protected static ?int $navigationSort = 3;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-share';

    public static function shouldRegisterNavigation(): bool {
        $user = Auth::user();

        if (!$user || $user->role !== 'ctv') {
            return false;
        }

        // Kiểm tra xem CTV có downline không
        $collaborator = Collaborator::where('email', $user->email)->first();
        if (!$collaborator) {
            return false;
        }

        // Kiểm tra xem có CTV cấp dưới nào không
        $hasDownline = Collaborator::where('upline_id', $collaborator->id)->exists();

        return $hasDownline;
    }

    public static function form(Schema $schema): Schema {
        return $schema;
    }

    public static function table(Table $table): Table {
        $user = Auth::user();
        $collaborator = Collaborator::where('email', $user->email)->first();

        return $table
            ->query(
                CommissionItem::query()
                    ->where('role', 'downline')
                    ->whereHas('recipient', function ($query) use ($collaborator) {
                        // Chỉ lấy commission của các CTV cấp dưới của CTV hiện tại
                        if ($collaborator) {
                            $query->where('upline_id', $collaborator->id);
                        }
                    })
            )
            ->columns([
                TextColumn::make('commission.student.full_name')
                    ->label('Sinh viên')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('commission.student.phone')
                    ->label('SĐT sinh viên')
                    ->searchable(),

                TextColumn::make('commission.payment.amount')
                    ->label('Số tiền')
                    ->money('VND')
                    ->sortable(),

                TextColumn::make('amount')
                    ->label('Hoa hồng được chia')
                    ->money('VND')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            \App\Models\CommissionItem::STATUS_PENDING => 'Chờ nhập học',
                            \App\Models\CommissionItem::STATUS_PAYABLE => 'Có thể thanh toán',
                            \App\Models\CommissionItem::STATUS_PAID => 'Đã chuyển',
                            \App\Models\CommissionItem::STATUS_PAYMENT_CONFIRMED => 'Đã xác nhận thanh toán',
                            \App\Models\CommissionItem::STATUS_RECEIVED_CONFIRMED => 'Đã nhận',
                            \App\Models\CommissionItem::STATUS_CANCELLED => 'Đã huỷ',
                            default => $state,
                        };
                    })
                    ->color(function ($state) {
                        return match ($state) {
                            \App\Models\CommissionItem::STATUS_PENDING => 'gray',
                            \App\Models\CommissionItem::STATUS_PAYABLE => 'warning',
                            \App\Models\CommissionItem::STATUS_PAID => 'info',
                            \App\Models\CommissionItem::STATUS_PAYMENT_CONFIRMED => 'primary',
                            \App\Models\CommissionItem::STATUS_RECEIVED_CONFIRMED => 'success',
                            \App\Models\CommissionItem::STATUS_CANCELLED => 'danger',
                            default => 'gray',
                        };
                    }),

                TextColumn::make('recipient.full_name')
                    ->label('CTV cấp dưới')
                    ->searchable(),

                TextColumn::make('payment_bill_path')
                    ->label('Bill')
                    ->formatStateUsing(fn($state) => $state ? '✓ Đã có' : '—')
                    ->badge()
                    ->color(fn($state) => $state ? 'success' : 'gray'),

                TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('view_student')
                        ->label('Xem sinh viên')
                        ->icon('heroicon-o-eye')
                        ->url(
                            fn(CommissionItem $record) =>
                            \App\Filament\Resources\Students\StudentResource::getUrl('edit', ['record' => $record->commission->student_id])
                        ),

                    Action::make('view_bill')
                        ->label('Xem Bill')
                        ->icon('heroicon-o-document-text')
                        ->color('gray')
                        ->modalContent(function (CommissionItem $record) {
                            return view('components.commission-bill-viewer', [
                                'commissionItem' => $record,
                            ]);
                        })
                        ->modalWidth('4xl')
                        ->visible(fn(CommissionItem $record) => !empty($record->payment_bill_path)),

                    Action::make('confirm_downline_received')
                        ->label('Xác nhận CTV cấp dưới đã nhận')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Xác nhận CTV cấp dưới đã nhận tiền')
                        ->modalDescription('Xác nhận CTV cấp dưới đã nhận tiền hoa hồng từ bạn.')
                        ->visible(function (CommissionItem $record) use ($collaborator) {
                            // Chỉ hiển thị khi item là downline và đã được chuyển tiền (STATUS_PAID)
                            return $record->role === 'downline'
                                && $record->status === \App\Models\CommissionItem::STATUS_PAID
                                && $record->recipient?->upline_id === $collaborator->id;
                        })
                        ->action(function (CommissionItem $record) {
                            $service = new \App\Services\CommissionService();
                            $service->confirmDownlineReceived($record, Auth::user()->id);

                            \Filament\Notifications\Notification::make()
                                ->title('Đã xác nhận')
                                ->body('Đã xác nhận CTV cấp dưới nhận tiền.')
                                ->success()
                                ->send();
                        }),
                ])
                    ->label('Hành động')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('gray')
                    ->button()
                    ->size('sm')
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array {
        return [];
    }

    public static function getPages(): array {
        return [
            'index' => ListDownlineCommissions::route('/'),
        ];
    }
}
