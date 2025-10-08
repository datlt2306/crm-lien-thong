<?php

namespace App\Filament\Resources\PermissionManagement\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Table;

class PermissionManagementTable {
    public static function configure(Table $table): Table {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Tên vai trò')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('guard_name')
                    ->label('Guard')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('secondary'),

                TextColumn::make('permissions_count')
                    ->label('Số quyền')
                    ->counts('permissions')
                    ->sortable()
                    ->badge()
                    ->color('success'),

                TextColumn::make('users_count')
                    ->label('Số người dùng')
                    ->counts('users')
                    ->sortable()
                    ->badge()
                    ->color('warning'),

                TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('guard_name')
                    ->label('Guard')
                    ->options([
                        'web' => 'Web',
                        'api' => 'API',
                    ]),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Chỉnh sửa'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Xóa đã chọn')
                        ->modalHeading('Xóa vai trò đã chọn')
                        ->modalDescription('Bạn có chắc chắn muốn xóa các vai trò đã chọn? Hành động này không thể hoàn tác.')
                        ->modalSubmitActionLabel('Xóa')
                        ->modalCancelActionLabel('Hủy'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
