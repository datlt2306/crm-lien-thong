<?php

namespace App\Filament\Resources\Quotas\Schemas;

use App\Models\Intake;
use App\Models\Major;
use App\Models\Organization;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;

class QuotaForm {
    public static function configure(Schema $schema): Schema {
        return $schema
            ->schema([
                Section::make('📅 Thông tin đợt tuyển sinh')
                    ->description('Chọn đợt tuyển sinh và tổ chức')
                    ->icon('heroicon-o-calendar-days')
                    ->schema([
                        Select::make('intake_id')
                            ->label('Đợt tuyển sinh')
                            ->relationship('intake', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $intake = Intake::find($state);
                                    if ($intake) {
                                        $set('organization_id', $intake->organization_id);
                                    }
                                }
                            })
                            ->getOptionLabelFromRecordUsing(function (\App\Models\Intake $record): string {
                                $program = $record->program ? ' (' . $record->program->name . ')' : '';
                                return $record->name . $program;
                            })
                            ->placeholder('Chọn đợt tuyển sinh...')
                            ->helperText('Chọn đợt tuyển sinh để tạo chỉ tiêu'),

                        Select::make('program_id')
                            ->label('Hệ đào tạo')
                            ->relationship('program', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Chọn hệ đào tạo...')
                            ->helperText('Chọn hệ đào tạo cho chỉ tiêu này'),

                        Select::make('organization_id')
                            ->label('Tổ chức')
                            ->relationship('organization', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->placeholder('Chọn tổ chức...')
                            ->helperText('Tổ chức sẽ được tự động chọn theo đợt tuyển sinh'),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Section::make('🎓 Thông tin ngành học')
                    ->description('Chọn ngành học cần tuyển sinh')
                    ->icon('heroicon-o-academic-cap')
                    ->schema([
                        Select::make('major_id')
                            ->label('Ngành học')
                            ->relationship('major', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->placeholder('Chọn ngành học...')
                            ->helperText('Chọn ngành học cần tuyển sinh')
                            ->getOptionLabelFromRecordUsing(fn(\App\Models\Major $record): string => $record->code . ' - ' . $record->name),
                    ])
                    ->columns(1)
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
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
