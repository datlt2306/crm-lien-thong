<?php

namespace App\Filament\Resources\CommissionPolicies\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;

class CommissionPoliciesTable {
    public static function configure(Table $table): Table {
        return $table
            ->columns([
                TextColumn::make('organization.name')
                    ->label('Tổ chức')
                    ->searchable()
                    ->default('Tất cả'),
                TextColumn::make('collaborator.full_name')
                    ->label('CTV')
                    ->searchable()
                    ->default('Tất cả'),
                TextColumn::make('program_type')
                    ->label('Chương trình')
                    ->formatStateUsing(fn($state) => $state ? ($state === 'REGULAR' ? 'Chính quy' : 'Bán thời gian') : 'Tất cả'),
                TextColumn::make('role')
                    ->label('Vai trò')
                    ->formatStateUsing(fn($state) => $state ? ($state === 'PRIMARY' ? 'CTV chính' : 'CTV phụ') : 'Tất cả'),
                TextColumn::make('type')
                    ->label('Loại')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'FIXED' => 'Cố định',
                        'PERCENT' => 'Phần trăm',
                        'PASS_THROUGH' => 'Chuyển tiếp',
                        default => $state
                    }),
                TextColumn::make('amount_vnd')
                    ->label('Số tiền (VND)')
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->type === 'FIXED' && $state) {
                            return number_format($state) . ' VND';
                        }
                        return '-';
                    }),
                TextColumn::make('percent')
                    ->label('Phần trăm (%)')
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->type === 'PERCENT' && $state) {
                            return $state . '%';
                        }
                        return '-';
                    }),
                TextColumn::make('trigger')
                    ->label('Kích hoạt')
                    ->formatStateUsing(fn($state) => $state ? ($state === 'ON_VERIFICATION' ? 'Khi xác nhận' : 'Khi nhập học') : 'Mặc định'),
                TextColumn::make('priority')
                    ->label('Ưu tiên')
                    ->sortable(),
                TextColumn::make('active')
                    ->label('Trạng thái')
                    ->badge()
                    ->color(fn($state) => $state ? 'success' : 'danger')
                    ->formatStateUsing(fn($state) => $state ? 'Kích hoạt' : 'Vô hiệu'),
                TextColumn::make('effective_from')
                    ->label('Từ ngày')
                    ->date()
                    ->sortable(),
                TextColumn::make('effective_to')
                    ->label('Đến ngày')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('organization_id')
                    ->label('Tổ chức')
                    ->relationship('organization', 'name'),
                SelectFilter::make('program_type')
                    ->label('Chương trình')
                    ->options([
                        'REGULAR' => 'Chính quy',
                        'PART_TIME' => 'Bán thời gian',
                    ]),
                SelectFilter::make('role')
                    ->label('Vai trò')
                    ->options([
                        'PRIMARY' => 'CTV chính',
                        'SUB' => 'CTV phụ',
                    ]),
                SelectFilter::make('type')
                    ->label('Loại hoa hồng')
                    ->options([
                        'FIXED' => 'Cố định',
                        'PERCENT' => 'Phần trăm',
                        'PASS_THROUGH' => 'Chuyển tiếp',
                    ]),
                TernaryFilter::make('active')
                    ->label('Trạng thái')
                    ->placeholder('Tất cả')
                    ->trueLabel('Kích hoạt')
                    ->falseLabel('Vô hiệu'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
