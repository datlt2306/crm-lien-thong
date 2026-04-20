<?php

namespace App\Filament\Resources\CommissionPolicies\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Tabs;
use Illuminate\Support\Facades\Auth;

class CommissionPolicyForm {
    public static function configure(Schema $schema): Schema {
        return $schema
            ->components([
                // 1. Điều kiện áp dụng (Để lên đầu cho rõ ràng)
                Section::make('Điều kiện áp dụng')
                    ->description('Xác định đối tượng và chương trình áp dụng chính sách này.')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('collaborator_id')
                                    ->label('Cộng tác viên')
                                    ->relationship('collaborator', 'full_name')
                                    ->searchable()
                                    ->preload()
                                    ->helperText('Để trống để áp dụng cho tất cả CTV')
                                    ->nullable(),
                                Select::make('program_type')
                                    ->label('Hệ đào tạo (Nhóm)')
                                    ->multiple()
                                    ->options([
                                        'REGULAR' => 'Chính quy',
                                        'PART_TIME' => 'Vừa học vừa làm',
                                        'DISTANCE' => 'Từ xa',
                                    ])
                                    ->reactive()
                                    ->afterStateUpdated(fn ($set) => $set('target_program_id', null))
                                    ->nullable()
                                    ->helperText('Chọn một hoặc nhiều Hệ đào tạo (Để trống nếu áp dụng tất cả)'),
                                Select::make('target_program_id')
                                    ->label('Ngành học cụ thể')
                                    ->options(fn() => \App\Models\Major::where('is_active', true)->pluck('name', 'name'))
                                    ->searchable()
                                    ->preload()
                                    ->reactive()
                                    ->afterStateUpdated(fn ($set) => $set('program_type', null))
                                    ->hidden(fn ($get) => !empty($get('program_type')))
                                    ->nullable()
                                    ->helperText('Để trống để áp dụng cho TẤT CẢ Ngành học'),
                            ]),
                    ]),

                // 2. Gói chia tiền (Trung tâm)
                Section::make('Gói chia tiền (Khuyên dùng)')
                    ->description('Thiết lập danh sách nhiều người nhận hoa hồng theo từng hệ.')
                    ->schema([
                        Tabs::make('Rules')
                            ->tabs(function ($get) {
                                $selectedTypes = $get('program_type') ?: [];
                                $options = [
                                    'REGULAR' => '🎯 Chính quy',
                                    'PART_TIME' => '🕒 Vừa học vừa làm',
                                    'DISTANCE' => '🌐 Từ xa',
                                ];

                                if (empty($selectedTypes)) {
                                    return [
                                        Tabs\Tab::make('Mặc định')
                                            ->schema([
                                                static::getRulesRepeater('default', 'Tất cả hệ đào tạo')
                                            ])
                                    ];
                                }

                                return collect($selectedTypes)->map(function ($type) use ($options) {
                                    return Tabs\Tab::make($options[$type] ?? $type)
                                        ->schema([
                                            static::getRulesRepeater($type, $options[$type] ?? $type)
                                        ]);
                                })->toArray();
                            })
                    ]),

                // 3. Cài đặt nâng cao (Cuối cùng)
                Section::make('Cài đặt nâng cao')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('priority')
                                    ->label('Độ ưu tiên')
                                    ->numeric()
                                    ->default(0),
                                Toggle::make('active')
                                    ->label('Kích hoạt')
                                    ->default(true),
                            ]),
                    ]),
            ]);
    }

    protected static function getRulesRepeater(string $type, string $label): Repeater {
        return Repeater::make("payout_rules.{$type}")
            ->label("Quy tắc chia tiền - {$label}")
            ->schema([
                Grid::make(2)
                    ->schema([
                        Select::make('recipient_type')
                            ->label('Người nhận')
                            ->options([
                                'direct_ctv' => '🎯 CTV giới thiệu (Trực tiếp)',
                                'specific_ctv' => '👤 Một CTV cụ thể (Quản lý/Bạn)',
                            ])
                            ->required()
                            ->reactive(),
                        Select::make('recipient_id')
                            ->label('Chọn CTV cụ thể')
                            ->relationship('collaborator', 'full_name')
                            ->searchable()
                            ->preload()
                            ->visible(fn($get) => $get('recipient_type') === 'specific_ctv')
                            ->required(fn($get) => $get('recipient_type') === 'specific_ctv'),
                    ]),
                Grid::make(2)
                    ->schema([
                        TextInput::make('amount_vnd')
                            ->label('Số tiền (VND)')
                            ->numeric()
                            ->required()
                            ->prefix('₫'),
                        Select::make('payout_trigger')
                            ->label('Thời điểm trả')
                            ->options([
                                'payment_verified' => '📅 Mùng 5 tháng sau',
                                'student_enrolled' => '🎓 Sau khi nhập học',
                            ])
                            ->required()
                            ->default('payment_verified'),
                    ]),
                TextInput::make('description')
                    ->label('Ghi chú')
                    ->placeholder('Vd: Tiền cắt phế...'),
            ])
            ->addActionLabel('Thêm người nhận tiền')
            ->itemLabel(fn (array $state): ?string => ($state['recipient_type'] ?? '') === 'direct_ctv' ? '🎯 CTV Trực tiếp' : '👤 CTV Cụ thể')
            ->collapsible()
            ->defaultItems(1);
    }
}
