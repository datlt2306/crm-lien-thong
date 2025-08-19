<?php

namespace App\Filament\Resources\Collaborators\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class CollaboratorForm {
    public static function configure(Schema $schema): Schema {
        return $schema
            ->components([
                \Filament\Forms\Components\Select::make('upline_id')
                    ->label('CTV giới thiệu (Upline)')
                    ->relationship('upline', 'full_name')
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->helperText('Chọn CTV đã có để làm người giới thiệu/upline. Nếu không có, để trống.'),
                TextInput::make('full_name')
                    ->label('Họ tên')
                    ->required(),
                TextInput::make('phone')
                    ->label('Số điện thoại')
                    ->tel()
                    ->unique(ignoreRecord: true)
                    ->required(),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->unique(ignoreRecord: true)
                    ->nullable(),
                \Filament\Forms\Components\TextInput::make('ref_id')
                    ->label('Mã giới thiệu')
                    ->readOnly()
                    ->unique(ignoreRecord: true)
                    ->required()
                    ->visibleOn('edit')
                    ->afterStateHydrated(function ($state, $component) {
                        if ($state) {
                            $component->helperText('Link giới thiệu: <a href="https://lienthongdaihoc.com/ref/' . $state . '" target="_blank" class="text-blue-600 underline">https://lienthongdaihoc.com/ref/' . $state . '</a>');
                        }
                    }),
                \Filament\Forms\Components\Select::make('organization_id')
                    ->label('Tổ chức')
                    ->relationship('organization', 'name')
                    ->searchable()
                    ->required()
                    ->visible(fn() => auth()->user()?->role === 'super_admin'),
                \Filament\Forms\Components\Textarea::make('note')
                    ->label('Ghi chú')
                    ->columnSpanFull(),
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
