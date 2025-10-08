<?php

namespace App\Filament\Resources\Collaborators\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class CollaboratorForm {
    public static function configure(Schema $schema): Schema {
        return $schema
            ->components([
                TextInput::make('full_name')
                    ->label('Họ tên')
                    ->required(),
                TextInput::make('phone')
                    ->label('Số điện thoại')
                    ->tel()
                    ->unique(ignoreRecord: true)
                    ->required(),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->unique(ignoreRecord: true)
                    ->nullable(),
                TextInput::make('password')
                    ->label('Mật khẩu')
                    ->password()
                    ->default('123456')
                    ->nullable()
                    ->visible(fn($get) => !empty($get('email')))
                    ->helperText('Mặc định: 123456. CTV có thể thay đổi sau khi đăng nhập.')
                    ->minLength(6)
                    ->confirmed(),
                TextInput::make('password_confirmation')
                    ->label('Xác nhận mật khẩu')
                    ->password()
                    ->default('123456')
                    ->nullable()
                    ->visible(fn($get) => !empty($get('email')))
                    ->same('password'),
                \Filament\Forms\Components\TextInput::make('ref_id')
                    ->label('Link giới thiệu')
                    ->readOnly()
                    ->unique(ignoreRecord: true)
                    ->required()
                    ->default(fn() => strtoupper(Str::random(8)))
                    ->formatStateUsing(
                        fn($state) =>
                        $state ? (request()->getSchemeAndHttpHost() . '/ref/' . $state) : ''
                    )
                    ->dehydrateStateUsing(
                        fn($state) =>
                        $state ? Str::afterLast($state, '/') : null
                    )
                    ->copyable()
                    ->helperText('Click vào field hoặc icon để copy link'),
                \Filament\Forms\Components\Select::make('organization_id')
                    ->label('Tổ chức')
                    ->relationship('organization', 'name')
                    ->required()
                    ->visible(fn() => Auth::user()?->role === 'super_admin'),
                \Filament\Forms\Components\Select::make('upline_id')
                    ->label('CTV cấp trên')
                    ->relationship('upline', 'full_name')
                    ->searchable()
                    ->preload()
                    ->helperText('Chọn CTV cấp trên (để trống nếu là CTV cấp cao nhất)')
                    ->visible(fn() => Auth::user()?->role === 'super_admin'),
                \Filament\Forms\Components\Textarea::make('note')
                    ->label('Ghi chú')
                    ->columnSpanFull(),
                \Filament\Forms\Components\Select::make('status')
                    ->label('Trạng thái')
                    ->options([
                        'active' => 'Kích hoạt',
                        'pending' => 'Chờ duyệt',
                        'inactive' => 'Vô hiệu',
                    ])
                    ->required()
                    ->default('active')
                    ->helperText('Chọn trạng thái cho cộng tác viên')
                    ->native(false),
            ]);
    }
}
