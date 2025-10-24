<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class SimplePaymentsTable extends BaseWidget {
    protected static ?string $heading = 'Thanh toán gần đây';
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table {
        // Tăng memory limit và execution time
        ini_set('memory_limit', '256M');
        set_time_limit(10);

        return $table
            ->query(
                Payment::query()
                    ->whereIn('status', ['submitted', 'verified'])
                    ->latest('created_at')
                    ->limit(20)
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Số tiền')
                    ->money('VND', true)
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Trạng thái')
                    ->colors([
                        'warning' => 'submitted',
                        'success' => 'verified',
                        'gray' => 'not_paid',
                    ])
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            'submitted' => 'Chờ xác nhận',
                            'verified' => 'Đã xác nhận',
                            'not_paid' => 'Chưa thanh toán',
                            default => $state
                        };
                    }),
                Tables\Columns\TextColumn::make('receipt_path')
                    ->label('Có bill')
                    ->formatStateUsing(function ($state) {
                        return !empty($state) ? '✅ Có' : '❌ Chưa có';
                    })
                    ->color(fn($state) => !empty($state) ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('student.full_name')
                    ->label('Học viên')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tạo lúc')
                    ->since()
                    ->sortable(),
            ])
            ->actions([
                Action::make('verify')
                    ->label('Xác nhận')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Xác nhận thanh toán')
                    ->modalDescription('Bạn có chắc chắn muốn xác nhận thanh toán này?')
                    ->modalSubmitActionLabel('Xác nhận')
                    ->modalCancelActionLabel('Hủy')
                    ->visible(fn (Payment $record): bool => $record->status === Payment::STATUS_SUBMITTED)
                    ->action(function (Payment $record): void {
                        $record->markAsVerified(Auth::id());
                        
                        Notification::make()
                            ->title('Xác nhận thành công')
                            ->body('Thanh toán đã được xác nhận.')
                            ->success()
                            ->send();
                    }),
                Action::make('reject')
                    ->label('Từ chối')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Từ chối thanh toán')
                    ->modalDescription('Bạn có chắc chắn muốn từ chối thanh toán này?')
                    ->modalSubmitActionLabel('Từ chối')
                    ->modalCancelActionLabel('Hủy')
                    ->visible(fn (Payment $record): bool => $record->status === Payment::STATUS_SUBMITTED)
                    ->action(function (Payment $record): void {
                        $record->update(['status' => Payment::STATUS_NOT_PAID]);
                        
                        Notification::make()
                            ->title('Đã từ chối')
                            ->body('Thanh toán đã bị từ chối.')
                            ->warning()
                            ->send();
                    }),
                Action::make('view')
                    ->label('Xem chi tiết')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading('Chi tiết thanh toán')
                    ->modalWidth('2xl')
                    ->modalContent(function (Payment $record) {
                        $student = $record->student?->full_name ?? '—';
                        $amount = number_format((float) $record->amount, 0, '.', ',') . ' ₫';
                        $status = Payment::getStatusOptions()[$record->status] ?? $record->status;
                        $created = optional($record->created_at)->format('d/m/Y H:i');

                        return view('components.payment-detail-modal', [
                            'student' => $student,
                            'amount' => $amount,
                            'status' => $status,
                            'record' => $record,
                            'created' => $created
                        ]);
                    }),
            ])
            ->defaultPaginationPageOption(10)
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options([
                        'submitted' => 'Chờ xác nhận',
                        'verified' => 'Đã xác nhận',
                    ]),
                Tables\Filters\TernaryFilter::make('receipt_path')
                    ->label('Có phiếu thu')
                    ->placeholder('Tất cả')
                    ->trueLabel('Có bill')
                    ->falseLabel('Chưa có bill'),
            ]);
    }
}
