<?php

namespace App\Filament\Resources\Majors\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MajorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('name')
                ->label('Tên ngành')
                ->required()
                ->maxLength(255),
            TextInput::make('code')
                ->label('Mã ngành')
                ->maxLength(255),
            Toggle::make('is_active')
                ->label('Đang hoạt động')
                ->default(true),
        ]);
    }
}

