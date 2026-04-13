<?php

namespace App\Filament\Resources\Programs\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProgramForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('name')
                ->label('Tên hệ đào tạo')
                ->required()
                ->maxLength(255),
            TextInput::make('code')
                ->label('Mã hệ')
                ->maxLength(255),
            Toggle::make('is_active')
                ->label('Đang hoạt động')
                ->default(true),
        ]);
    }
}

