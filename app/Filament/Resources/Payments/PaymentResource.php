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
                Action::make('verify')
                    ->label('Xác nhận')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Xác nhận thanh toán')
                    ->modalDescription('Xác nhận đã nhận tiền và chọn hệ đào tạo cho sinh viên. Hệ thống sẽ tự động tạo commission cho CTV.')
                    ->modalSubmitActionLabel('Xác nhận')
                    ->modalCancelActionLabel('Hủy')
                    ->visible(fn(Payment $record): bool => $record->status === Payment::STATUS_SUBMITTED)
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
                        $record->markAsVerified(auth()->id());
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
