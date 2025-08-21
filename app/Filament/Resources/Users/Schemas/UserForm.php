<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm {
    public static function configure(Schema $schema): Schema {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                DateTimePicker::make('email_verified_at'),
                \Filament\Forms\Components\Select::make('role')
                    ->label('Vai trò')
                    ->options([
                        'super_admin' => 'Super Admin',
                        'chủ đơn vị' => 'Chủ đơn vị',
                        'ctv' => 'Cộng tác viên',
                    ])
                    ->required()
                    ->default('ctv'),
                \Filament\Forms\Components\TextInput::make('password')
                    ->password()
                    ->label('Mật khẩu')
                    ->required(fn($context) => $context === 'create'),
            ]);
    }
}
