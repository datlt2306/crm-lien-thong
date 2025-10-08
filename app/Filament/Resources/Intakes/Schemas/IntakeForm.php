<?php

namespace App\Filament\Resources\Intakes\Schemas;

use App\Models\Organization;
use App\Models\Program;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;

class IntakeForm {
    public static function configure(Schema $schema): Schema {
        return $schema
            ->schema([
                Section::make('📅 Thông tin đợt tuyển sinh')
                    ->description('Thiết lập thông tin cơ bản cho đợt tuyển sinh')
                    ->icon('heroicon-o-calendar-days')
                    ->schema([
                        TextInput::make('name')
                            ->label('🎯 Tên đợt tuyển sinh')
                            ->required()
                            ->placeholder('VD: Đợt 1 - Học kỳ I 2025')
                            ->maxLength(255)
                            ->helperText('Tên đợt tuyển sinh sẽ hiển thị trong danh sách và báo cáo'),

                        Textarea::make('description')
                            ->label('📝 Mô tả chi tiết')
                            ->placeholder('Mô tả chi tiết về đợt tuyển sinh...')
                            ->rows(3)
                            ->helperText('Mô tả về mục tiêu, đối tượng tuyển sinh hoặc yêu cầu đặc biệt')
                            ->columnSpanFull(),

                        Select::make('organization_id')
                            ->label('🏢 Tổ chức')
                            ->relationship('organization', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->placeholder('Chọn tổ chức...')
                            ->helperText('Tổ chức sẽ quản lý đợt tuyển sinh này'),

                        Select::make('program_id')
                            ->label('🎓 Chương trình đào tạo')
                            ->options(Program::where('is_active', true)->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->placeholder('Chọn chương trình đào tạo (tùy chọn)')
                            ->helperText('Chương trình đào tạo cho đợt tuyển sinh này'),

                        Select::make('status')
                            ->label('📋 Trạng thái')
                            ->options(\App\Models\Intake::getStatusOptions())
                            ->required()
                            ->default(\App\Models\Intake::STATUS_UPCOMING)
                            ->placeholder('Chọn trạng thái...')
                            ->helperText('Trạng thái hiện tại của đợt tuyển sinh'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('⏰ Thời gian tuyển sinh')
                    ->description('Thiết lập lịch trình tuyển sinh')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        DatePicker::make('start_date')
                            ->label('📅 Ngày bắt đầu tuyển sinh')
                            ->required()
                            ->displayFormat('d/m/Y')
                            ->native(false)
                            ->placeholder('Chọn ngày bắt đầu...')
                            ->helperText('Ngày bắt đầu nhận hồ sơ tuyển sinh'),

                        DatePicker::make('end_date')
                            ->label('📅 Ngày kết thúc tuyển sinh')
                            ->required()
                            ->displayFormat('d/m/Y')
                            ->native(false)
                            ->after('start_date')
                            ->placeholder('Chọn ngày kết thúc...')
                            ->helperText('Ngày cuối cùng nhận hồ sơ tuyển sinh'),

                        DatePicker::make('enrollment_deadline')
                            ->label('📅 Hạn chót nhập học')
                            ->displayFormat('d/m/Y')
                            ->native(false)
                            ->after('end_date')
                            ->placeholder('Chọn hạn chót nhập học...')
                            ->helperText('Hạn chót để học viên hoàn tất nhập học'),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Section::make('⚙️ Cài đặt bổ sung')
                    ->description('Thiết lập các cài đặt nâng cao cho đợt tuyển sinh')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->schema([
                        Textarea::make('settings')
                            ->label('📋 Cài đặt JSON')
                            ->placeholder('{"application_fee": 100000, "required_documents": ["cmnd", "hoc_ba"]}')
                            ->helperText('Cài đặt bổ sung cho đợt tuyển sinh (JSON format). VD: phí đăng ký, tài liệu yêu cầu...')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
