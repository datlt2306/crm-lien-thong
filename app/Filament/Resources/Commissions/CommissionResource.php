<?php

namespace App\Filament\Resources\Commissions;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Models\Commission;
use App\Models\CommissionItem;
use App\Filament\Resources\Commissions\Pages\ListCommissions;

class CommissionResource extends Resource {
    protected static ?string $model = Commission::class;
    protected static string|\UnitEnum|null $navigationGroup = 'Finance';
    protected static ?string $navigationLabel = 'Hoa hồng';
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    public static function shouldRegisterNavigation(): bool {
        return Gate::allows('view_finance');
    }

    public static function form(Schema $schema): Schema {
        return $schema;
    }

    public static function table(Table $table): Table {
        return $table
            ->query(CommissionItem::query())
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('commission.student.full_name')
                    ->label('Sinh viên')
                    ->searchable()
                    ->sortable(),

                \Filament\Tables\Columns\TextColumn::make('recipient.full_name')
                    ->label('CTV nhận hoa hồng')
                    ->searchable()
                    ->sortable(),

                \Filament\Tables\Columns\TextColumn::make('role')
                    ->label('Vai trò')
                    ->badge()
                    ->color(fn(string $state): string => match (strtoupper($state)) {
                        'PRIMARY' => 'success',
                        'SUB' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match (strtoupper($state)) {
                        'PRIMARY' => 'CTV cấp 1',
                        'SUB' => 'CTV cấp 2',
                        default => $state,
                    }),

                \Filament\Tables\Columns\TextColumn::make('amount')
                    ->label('Số tiền hoa hồng')
                    ->money('VND')
                    ->sortable(),

                \Filament\Tables\Columns\BadgeColumn::make('status')
                    ->label('Trạng thái')
                    ->color(function (string $state): string {
                        return match ($state) {
                            CommissionItem::STATUS_PENDING => 'gray',
                            CommissionItem::STATUS_PAYABLE => 'warning',
                            CommissionItem::STATUS_PAID => 'success',
                            CommissionItem::STATUS_CANCELLED => 'danger',
                            default => 'gray',
                        };
                    })
                    ->formatStateUsing(fn (string $state): string => CommissionItem::getStatusOptions()[$state] ?? $state),

                \Filament\Tables\Columns\TextColumn::make('trigger')
                    ->label('Điều kiện kích hoạt')
                    ->badge()
                    ->color(fn(string $state): string => match (strtoupper($state)) {
                        'ON_VERIFICATION' => 'blue',
                        'ON_ENROLLMENT' => 'green',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match (strtoupper($state)) {
                        'ON_VERIFICATION' => 'Khi xác nhận thanh toán',
                        'ON_ENROLLMENT' => 'Khi nhập học',
                        default => $state,
                    }),

                \Filament\Tables\Columns\TextColumn::make('payable_at')
                    ->label('Có thể thanh toán từ')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                \Filament\Tables\Columns\TextColumn::make('paid_at')
                    ->label('Đã thanh toán lúc')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options(CommissionItem::getStatusOptions()),

                \Filament\Tables\Filters\SelectFilter::make('role')
                    ->label('Vai trò')
                    ->options([
                        'PRIMARY' => 'CTV cấp 1',
                        'SUB' => 'CTV cấp 2',
                    ]),

                \Filament\Tables\Filters\SelectFilter::make('trigger')
                    ->label('Điều kiện kích hoạt')
                    ->options([
                        'ON_VERIFICATION' => 'Khi xác nhận thanh toán',
                        'ON_ENROLLMENT' => 'Khi nhập học',
                    ]),
            ])
            ->actions([
                Action::make('mark_payable')
                    ->label('Đánh dấu có thể thanh toán')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Đánh dấu có thể thanh toán')
                    ->modalDescription('Đánh dấu commission này đã đến hạn chi, CTV có thể nhận.')
                    ->modalSubmitActionLabel('Xác nhận')
                    ->modalCancelActionLabel('Hủy')
                    ->visible(fn(CommissionItem $record): bool => $record->status === CommissionItem::STATUS_PENDING)
                    ->action(function (CommissionItem $record) {
                        $record->markAsPayable();

                        \Filament\Notifications\Notification::make()
                            ->title('Đã đánh dấu có thể thanh toán')
                            ->body('CTV có thể nhận hoa hồng này.')
                            ->success()
                            ->send();
                    }),

                Action::make('mark_paid')
                    ->label('Đánh dấu đã thanh toán')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Đánh dấu đã thanh toán')
                    ->modalDescription('Đánh dấu đã chi trả hoa hồng cho CTV (ghi nhận bằng tay, đính bill).')
                    ->modalSubmitActionLabel('Xác nhận')
                    ->modalCancelActionLabel('Hủy')
                    ->visible(fn(CommissionItem $record): bool => in_array($record->status, [CommissionItem::STATUS_PAYABLE, CommissionItem::STATUS_PENDING]))
                    ->action(function (CommissionItem $record) {
                        $record->markAsPaid();

                        \Filament\Notifications\Notification::make()
                            ->title('Đã đánh dấu thanh toán')
                            ->body('Hoa hồng đã được chi trả.')
                            ->success()
                            ->send();
                    }),

                Action::make('mark_cancelled')
                    ->label('Đánh dấu đã huỷ')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Đánh dấu đã huỷ')
                    ->modalDescription('Đánh dấu huỷ hoa hồng này (VD: SV không nhập học).')
                    ->modalSubmitActionLabel('Xác nhận')
                    ->modalCancelActionLabel('Hủy')
                    ->visible(fn(CommissionItem $record): bool => in_array($record->status, [CommissionItem::STATUS_PENDING, CommissionItem::STATUS_PAYABLE]))
                    ->action(function (CommissionItem $record) {
                        $record->markAsCancelled();

                        \Filament\Notifications\Notification::make()
                            ->title('Đã đánh dấu huỷ')
                            ->body('Hoa hồng đã được huỷ.')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array {
        return [
            'index' => ListCommissions::route('/'),
        ];
    }
}
