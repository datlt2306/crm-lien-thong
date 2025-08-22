<?php

namespace App\Filament\Resources\CommissionPolicies\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class CommissionPolicyForm {
    public static function configure(Schema $schema): Schema {
        return $schema
            ->components([
                Select::make('organization_id')
                    ->label('Tổ chức')
                    ->relationship('organization', 'name')
                    ->searchable()
                    ->preload()
                    ->helperText('Để trống để áp dụng cho tất cả tổ chức')
                    ->nullable(),
                Select::make('collaborator_id')
                    ->label('Cộng tác viên')
                    ->relationship('collaborator', 'full_name')
                    ->searchable()
                    ->preload()
                    ->helperText('Để trống để áp dụng cho tất cả CTV')
                    ->nullable(),
                Select::make('program_type')
                    ->label('Hệ đào tạo')
                    ->options([
                        'REGULAR' => 'Chính quy',
                        'PART_TIME' => 'Bán thời gian',
                    ])
                    ->helperText('Để trống để áp dụng cho tất cả loại chương trình')
                    ->nullable(),
                Select::make('role')
                    ->label('Vai trò')
                    ->options([
                        'PRIMARY' => 'CTV chính',
                        'SUB' => 'CTV phụ',
                    ])
                    ->helperText('Để trống để áp dụng cho tất cả vai trò')
                    ->nullable(),
                Select::make('type')
                    ->label('Loại hoa hồng')
                    ->options([
                        'FIXED' => 'Cố định (VND)',
                        'PERCENT' => 'Phần trăm (%)',
                        'PASS_THROUGH' => 'Chuyển tiếp (100%)',
                    ])
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state === 'FIXED') {
                            $set('percent', null);
                        } elseif ($state === 'PERCENT') {
                            $set('amount_vnd', null);
                        } else {
                            $set('amount_vnd', null);
                            $set('percent', null);
                        }
                    }),
                TextInput::make('amount_vnd')
                    ->label('Số tiền cố định (VND)')
                    ->numeric()
                    ->visible(fn($get) => $get('type') === 'FIXED')
                    ->required(fn($get) => $get('type') === 'FIXED')
                    ->helperText('Số tiền hoa hồng cố định'),
                TextInput::make('percent')
                    ->label('Phần trăm (%)')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->visible(fn($get) => $get('type') === 'PERCENT')
                    ->required(fn($get) => $get('type') === 'PERCENT')
                    ->helperText('Phần trăm hoa hồng (0-100%)'),
                Select::make('trigger')
                    ->label('Thời điểm kích hoạt')
                    ->options([
                        'ON_VERIFICATION' => 'Khi xác nhận thanh toán',
                        'ON_ENROLLMENT' => 'Khi học viên nhập học',
                    ])
                    ->helperText('Để trống để sử dụng mặc định')
                    ->nullable(),
                Select::make('visibility')
                    ->label('Hiển thị')
                    ->options([
                        'INTERNAL' => 'Nội bộ (chỉ admin)',
                        'ORG_ONLY' => 'Tổ chức (admin + chủ tổ chức)',
                    ])
                    ->helperText('Để trống để sử dụng mặc định')
                    ->nullable(),
                TextInput::make('priority')
                    ->label('Độ ưu tiên')
                    ->numeric()
                    ->default(0)
                    ->helperText('Số càng cao càng ưu tiên (mặc định: 0)'),
                Toggle::make('active')
                    ->label('Kích hoạt')
                    ->default(true)
                    ->helperText('Bật để kích hoạt chính sách này'),
                DatePicker::make('effective_from')
                    ->label('Có hiệu lực từ')
                    ->helperText('Để trống để có hiệu lực ngay')
                    ->nullable(),
                DatePicker::make('effective_to')
                    ->label('Có hiệu lực đến')
                    ->helperText('Để trống để không giới hạn thời gian')
                    ->nullable(),
                Textarea::make('meta')
                    ->label('Thông tin bổ sung')
                    ->helperText('Thông tin bổ sung về chính sách (JSON)')
                    ->nullable(),
            ]);
    }
}
