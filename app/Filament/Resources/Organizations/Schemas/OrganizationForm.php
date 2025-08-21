<?php

namespace App\Filament\Resources\Organizations\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class OrganizationForm {
    public static function configure(Schema $schema): Schema {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Tên đơn vị')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, callable $set) {
                        $set('code', Str::slug($state));
                    }),
                TextInput::make('code')
                    ->label('Mã đơn vị')
                    ->required()
                    ->disabled(),

                Section::make('Chủ đơn vị')
                    ->description('Chọn tài khoản có sẵn hoặc tạo mới')
                    ->visible(fn($context) => Auth::user()?->role === 'super_admin')
                    ->schema([
                        \Filament\Forms\Components\Select::make('owner_id')
                            ->label('Chọn tài khoản có sẵn')
                            ->relationship('owner', 'name', fn($query) => $query->whereIn('role', ['super_admin', 'chủ đơn vị']))
                            ->searchable()
                            ->preload()
                            ->placeholder('Chọn tài khoản có sẵn...')
                            ->helperText('Hoặc để trống để tạo tài khoản mới bên dưới'),
                        \Filament\Forms\Components\TextInput::make('owner_email')
                            ->label('Email chủ đơn vị (nếu tạo mới)')
                            ->email()
                            ->helperText('Chỉ điền nếu muốn tạo tài khoản mới')
                            ->visible(fn($get) => !$get('owner_id')),
                        \Filament\Forms\Components\TextInput::make('owner_password')
                            ->label('Mật khẩu (nếu tạo mới)')
                            ->password()
                            ->default('123456')
                            ->helperText('Mặc định: 123456')
                            ->minLength(6)
                            ->confirmed()
                            ->visible(fn($get) => !$get('owner_id')),
                        \Filament\Forms\Components\TextInput::make('owner_password_confirmation')
                            ->label('Xác nhận mật khẩu')
                            ->password()
                            ->default('123456')
                            ->same('owner_password')
                            ->visible(fn($get) => !$get('owner_id')),
                    ]),
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
