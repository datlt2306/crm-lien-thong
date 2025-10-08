<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

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
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tạo lúc')
                    ->since()
                    ->sortable(),
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
