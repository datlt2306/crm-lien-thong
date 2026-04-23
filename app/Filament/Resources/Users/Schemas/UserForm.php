<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
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
                    ->required()
                    ->rules([
                        function ($record) {
                            $rule = \Illuminate\Validation\Rule::unique('users', 'email');
                            if ($record) {
                                $rule = $rule->ignore($record->id);
                            }
                            return $rule;
                        },
                    ])
                    ->validationAttribute('Email'),
                TextInput::make('phone')
                    ->label('Số điện thoại')
                    ->tel()
                    ->required(fn(callable $get) => $get('role') === 'ctv')
                    ->rules([
                        function (callable $get, $record) {
                            if ($get('role') !== 'ctv') return null;
                            $rule = \Illuminate\Validation\Rule::unique('collaborators', 'phone');
                            if ($record) {
                                $collab = \App\Models\Collaborator::where('email', $record->email)->first();
                                if ($collab) {
                                    $rule = $rule->ignore($collab->id);
                                }
                            }
                            return $rule;
                        },
                    ])
                    ->validationAttribute('Số điện thoại')
                    ->helperText('Bắt buộc với vai trò CTV. Số điện thoại phải là duy nhất.'),
                \Filament\Forms\Components\Select::make('role')
                    ->label('Vai trò')
                    ->options([
                        'super_admin' => 'Super Admin',

                        'ctv' => 'Cộng tác viên',
                        'accountant' => 'Kế toán',
                        'admissions' => 'Cán bộ tuyển sinh',
                        'document' => 'Cán bộ hồ sơ',
                    ])
                    ->required()
                    ->default('ctv')
                    ->visible(fn() => in_array(\Illuminate\Support\Facades\Auth::user()?->role, ['super_admin', ]))
                    ->helperText('Chọn vai trò cho người dùng'),
                \Filament\Forms\Components\TextInput::make('password')
                    ->password()
                    ->label('Mật khẩu')
                    ->required(false)
                    ->dehydrateStateUsing(fn ($context, $state) => !empty($state) ? \Illuminate\Support\Facades\Hash::make($state) : ($context === 'create' ? \Illuminate\Support\Facades\Hash::make('12345678') : null))
                    ->dehydrated(fn($context, $state) => $context === 'create' || !empty($state))
                    ->helperText(fn($context) => $context === 'edit' 
                        ? 'Để trống nếu không muốn thay đổi mật khẩu' 
                        : 'Để trống để tự động dùng mật khẩu mặc định: 12345678')
                    ->confirmed(),
                \Filament\Forms\Components\TextInput::make('password_confirmation')
                    ->password()
                    ->label('Xác nhận mật khẩu')
                    ->required(false)
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
                    ->disk('google')
                    ->directory('avatars'),
            ]);
    }
}
