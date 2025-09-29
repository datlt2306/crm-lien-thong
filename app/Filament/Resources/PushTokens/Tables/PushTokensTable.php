<?php

namespace App\Filament\Resources\PushTokens\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Tables\Table;

class PushTokensTable {
    public static function configure(Table $table): Table {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Người dùng')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable(),

                TextColumn::make('token')
                    ->label('Token')
                    ->limit(30)
                    ->tooltip(fn($record) => $record->token),

                BadgeColumn::make('platform')
                    ->label('Nền tảng')
                    ->colors([
                        'primary' => 'web',
                        'success' => 'ios',
                        'warning' => 'android',
                    ]),

                TextColumn::make('device_name')
                    ->label('Thiết bị')
                    ->placeholder('Không xác định'),

                IconColumn::make('is_active')
                    ->label('Trạng thái')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('last_used_at')
                    ->label('Lần cuối sử dụng')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Chưa sử dụng')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('platform')
                    ->label('Nền tảng')
                    ->options([
                        'web' => 'Web',
                        'ios' => 'iOS',
                        'android' => 'Android',
                    ]),

                SelectFilter::make('is_active')
                    ->label('Trạng thái')
                    ->options([
                        '1' => 'Đang hoạt động',
                        '0' => 'Không hoạt động',
                    ]),
            ])
            ->recordActions([
                Action::make('activate')
                    ->label('Kích hoạt')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(fn($record) => $record->activate())
                    ->visible(fn($record) => !$record->is_active),

                Action::make('deactivate')
                    ->label('Vô hiệu hóa')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->action(fn($record) => $record->deactivate())
                    ->visible(fn($record) => $record->is_active),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
