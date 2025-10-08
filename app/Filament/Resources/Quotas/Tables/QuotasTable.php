<?php

namespace App\Filament\Resources\Quotas\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Table;

class QuotasTable {
    public static function configure(Table $table): Table {
        return $table
            ->columns([
                TextColumn::make('intake.name')
                    ->label('Đợt tuyển sinh')
                    ->searchable()
                    ->sortable()
                    ->description(fn($record) => $record->intake ?
                        'Từ ' . $record->intake->start_date?->format('d/m/Y') .
                        ' đến ' . $record->intake->end_date?->format('d/m/Y') : null)
                    ->badge()
                    ->color('info'),

                TextColumn::make('major.name')
                    ->label('Ngành đào tạo')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(function ($record) {
                        if ($record->major) {
                            return $record->major->code . ' - ' . $record->major->name;
                        }
                        return 'Chưa chọn ngành';
                    }),

                TextColumn::make('program.name')
                    ->label('Hệ đào tạo')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Chưa chọn')
                    ->badge()
                    ->color('warning'),

                TextColumn::make('intake.start_date')
                    ->label('Năm')
                    ->formatStateUsing(function ($record) {
                        return $record->intake?->start_date?->format('Y');
                    })
                    ->sortable()
                    ->searchable(),

                TextColumn::make('intake.start_date')
                    ->label('Tháng')
                    ->formatStateUsing(function ($record) {
                        return $record->intake?->start_date?->format('m');
                    })
                    ->sortable()
                    ->searchable(),

                TextColumn::make('target_quota')
                    ->label('Chỉ tiêu')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('current_quota')
                    ->label('Đã đạt')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('available_slots')
                    ->label('Còn trống')
                    ->getStateUsing(function ($record) {
                        return $record->available_slots;
                    })
                    ->color(fn($state) => $state <= 0 ? 'danger' : ($state <= 10 ? 'warning' : 'success')),

                BadgeColumn::make('status')
                    ->label('Trạng Thái')
                    ->formatStateUsing(function ($state) {
                        return \App\Models\Quota::getStatusOptions()[$state] ?? $state;
                    })
                    ->colors([
                        'success' => \App\Models\Quota::STATUS_ACTIVE,
                        'warning' => \App\Models\Quota::STATUS_INACTIVE,
                        'danger' => \App\Models\Quota::STATUS_FULL,
                    ]),

                TextColumn::make('organization.name')
                    ->label('Tổ chức')
                    ->searchable()
                    ->sortable()
                    ->visible(fn() => \Illuminate\Support\Facades\Auth::user() &&
                        !in_array(\Illuminate\Support\Facades\Auth::user()->role, ['ctv'])),

                TextColumn::make('tuition_fee')
                    ->label('Học phí')
                    ->money('VND')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options(\App\Models\Quota::getStatusOptions()),

                \Filament\Tables\Filters\SelectFilter::make('intake_id')
                    ->label('Đợt tuyển sinh')
                    ->relationship('intake', 'name')
                    ->searchable()
                    ->preload(),

                \Filament\Tables\Filters\SelectFilter::make('major_id')
                    ->label('Ngành học')
                    ->relationship('major', 'name')
                    ->searchable()
                    ->preload(),

                \Filament\Tables\Filters\SelectFilter::make('program_id')
                    ->label('Hệ đào tạo')
                    ->relationship('program', 'name')
                    ->searchable()
                    ->preload(),

                \Filament\Tables\Filters\SelectFilter::make('organization_id')
                    ->label('Tổ chức')
                    ->relationship('organization', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn() => \Illuminate\Support\Facades\Auth::user() &&
                        !in_array(\Illuminate\Support\Facades\Auth::user()->role, ['ctv'])),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Xem chi tiết'),
                EditAction::make()
                    ->label('Chỉnh sửa')
                    ->visible(fn() => \Illuminate\Support\Facades\Auth::user() &&
                        in_array(\Illuminate\Support\Facades\Auth::user()->role, ['super_admin', 'organization_owner'])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Xóa đã chọn')
                        ->modalHeading('Xóa chỉ tiêu đã chọn')
                        ->modalDescription('Bạn có chắc chắn muốn xóa các chỉ tiêu đã chọn? Hành động này không thể hoàn tác.')
                        ->modalSubmitActionLabel('Xóa')
                        ->modalCancelActionLabel('Hủy')
                        ->visible(fn() => \Illuminate\Support\Facades\Auth::user() &&
                            in_array(\Illuminate\Support\Facades\Auth::user()->role, ['super_admin', 'organization_owner'])),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
