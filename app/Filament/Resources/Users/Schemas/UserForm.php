<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Schema;

class UserForm {
    public static function configure(Schema $schema): Schema {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Họ và tên')
                    ->required(),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required(),
                TextInput::make('phone')
                    ->label('Số điện thoại')
                    ->tel()
                    ->helperText('VD: 0123456789'),
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
                    ->required(fn($context) => $context === 'create')
                    ->dehydrated(fn($context, $state) => $context === 'create' || !empty($state))
                    ->helperText(fn($context) => $context === 'edit' ? 'Để trống nếu không muốn thay đổi mật khẩu' : 'Nhập mật khẩu cho tài khoản mới')
                    ->confirmed(),
                \Filament\Forms\Components\TextInput::make('password_confirmation')
                    ->password()
                    ->label('Xác nhận mật khẩu')
                    ->required(fn($context) => $context === 'create')
                    ->dehydrated(false)
                    ->helperText('Nhập lại mật khẩu để xác nhận'),
                FileUpload::make('avatar')
                    ->label('Ảnh đại diện')
                    ->image()
                    ->imageEditor()
                    ->imageCropAspectRatio('1:1')
                    ->imageResizeTargetWidth('200')
                    ->imageResizeTargetHeight('200')
                    ->helperText('Tải lên ảnh đại diện (khuyến nghị: 200x200px)')
                    ->directory('avatars'),
            ]);
    }
}
