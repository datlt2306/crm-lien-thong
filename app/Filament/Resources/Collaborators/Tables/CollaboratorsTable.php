<?php

namespace App\Filament\Resources\Collaborators\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;

class CollaboratorsTable {
    public static function configure(Table $table): Table {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->label('Họ và tên')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('contact')
                    ->label('Liên hệ')
                    ->state(fn($record) => $record)
                    ->formatStateUsing(function ($record) {
                        $phone = $record->phone ?: '';
                        $email = $record->email ?: '';
                        $lines = [];
                        if ($phone) {
                            $lines[] = '📞 ' . e($phone);
                        }
                        if ($email) {
                            $lines[] = '✉️ ' . e($email);
                        }
                        return implode('<br>', $lines) ?: '—';
                    })
                    ->html()
                    ->searchable(query: function ($query, $search) {
                        return $query->where(function ($q) use ($search) {
                            $q->where('phone', 'like', "%$search%")
                                ->orWhere('email', 'like', "%$search%");
                        });
                    })
                    ->sortable(),
                TextColumn::make('upline.full_name')
                    ->label('CTV cấp trên')
                    ->searchable()
                    ->visible(fn() => Auth::user()?->role === 'super_admin'),
                TextColumn::make('downlines_count')
                    ->label('Số CTV con')
                    ->counts('downlines')
                    ->badge()
                    ->color('info')
                    ->visible(function ($record) {
                        return $record && $record->downlines()->count() > 0;
                    }),
                // TextColumn::make('organization.name')
                //     ->label('Tổ chức')
                //     ->searchable(),
                TextColumn::make('ref_id')
                    ->label('Mã giới thiệu')
                    ->badge()
                    ->color('warning')
                    ->copyable(fn($record) => 'https://lienthongdaihoc.com/ref/' . $record->ref_id)
                    ->copyMessage('Đã copy link giới thiệu!')
                    ->copyMessageDuration(2000),
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
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
