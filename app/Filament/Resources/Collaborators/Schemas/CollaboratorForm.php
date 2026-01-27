<?php

namespace App\Filament\Resources\Collaborators\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
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

                Section::make('Thông tin định danh & thanh toán')
                    ->description('CCCD, mã số thuế và tài khoản ngân hàng dùng cho KYC và chi trả hoa hồng.')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        TextInput::make('identity_card')
                            ->label('Số CCCD')
                            ->placeholder('Số CCCD 12 chữ số')
                            ->maxLength(20)
                            ->nullable(),
                        TextInput::make('tax_code')
                            ->label('Mã số thuế')
                            ->placeholder('Mã số thuế cá nhân/doanh nghiệp')
                            ->maxLength(20)
                            ->nullable(),
                        TextInput::make('bank_name')
                            ->label('Ngân hàng')
                            ->placeholder('VD: Vietcombank, Techcombank, ...')
                            ->maxLength(255)
                            ->nullable(),
                        TextInput::make('bank_account')
                            ->label('Tài khoản ngân hàng')
                            ->placeholder('Số tài khoản nhận chuyển khoản')
                            ->maxLength(50)
                            ->nullable(),
                    ])
                    ->columns(2)
                    ->collapsed(),

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
                    ->formatStateUsing(function ($state) {
                        if (empty($state)) return '';
                        // Tránh duplicate: nếu state đã là URL đầy đủ (chứa /ref/), chỉ lấy mã ref_id
                        $refCode = str_contains((string) $state, '/ref/')
                            ? Str::afterLast($state, '/')
                            : $state;
                        return request()->getSchemeAndHttpHost() . '/ref/' . $refCode;
                    })
                    ->dehydrateStateUsing(function ($state) {
                        if (empty($state)) return null;
                        // Luôn lưu DB chỉ mã ref_id (bỏ URL nếu user paste cả link)
                        return Str::afterLast($state, '/') ?: $state;
                    })
                    ->copyable()
                    ->helperText('Click vào field hoặc icon để copy link'),
                \Filament\Forms\Components\Select::make('organization_id')
                    ->label('Tổ chức')
                    ->relationship('organization', 'name')
                    ->required()
                    ->visible(fn() => Auth::user()?->role === 'super_admin'),
                // Đã loại bỏ chọn CTV cấp trên - hệ thống chỉ còn 1 cấp
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
                    ->formatStateUsing(fn($state) => match (true) {
                        $state === 'active', $state === 1, $state === '1' => 'Kích hoạt',
                        $state === 'pending', $state === 2, $state === '2' => 'Chờ duyệt',
                        $state === 'inactive', $state === 3, $state === '3' => 'Vô hiệu',
                        default => '—',
                    })
                    ->dehydrateStateUsing(function ($state) {
                        // Chuẩn hóa giá trị lưu DB: số (1,2,3) hoặc dữ liệu cũ -> enum đúng
                        return match (true) {
                            $state === 'active', $state === 1, $state === '1' => 'active',
                            $state === 'pending', $state === 2, $state === '2' => 'pending',
                            $state === 'inactive', $state === 3, $state === '3' => 'inactive',
                            default => \in_array((string) $state, ['active', 'pending', 'inactive'], true) ? $state : 'active',
                        };
                    })
                    ->helperText('Chọn trạng thái cho cộng tác viên')
                    ->native(false),
            ]);
    }
}
