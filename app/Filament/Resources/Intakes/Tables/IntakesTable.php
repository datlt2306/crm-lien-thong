<?php

namespace App\Filament\Resources\Intakes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Table;

class IntakesTable {
    public static function configure(Table $table): Table {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Tên đợt tuyển sinh')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('organization.name')
                    ->label('Tổ chức')
                    ->searchable()
                    ->sortable()
                    ->visible(fn() => \Illuminate\Support\Facades\Auth::user() &&
                        !in_array(\Illuminate\Support\Facades\Auth::user()->role, ['ctv'])),

                TextColumn::make('program.name')
                    ->label('Chương trình đào tạo')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Chưa chọn')
                    ->badge()
                    ->color(fn($state) => $state ? 'success' : 'gray'),

                BadgeColumn::make('status')
                    ->label('Trạng thái')
                    ->formatStateUsing(function ($state) {
                        return \App\Models\Intake::getStatusOptions()[$state] ?? $state;
                    })
                    ->colors([
                        'warning' => \App\Models\Intake::STATUS_UPCOMING,
                        'success' => \App\Models\Intake::STATUS_ACTIVE,
                        'danger' => \App\Models\Intake::STATUS_CLOSED,
                        'gray' => \App\Models\Intake::STATUS_CANCELLED,
                    ]),

                TextColumn::make('start_date')
                    ->label('Bắt đầu')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('end_date')
                    ->label('Kết thúc')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('quotas_count')
                    ->label('Số ngành')
                    ->counts('quotas')
                    ->sortable(),

                TextColumn::make('total_target_quota')
                    ->label('Tổng chỉ tiêu')
                    ->getStateUsing(function ($record) {
                        return number_format($record->total_target_quota);
                    })
                    ->sortable(),

                TextColumn::make('quota_utilization')
                    ->label('Tỷ lệ sử dụng')
                    ->getStateUsing(function ($record) {
                        return $record->quota_utilization . '%';
                    })
                    ->color(
                        fn($state) =>
                        (float)str_replace('%', '', $state) > 90 ? 'danger' : ((float)str_replace('%', '', $state) > 70 ? 'warning' : 'success')
                    ),

                TextColumn::make('students_count')
                    ->label('Số học viên')
                    ->counts('students')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options(\App\Models\Intake::getStatusOptions()),

                \Filament\Tables\Filters\SelectFilter::make('organization_id')
                    ->label('Tổ chức')
                    ->relationship('organization', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn() => \Illuminate\Support\Facades\Auth::user() &&
                        !in_array(\Illuminate\Support\Facades\Auth::user()->role, ['ctv'])),

                \Filament\Tables\Filters\SelectFilter::make('program_id')
                    ->label('Chương trình đào tạo')
                    ->relationship('program', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Xem chi tiết'),
                    EditAction::make()
                        ->label('Chỉnh sửa')
                        ->visible(fn() => \Illuminate\Support\Facades\Auth::user() &&
                            in_array(\Illuminate\Support\Facades\Auth::user()->role, ['super_admin', 'organization_owner'])),
                ])
                    ->label('Hành động')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('gray')
                    ->button()
                    ->size('sm')
                    ->tooltip('Các hành động khả dụng')
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Xóa đã chọn')
                        ->modalHeading('Xóa đợt tuyển sinh đã chọn')
                        ->modalDescription('Bạn có chắc chắn muốn xóa các đợt tuyển sinh đã chọn? Hành động này không thể hoàn tác.')
                        ->modalSubmitActionLabel('Xóa')
                        ->modalCancelActionLabel('Hủy')
                        ->visible(fn() => \Illuminate\Support\Facades\Auth::user() &&
                            in_array(\Illuminate\Support\Facades\Auth::user()->role, ['super_admin', 'organization_owner'])),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
