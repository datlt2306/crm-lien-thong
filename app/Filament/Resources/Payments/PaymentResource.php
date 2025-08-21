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

class PaymentResource extends Resource {
    protected static ?string $model = Payment::class;
    protected static string|\UnitEnum|null $navigationGroup = 'Finance';
    protected static ?string $navigationLabel = 'Thanh toán';
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    public static function shouldRegisterNavigation(): bool {
        return Gate::allows('view_finance');
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
                    ->color(fn(string $state): string => match ($state) {
                        'cq' => 'success',
                        'vhvlv' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'cq' => 'Chính quy',
                        'vhvlv' => 'VHVLV',
                        default => $state,
                    }),

                \Filament\Tables\Columns\TextColumn::make('amount')
                    ->label('Số tiền')
                    ->money('VND')
                    ->sortable(),

                \Filament\Tables\Columns\BadgeColumn::make('status')
                    ->label('Trạng thái')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'verified',
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'pending' => 'Chờ xác nhận',
                        'verified' => 'Đã xác nhận',
                        'rejected' => 'Từ chối',
                        default => $state,
                    }),

                \Filament\Tables\Columns\TextColumn::make('verified_at')
                    ->label('Xác nhận lúc')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options([
                        'pending' => 'Chờ xác nhận',
                        'verified' => 'Đã xác nhận',
                        'rejected' => 'Từ chối',
                    ]),

                \Filament\Tables\Filters\SelectFilter::make('program_type')
                    ->label('Loại chương trình')
                    ->options([
                        'cq' => 'Chính quy',
                        'vhvlv' => 'VHVLV',
                    ]),
            ])
            ->actions([
                Action::make('verify')
                    ->label('Xác nhận')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Xác nhận thanh toán')
                    ->modalDescription('Bạn có chắc chắn muốn xác nhận thanh toán này? Hệ thống sẽ tự động tạo commission cho CTV.')
                    ->modalSubmitActionLabel('Xác nhận')
                    ->modalCancelActionLabel('Hủy')
                    ->visible(fn(Payment $record): bool => $record->status === 'pending')
                    ->action(function (Payment $record) {
                        $record->update([
                            'status' => 'verified',
                            'verified_by' => auth()->id(),
                            'verified_at' => now(),
                        ]);

                        // Tạo commission
                        $commissionService = new \App\Services\CommissionService();
                        $commissionService->createCommissionFromPayment($record);

                        \Filament\Notifications\Notification::make()
                            ->title('Đã xác nhận thanh toán')
                            ->body('Commission đã được tạo tự động.')
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
            'index' => ListPayments::route('/'),
        ];
    }
}
