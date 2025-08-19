<?php

namespace App\Filament\Resources\Collaborators\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CollaboratorForm {
    public static function configure(Schema $schema): Schema {
        return $schema
            ->components([
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
                    ->label('Link giới thiệu')
                    ->readOnly()
                    ->unique(ignoreRecord: true)
                    ->required()
                    ->default(fn() => strtoupper(Str::random(8)))
                    ->formatStateUsing(
                        fn($state) =>
                        $state ? 'https://lienthongdaihoc.com/ref/' . $state : ''
                    )
                    ->dehydrateStateUsing(
                        fn($state) =>
                        $state ? Str::afterLast($state, '/') : null
                    ),
                \Filament\Forms\Components\Select::make('organization_id')
                    ->label('Tổ chức')
                    ->relationship('organization', 'name')
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
