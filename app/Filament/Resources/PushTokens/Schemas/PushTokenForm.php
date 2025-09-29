<?php

namespace App\Filament\Resources\PushTokens\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Schema;

class PushTokenForm {
    public static function configure(Schema $schema): Schema {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('Người dùng')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                TextInput::make('token')
                    ->label('Token')
                    ->required()
                    ->maxLength(255)
                    ->helperText('Push notification token từ thiết bị'),

                Select::make('platform')
                    ->label('Nền tảng')
                    ->options([
                        'web' => 'Web',
                        'ios' => 'iOS',
                        'android' => 'Android',
                    ])
                    ->default('web')
                    ->required(),

                TextInput::make('device_id')
                    ->label('ID thiết bị')
                    ->maxLength(255)
                    ->helperText('ID duy nhất của thiết bị'),

                TextInput::make('device_name')
                    ->label('Tên thiết bị')
                    ->maxLength(255)
                    ->helperText('Tên hiển thị của thiết bị'),

                Toggle::make('is_active')
                    ->label('Đang hoạt động')
                    ->default(true),

                DateTimePicker::make('last_used_at')
                    ->label('Lần cuối sử dụng')
                    ->disabled(),
            ]);
    }
}
