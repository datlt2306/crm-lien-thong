<?php

namespace App\Filament\Resources\Collaborators\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\Action;

class CollaboratorsTable {
    public static function configure(Table $table): Table {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->searchable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('organization_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('organization.name')
                    ->label('Tổ chức')
                    ->searchable(),
                TextColumn::make('upline.full_name')
                    ->label('Upline')
                    ->searchable(),
                TextColumn::make('ref_id')
                    ->label('Mã giới thiệu')
                    ->badge()
                    ->copyable()
                    ->copyMessage('Đã copy!')
                    ->copyMessageDuration(1500)
                    ->formatStateUsing(fn($state) => $state)
                    ->extraAttributes(['class' => 'cursor-pointer']),
                TextColumn::make('upline_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->color(fn($state) => $state === 'active' ? 'success' : 'danger')
                    ->formatStateUsing(fn($state) => $state === 'active' ? 'Kích hoạt' : 'Vô hiệu'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('organization_id')
                    ->label('Tổ chức')
                    ->relationship('organization', 'name'),
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options([
                        'active' => 'Kích hoạt',
                        'inactive' => 'Vô hiệu',
                    ]),
            ])
            ->recordActions([
                Action::make('copy_ref_link')
                    ->label('Copy ref link')
                    ->icon('heroicon-o-clipboard')
                    ->action(fn($record) => \Filament\Support\Facades\FilamentCopy::copy(url('/apply?ref=' . $record->ref_id)))
                    ->tooltip('Copy đường dẫn giới thiệu'),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
