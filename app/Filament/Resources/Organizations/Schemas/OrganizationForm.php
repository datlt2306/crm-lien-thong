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
                    ->unique('organizations', 'name', ignoreRecord: true)
                    ->validationAttribute('Tên đơn vị'),
                Section::make('Chủ đơn vị')
                    ->columns(1)
                    ->visible(fn($context) => Auth::user()?->role === 'super_admin')
                    ->schema([
                        \Filament\Forms\Components\Select::make('organization_owner_id')
                            ->label('Chọn tài khoản có sẵn')
                            ->relationship('organization_owner', 'name', fn($query) => $query->whereIn('role', ['super_admin', 'organization_owner']))
                            ->searchable()
                            ->preload()
                            ->placeholder('Chọn tài khoản có sẵn...')
                            ->required(fn($context) => $context === 'create'),
                        // Ẩn các trường tạo tài khoản mới khi tạo mới đơn vị; chỉ hiển thị ở chế độ sửa
                        \Filament\Forms\Components\TextInput::make('organization_owner_email')
                            ->label('Email chủ đơn vị (nếu tạo mới)')
                            ->email()
                            ->helperText('Chỉ điền nếu muốn tạo tài khoản mới (chỉ trong chỉnh sửa)')
                            ->visible(fn($get, $context) => $context === 'edit' && !$get('organization_owner_id')),
                        \Filament\Forms\Components\TextInput::make('organization_owner_password')
                            ->label('Mật khẩu (nếu tạo mới)')
                            ->password()
                            ->default('123456')
                            ->helperText('Mặc định: 123456')
                            ->minLength(6)
                            ->confirmed()
                            ->visible(fn($get, $context) => $context === 'edit' && !$get('organization_owner_id')),
                        \Filament\Forms\Components\TextInput::make('organization_owner_password_confirmation')
                            ->label('Xác nhận mật khẩu')
                            ->password()
                            ->default('123456')
                            ->same('organization_owner_password')
                            ->visible(fn($get, $context) => $context === 'edit' && !$get('organization_owner_id')),
                    ]),


                \Filament\Forms\Components\Toggle::make('status')
                    ->label('Kích hoạt')
                    ->onColor('success')
                    ->offColor('danger')
                    ->inline(false)
                    ->helperText('Bật để kích hoạt, tắt để vô hiệu hoá đơn vị')
                    ->formatStateUsing(function ($state) {
                        // Filament đã tự động chuyển đổi string thành boolean
                        // Nếu state là string, so sánh với 'active'
                        // Nếu state là boolean, trả về trực tiếp
                        if (is_string($state)) {
                            return $state === 'active';
                        }
                        return (bool) $state;
                    })
                    ->dehydrateStateUsing(function ($state) {
                        return $state ? 'active' : 'inactive';
                    }),
            ])
            ->columns(1);
    }
}
