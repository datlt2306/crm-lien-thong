<?php

namespace App\Filament\Resources\PermissionManagement\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\CheckboxList;
use Filament\Schemas\Components\Section;

class PermissionManagementForm {
    public static function configure(Schema $schema): Schema {
        return $schema
            ->schema([
                Section::make('📋 Thông tin vai trò')
                    ->description('Thiết lập thông tin cơ bản của vai trò')
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        TextInput::make('name')
                            ->label('Tên vai trò')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Nhập tên vai trò...')
                            ->helperText('Tên vai trò phải duy nhất trong hệ thống'),

                        Select::make('guard_name')
                            ->label('Guard')
                            ->options([
                                'web' => 'Web',
                                'api' => 'API',
                            ])
                            ->required()
                            ->default('web')
                            ->helperText('Guard xác định cách xác thực vai trò'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('🔐 Phân quyền')
                    ->description('Chọn các quyền cho vai trò này')
                    ->icon('heroicon-o-key')
                    ->schema([
                        CheckboxList::make('permissions')
                            ->label('Danh sách quyền')
                            ->relationship('permissions', 'name')
                            ->searchable()
                            ->bulkToggleable()
                            ->gridDirection('row')
                            ->columns(3)
                            ->helperText('Chọn các quyền mà vai trò này có thể thực hiện'),
                    ])
                    ->collapsible(),
            ]);
    }
}
