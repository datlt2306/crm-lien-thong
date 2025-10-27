<?php

namespace App\Filament\Resources\CollaboratorRegistrations\Tables;

use App\Filament\Resources\CollaboratorRegistrations\Actions\ApproveRegistrationAction;
use App\Filament\Resources\CollaboratorRegistrations\Actions\RejectRegistrationAction;
use App\Models\CollaboratorRegistration;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CollaboratorRegistrationsTable {
    public static function configure(Table $table): Table {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->label('Họ và tên')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('phone')
                    ->label('Số điện thoại')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('organization.name')
                    ->label('Tổ chức')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('upline.full_name')
                    ->label('CTV giới thiệu')
                    ->formatStateUsing(function ($record) {
                        if (!$record->upline) {
                            return 'CTV cấp 1';
                        }
                        return $record->upline->full_name . ' (' . $record->upline->ref_id . ')';
                    })
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'pending' => 'Chờ duyệt',
                        'approved' => 'Đã duyệt',
                        'rejected' => 'Từ chối',
                    }),

                TextColumn::make('reviewer.name')
                    ->label('Người duyệt')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('reviewed_at')
                    ->label('Thời gian duyệt')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Ngày đăng ký')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options([
                        'pending' => 'Chờ duyệt',
                        'approved' => 'Đã duyệt',
                        'rejected' => 'Từ chối',
                    ]),

                SelectFilter::make('organization_id')
                    ->label('Tổ chức')
                    ->relationship('organization', 'name'),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->label('Chỉnh sửa'),
                    ApproveRegistrationAction::make()
                        ->visible(fn(CollaboratorRegistration $record): bool => $record->status === 'pending'),
                    RejectRegistrationAction::make()
                        ->visible(fn(CollaboratorRegistration $record): bool => $record->status === 'pending'),
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
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
