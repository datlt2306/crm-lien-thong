<?php

namespace App\Filament\Resources\NotificationPreferences\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ActionGroup;
use Filament\Tables\Table;

class NotificationPreferencesTable {
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

                BadgeColumn::make('user.role')
                    ->label('Vai trò')
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'super_admin' => 'Super Admin',
                        'organization_owner' => 'Chủ đơn vị',
                        'ctv' => 'CTV',
                        'accountant' => 'Kế toán',
                        default => $state,
                    })
                    ->colors([
                        'danger' => 'super_admin',
                        'warning' => 'organization_owner',
                        'success' => 'ctv',
                        'info' => 'accountant',
                    ]),

                IconColumn::make('email_payment_verified')
                    ->label('Email - Thanh toán')
                    ->boolean(),

                IconColumn::make('push_payment_verified')
                    ->label('Push - Thanh toán')
                    ->boolean(),

                IconColumn::make('in_app_payment_verified')
                    ->label('In-App - Thanh toán')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('user_role')
                    ->label('Vai trò')
                    ->relationship('user', 'role')
                    ->options([
                        'super_admin' => 'Super Admin',
                        'organization_owner' => 'Chủ đơn vị',
                        'ctv' => 'CTV',
                        'accountant' => 'Kế toán',
                    ]),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->label('Chỉnh sửa'),
                    DeleteAction::make()
                        ->label('Xóa'),
                ])
                    ->label('Hành động')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('gray')
                    ->button()
                    ->size('sm')
                    ->tooltip('Các hành động khả dụng')
            ])
            ->defaultSort('created_at', 'desc');
    }
}
