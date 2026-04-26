<?php

namespace App\Filament\Resources\Quotas\Schemas;


use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Schema as SchemaFacade;

class QuotaForm {
    public static function configure(Schema $schema): Schema {
        return $schema
            ->schema([
                Section::make('📅 Thông tin đợt tuyển sinh')
                    ->description('Điền tên đợt, khoảng thời gian và tổ chức — tạo/chỉnh sửa trực tiếp tại đây')
                    ->icon('heroicon-o-calendar-days')
                    ->schema([
                        TextInput::make('intake_name')
                            ->label('Tên đợt tuyển sinh')
                            ->required()
                            ->placeholder('VD: Đợt 1 - Học kỳ I 2026')
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->helperText('Đặt tên đợt tuyển sinh'),

                        DatePicker::make('intake_start_date')
                            ->label('Từ ngày')
                            ->required()
                            ->displayFormat('d/m/Y')
                            ->native(false)
                            ->helperText('Ngày bắt đầu nhận hồ sơ'),

                        DatePicker::make('intake_end_date')
                            ->label('Đến ngày')
                            ->required()
                            ->displayFormat('d/m/Y')
                            ->native(false)
                            ->after('intake_start_date')
                            ->helperText('Ngày cuối cùng nhận hồ sơ'),


                        TextInput::make('name')
                            ->label('Tên chương trình tuyển sinh')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('VD: Ngôn ngữ Anh - Đại học từ xa')
                            ->columnSpanFull()
                            ->helperText('Tên gọi hiển thị của chỉ tiêu/chương trình này'),

                        Select::make('major_name')
                            ->label('Ngành học')
                            ->options(fn() => SchemaFacade::hasTable('majors')
                                ? \App\Models\Major::query()
                                    ->where('is_active', true)
                                    ->orderBy('name')
                                    ->pluck('name', 'name')
                                : [])
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('program_name')
                            ->label('Hệ đào tạo')
                            ->options(function () {
                                $base = [
                                    'regular' => 'Chính quy',
                                    'part_time' => 'Vừa học vừa làm',
                                    'distance' => 'Đào tạo từ xa',
                                    'Chính quy' => 'Chính quy',
                                    'Vừa học vừa làm' => 'Vừa học vừa làm',
                                    'Đào tạo từ xa' => 'Đào tạo từ xa',
                                ];

                                if (!SchemaFacade::hasTable('programs')) {
                                    return $base;
                                }

                                $fromPrograms = \App\Models\Program::query()
                                    ->where('is_active', true)
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(fn($program) => [$program->code => $program->name])
                                    ->toArray();

                                return array_merge($base, $fromPrograms);
                            })
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
                    ->columns(2)
                    ->columnSpanFull()
                    ->collapsible(),



                Section::make('📊 Chỉ tiêu tuyển sinh')
                    ->description('Thiết lập chỉ tiêu và học phí cho ngành học')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->schema([
                        TextInput::make('target_quota')
                            ->label('🎯 Chỉ tiêu mục tiêu')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->default(0)
                            ->suffix('học viên')
                            ->placeholder('Nhập số lượng dự kiến...')
                            ->helperText('Số lượng học viên dự kiến tuyển sinh cho ngành này')
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $target = (int) $state;
                                $current = (int) $get('current_quota');
                                $pending = (int) $get('pending_quota');
                                $reserved = (int) $get('reserved_quota');
                                $available = $target - $current - $pending - $reserved;
                                $set('available_slots', max(0, $available));
                            }),

                        TextInput::make('current_quota')
                            ->label('✅ Đã nhập học')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->suffix('học viên')
                            ->placeholder('0')
                            ->helperText('Số lượng học viên đã nhập học chính thức'),

                        TextInput::make('pending_quota')
                            ->label('⏳ Đang chờ xử lý')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->suffix('hồ sơ')
                            ->placeholder('0')
                            ->helperText('Số lượng hồ sơ đang chờ xét duyệt'),

                        TextInput::make('reserved_quota')
                            ->label('💰 Đã đặt cọc')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->suffix('học viên')
                            ->placeholder('0')
                            ->helperText('Số lượng học viên đã đặt cọc giữ chỗ'),

                        TextInput::make('tuition_fee')
                            ->label('💵 Học phí')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('VNĐ')
                            ->placeholder('Nhập học phí...')
                            ->helperText('Học phí cho ngành này trong đợt tuyển sinh'),

                        Select::make('status')
                            ->label('📋 Trạng thái')
                            ->options(\App\Models\Quota::getStatusOptions())
                            ->required()
                            ->default(\App\Models\Quota::STATUS_ACTIVE)
                            ->placeholder('Chọn trạng thái...')
                            ->helperText('Trạng thái hoạt động của chỉ tiêu này'),
                    ])
                    ->columns(3)
                    ->columnSpanFull()
                    ->collapsible(),

                Section::make('📝 Ghi chú bổ sung')
                    ->description('Thêm ghi chú hoặc lưu ý đặc biệt')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Nội dung ghi chú')
                            ->placeholder('Nhập ghi chú bổ sung về chỉ tiêu tuyển sinh...')
                            ->rows(4)
                            ->helperText('Có thể ghi chú về điều kiện đặc biệt, yêu cầu bổ sung, hoặc lưu ý quan trọng')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull()
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
