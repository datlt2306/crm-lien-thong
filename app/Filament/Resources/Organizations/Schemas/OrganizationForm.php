<?php

namespace App\Filament\Resources\Organizations\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OrganizationForm {
    public static function configure(Schema $schema): Schema {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Tên đơn vị')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, callable $set) {
                        $set('code', \Str::slug($state));
                    }),
                TextInput::make('code')
                    ->label('Mã đơn vị')
                    ->required()
                    ->disabled(),
                TextInput::make('contact_name')
                    ->label('Người liên hệ'),
                TextInput::make('contact_phone')
                    ->label('Số điện thoại liên hệ')
                    ->tel(),
                \Filament\Forms\Components\Select::make('owner_id')
                    ->label('Chủ đơn vị')
                    ->relationship('owner', 'name', fn($query) => $query->whereIn('role', ['super_admin', 'user']))
                    ->searchable()
                    ->preload(),
                \Filament\Forms\Components\Toggle::make('status')
                    ->label('Kích hoạt')
                    ->onColor('success')
                    ->offColor('danger')
                    ->inline(false)
                    ->required()
                    ->default(true)
                    ->helperText('Bật để kích hoạt, tắt để vô hiệu')
                    ->formatStateUsing(fn($state) => $state === 'active')
                    ->dehydrateStateUsing(fn($state) => $state ? 'active' : 'inactive'),
            ]);
    }
}
