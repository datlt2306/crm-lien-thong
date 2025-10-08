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
                \Filament\Forms\Components\Select::make('organization_id')
                    ->label('Tổ chức')
                    ->options(function () {
                        return \App\Models\Organization::orderBy('name')->pluck('name', 'id')->toArray();
                    })
                    ->required()
                    ->visible(fn() => \Illuminate\Support\Facades\Auth::user()?->role === 'super_admin')
                    ->helperText(function ($context) {
                        return $context === 'create' ? 'Chọn tổ chức cho người dùng mới' : 'Chọn tổ chức cho người dùng';
                    })
                    ->default(function ($record) {
                        if (!$record) return null;
                        // Ưu tiên users.organization_id nếu đã có
                        if (!empty($record->organization_id)) {
                            return $record->organization_id;
                        }
                        // Fallback: lấy từ collaborator theo email
                        if (!empty($record->email)) {
                            $collaborator = \App\Models\Collaborator::where('email', $record->email)->first();
                            return $collaborator?->organization_id;
                        }
                        return null;
                    }),
                \Filament\Forms\Components\Select::make('role')
                    ->label('Vai trò')
                    ->options(function () {
                        $user = \Illuminate\Support\Facades\Auth::user();
                        if ($user && $user->role === 'super_admin') {
                            // Super admin có thể chọn tất cả vai trò
                            return [
                                'super_admin' => 'Super Admin',
                                'organization_owner' => 'Chủ đơn vị',
                                'ctv' => 'Cộng tác viên',
                                'accountant' => 'Kế toán',
                                'admissions' => 'Cán bộ tuyển sinh',
                                'document' => 'Cán bộ hồ sơ',
                            ];
                        } elseif ($user && $user->role === 'organization_owner') {
                            // Owner chỉ có thể chọn các vai trò phù hợp trong tổ chức
                            return [
                                'ctv' => 'Cộng tác viên',
                                'accountant' => 'Kế toán',
                                'admissions' => 'Cán bộ tuyển sinh',
                                'document' => 'Cán bộ hồ sơ',
                            ];
                        }
                        return [];
                    })
                    ->required()
                    ->default('ctv')
                    ->visible(fn() => in_array(\Illuminate\Support\Facades\Auth::user()?->role, ['super_admin', 'organization_owner']))
                    ->helperText(function () {
                        $user = \Illuminate\Support\Facades\Auth::user();
                        if ($user && $user->role === 'organization_owner') {
                            return 'Chọn vai trò cho người dùng trong tổ chức của bạn';
                        }
                        return 'Chọn vai trò cho người dùng mới';
                    }),
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
