<?php

namespace App\Filament\Resources\Students\Schemas;

use App\Models\Organization;
use App\Models\Student;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class StudentForm {
    public static function configure(Schema $schema): Schema {
        return $schema
            ->components([
                TextInput::make('full_name')
                    ->label('Họ và tên')
                    ->required(),
                TextInput::make('phone')
                    ->label('Số điện thoại')
                    ->tel()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->validationMessages([
                        'unique' => 'Số điện thoại đã được sử dụng bởi học viên khác.',
                    ]),
                TextInput::make('email')
                    ->label('Email')
                    ->email(),
                TextInput::make('identity_card')
                    ->label('Căn cước công dân')
                    ->helperText('Nhập số căn cước công dân của học viên')
                    ->unique(ignoreRecord: true)
                    ->validationMessages([
                        'unique' => 'Số căn cước công dân đã được sử dụng bởi học viên khác.',
                    ]),
                Select::make('organization_id')
                    ->label('Tổ chức')
                    ->options(fn() => Organization::orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->visible(fn() => Auth::user()?->role === 'super_admin')
                    ->required(fn() => Auth::user()?->role === 'super_admin')
                    ->helperText('Chọn tổ chức quản lý học viên')
                    ->columnSpanFull(),
                \Filament\Forms\Components\Select::make('major')
                    ->label('Ngành đăng ký học')
                    ->options(\App\Models\Major::where('is_active', true)->orderBy('name')->pluck('name', 'name')->toArray())
                    ->searchable()
                    ->required(),
                \Filament\Forms\Components\Select::make('program_type')
                    ->label('Hệ liên thông')
                    ->options([
                        'REGULAR' => 'Chính quy',
                        'PART_TIME' => 'Vừa học vừa làm',
                    ])
                    ->helperText('Chọn hệ dự kiến cho sinh viên'),
                \Filament\Forms\Components\DatePicker::make('dob')
                    ->label('Ngày sinh')
                    ->displayFormat('d/m/Y')
                    ->helperText('Chọn ngày tháng năm sinh của sinh viên'),
                \Filament\Forms\Components\Select::make('intake_month')
                    ->label('Đợt tuyển')
                    ->options([
                        1 => 'Tháng 1',
                        2 => 'Tháng 2',
                        3 => 'Tháng 3',
                        4 => 'Tháng 4',
                        5 => 'Tháng 5',
                        6 => 'Tháng 6',
                        7 => 'Tháng 7',
                        8 => 'Tháng 8',
                        9 => 'Tháng 9',
                        10 => 'Tháng 10',
                        11 => 'Tháng 11',
                        12 => 'Tháng 12',
                    ])
                    ->helperText('Chọn tháng tuyển sinh dự kiến'),
                \Filament\Forms\Components\Textarea::make('address')
                    ->label('Địa chỉ')
                    ->rows(3)
                    ->helperText('Nhập địa chỉ của sinh viên'),
                \Filament\Forms\Components\Select::make('source')
                    ->label('Nguồn')
                    ->options([
                        'form' => 'Form website',
                        'ref' => 'Giới thiệu (CTV/Đối tác)',
                        'facebook' => 'Facebook',
                        'zalo' => 'Zalo',
                        'tiktok' => 'TikTok',
                        'hotline' => 'Hotline',
                        'event' => 'Sự kiện',
                        'school' => 'Trường THPT/Trung tâm',
                        'walkin' => 'Đến trực tiếp',
                        'other' => 'Khác',
                    ])
                    ->required()
                    ->default('form'),
                Textarea::make('notes')
                    ->label('Ghi chú')
                    ->columnSpanFull(),

                // Checklist hồ sơ nhập học
                \Filament\Forms\Components\CheckboxList::make('document_checklist')
                    ->label('✅ Checklist hồ sơ nhập học')
                    ->options([
                        'phieu_tuyen_sinh' => '📄 Phiếu tuyển sinh hệ CQ hoặc VHVL',
                        'phieu_xet_tuyen' => '📄 Phiếu xét tuyển hệ đào tạo từ xa (Xã phường hoặc cơ quan đang làm việc đóng dấu)',
                        'bang_cao_dang' => '📄 01 Bản sao công chứng hợp lệ bằng tốt nghiệp Cao đẳng',
                        'bang_thpt' => '📄 01 Bản sao công chứng bằng tốt nghiệp THPT',
                        'bang_diem' => '📄 01 Bản công chứng giấy chứng nhận kết quả học tập (Bảng điểm)',
                        'giay_khai_sinh' => '📄 01 Bản sao công chứng hợp lệ giấy khai sinh',
                        'cccd' => '📄 01 Bản sao công chứng căn cước công dân',
                        'giay_kham_suc_khoe' => '📷 Giấy khám đủ sức khỏe (cấp bởi Bệnh viện hoặc TTYT công lập cấp quận/huyện trở lên) - Giấy A3, bản gốc',
                        'anh_4x6' => '📷 04 ảnh chân dung 4x6 cm (Chụp trong vòng 6 tháng trở lại)',
                    ])
                    ->columns(1)
                    ->gridDirection('row')
                    ->bulkToggleable()
                    ->helperText('Đánh dấu các giấy tờ mà học viên đã nộp đầy đủ')
                    ->columnSpanFull(),
            ]);
    }
}
