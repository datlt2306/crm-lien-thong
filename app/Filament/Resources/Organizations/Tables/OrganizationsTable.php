<?php

namespace App\Filament\Resources\Organizations\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrganizationsTable {
    public static function configure(Table $table): Table {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Tên đơn vị')
                    ->searchable(),
                // TextColumn::make('code')
                //     ->label('Mã đơn vị')
                //     ->searchable(),
                TextColumn::make('owner_contact')
                    ->label('Chủ đơn vị')
                    ->state(fn($record) => $record->owner)
                    ->formatStateUsing(function ($owner) {
                        if (!$owner) return '—';
                        $name = $owner->name ?? '';
                        $email = $owner->email ?? '';
                        $lines = [];
                        if ($name) {
                            $lines[] = e($name);
                        }
                        if ($email) {
                            $lines[] = '<span class="text-gray-500">' . e($email) . '</span>';
                        }
                        return implode('<br>', $lines) ?: '—';
                    })
                    ->html()
                    ->searchable(query: function ($query, $search) {
                        return $query->whereHas('owner', function ($q) use ($search) {
                            $q->where('name', 'like', "%$search%")
                                ->orWhere('email', 'like', "%$search%");
                        });
                    }),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->color(fn($state) => $state === 'active' ? 'success' : 'danger')
                    ->formatStateUsing(fn($state) => $state === 'active' ? 'Kích hoạt' : 'Vô hiệu'),
                TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Ngày cập nhật')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options([
                        'active' => 'Kích hoạt',
                        'inactive' => 'Vô hiệu',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
