<?php

namespace App\Filament\Resources\Students\Schemas;

use App\Models\Organization;
use App\Models\Student;
use App\Models\Payment;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Tabs;
use Illuminate\Support\Facades\Auth;

class StudentForm {
    public static function configure(Schema $schema): Schema {
        return $schema
            ->columns(12)
            ->schema([
                // Left section: 8 columns - các tab thông tin & upload giấy tờ
                Tabs::make('StudentInformation')
                    ->columnSpan(8)
                    ->tabs([
                        // Tab 1: Thông tin cơ bản
                        Tabs\Tab::make('Thông tin cơ bản')
                            ->icon('heroicon-o-identification')
                            ->schema([
                                TextInput::make('full_name')
                                    ->label('Họ và tên')
                                    ->required(),
                                TextInput::make('instructor')
                                    ->label('GVHD')
                                    ->helperText('Nhập tên giáo viên hướng dẫn (nếu có)')
                                    ->maxLength(255),
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
                                    ->helperText('Nhập địa chỉ của sinh viên')
                                    ->columnSpanFull(),
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
                                \Filament\Forms\Components\Placeholder::make('fee_status')
                                    ->label('Lệ phí')
                                    ->content(function (?Student $record) {
                                        if (!$record?->payment) {
                                            return 'Chưa có thông tin lệ phí';
                                        }

                                        $amount = $record->payment->amount ?? 0;

                                        $statusLabel = match ($record->payment->status) {
                                            Payment::STATUS_NOT_PAID => 'Chưa nộp',
                                            Payment::STATUS_SUBMITTED => 'Đã nộp, chờ xác minh',
                                            Payment::STATUS_VERIFIED => 'Đã nộp tiền',
                                            default => $record->payment->status,
                                        };

                                        if ($amount <= 0) {
                                            return "Chưa cập nhật số tiền - {$statusLabel}";
                                        }

                                        $formattedAmount = number_format($amount, 0, ',', '.');
                                        return "{$formattedAmount} đ - {$statusLabel}";
                                    })
                                    ->columnSpanFull()
                                    ->visible(fn() => Auth::user()?->role !== 'ctv'),
                                Textarea::make('notes')
                                    ->label('Ghi chú')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),

                        // Tab 2: Thông tin cá nhân
                        Tabs\Tab::make('Thông tin cá nhân')
                            ->icon('heroicon-o-user')
                            ->visible(fn() => Auth::user()?->role !== 'ctv')
                            ->schema([
                                \Filament\Forms\Components\DatePicker::make('dob')
                                    ->label('Ngày sinh')
                                    ->displayFormat('d/m/Y')
                                    ->helperText('Chọn ngày tháng năm sinh của sinh viên'),
                                TextInput::make('birth_place')
                                    ->label('Nơi sinh')
                                    ->maxLength(255),
                                Textarea::make('permanent_residence')
                                    ->label('Hộ khẩu thường trú')
                                    ->rows(2)
                                    ->columnSpanFull(),
                                TextInput::make('ethnicity')
                                    ->label('Dân tộc')
                                    ->maxLength(100),
                                Select::make('gender')
                                    ->label('Giới tính')
                                    ->options([
                                        'male' => 'Nam',
                                        'female' => 'Nữ',
                                        'other' => 'Khác',
                                    ]),
                                TextInput::make('identity_card')
                                    ->label('Số CCCD')
                                    ->helperText('Nhập số căn cước công dân của học viên')
                                    ->unique(ignoreRecord: true)
                                    ->validationMessages([
                                        'unique' => 'Số căn cước công dân đã được sử dụng bởi học viên khác.',
                                    ]),
                                \Filament\Forms\Components\DatePicker::make('identity_card_issue_date')
                                    ->label('Ngày cấp CCCD')
                                    ->displayFormat('d/m/Y'),
                                TextInput::make('identity_card_issue_place')
                                    ->label('Nơi cấp CCCD')
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),

                        // Tab 3: Thông tin THPT
                        Tabs\Tab::make('Thông tin THPT')
                            ->icon('heroicon-o-academic-cap')
                            ->visible(fn() => Auth::user()?->role !== 'ctv')
                            ->schema([
                                TextInput::make('high_school_name')
                                    ->label('Tên trường THPT')
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                                TextInput::make('high_school_code')
                                    ->label('Mã trường')
                                    ->maxLength(50),
                                TextInput::make('high_school_province')
                                    ->label('Tên tỉnh/TP')
                                    ->maxLength(255),
                                TextInput::make('high_school_province_code')
                                    ->label('Mã tỉnh')
                                    ->maxLength(50),
                                TextInput::make('high_school_district')
                                    ->label('Tên quận/huyện')
                                    ->maxLength(255),
                                TextInput::make('high_school_district_code')
                                    ->label('Mã quận/huyện')
                                    ->maxLength(50),
                                TextInput::make('priority_area')
                                    ->label('Khu vực ưu tiên')
                                    ->maxLength(50),
                                \Filament\Forms\Components\TextInput::make('high_school_graduation_year')
                                    ->label('Năm tốt nghiệp THPT')
                                    ->numeric()
                                    ->minValue(1900)
                                    ->maxValue(2100),
                                Select::make('high_school_academic_performance')
                                    ->label('Học lực cả năm')
                                    ->options([
                                        'Giỏi' => 'Giỏi',
                                        'Khá' => 'Khá',
                                        'Trung bình' => 'Trung bình',
                                        'Yếu' => 'Yếu',
                                    ]),
                                Select::make('high_school_conduct')
                                    ->label('Hạnh kiểm')
                                    ->options([
                                        'Tốt' => 'Tốt',
                                        'Khá' => 'Khá',
                                        'Trung bình' => 'Trung bình',
                                        'Yếu' => 'Yếu',
                                    ]),
                            ])
                            ->columns(3),

                        // Tab 4: Thông tin văn bằng Cao đẳng
                        Tabs\Tab::make('Thông tin văn bằng Cao đẳng')
                            ->icon('heroicon-o-document-text')
                            ->visible(fn() => Auth::user()?->role !== 'ctv')
                            ->schema([
                                TextInput::make('college_graduation_school')
                                    ->label('Trường tốt nghiệp CĐ')
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                                TextInput::make('college_graduation_major')
                                    ->label('Ngành tốt nghiệp CĐ')
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                                Select::make('college_graduation_grade')
                                    ->label('Xếp loại')
                                    ->options([
                                        'Xuất sắc' => 'Xuất sắc',
                                        'Giỏi' => 'Giỏi',
                                        'Khá' => 'Khá',
                                        'Trung bình' => 'Trung bình',
                                    ]),
                                Select::make('college_training_type')
                                    ->label('Hệ đào tạo tốt nghiệp')
                                    ->options([
                                        'Chính quy' => 'Chính quy',
                                        'Vừa học vừa làm' => 'Vừa học vừa làm',
                                        'Từ xa' => 'Từ xa',
                                        'Khác' => 'Khác',
                                    ]),
                                \Filament\Forms\Components\TextInput::make('college_graduation_year')
                                    ->label('Năm tốt nghiệp')
                                    ->numeric()
                                    ->minValue(1900)
                                    ->maxValue(2100),
                                TextInput::make('college_diploma_number')
                                    ->label('Số hiệu bằng TN CĐ')
                                    ->maxLength(255),
                                TextInput::make('college_diploma_book_number')
                                    ->label('Số vào sổ cấp bằng TN CĐ')
                                    ->maxLength(255),
                                \Filament\Forms\Components\DatePicker::make('college_diploma_issue_date')
                                    ->label('Ngày ký bằng TN CĐ')
                                    ->displayFormat('d/m/Y'),
                                TextInput::make('college_diploma_signer')
                                    ->label('Người ký bằng TN CĐ')
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                            ])
                            ->columns(3),

                        // Tab 5: Thông tin văn bằng Trung cấp
                        Tabs\Tab::make('Thông tin văn bằng Trung cấp')
                            ->icon('heroicon-o-document')
                            ->visible(fn() => Auth::user()?->role !== 'ctv')
                            ->schema([
                                TextInput::make('intermediate_graduation_school')
                                    ->label('Trường tốt nghiệp TC')
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                                TextInput::make('intermediate_graduation_major')
                                    ->label('Ngành tốt nghiệp TC')
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                                Select::make('intermediate_graduation_grade')
                                    ->label('Xếp loại')
                                    ->options([
                                        'Xuất sắc' => 'Xuất sắc',
                                        'Giỏi' => 'Giỏi',
                                        'Khá' => 'Khá',
                                        'Trung bình' => 'Trung bình',
                                    ]),
                                Select::make('intermediate_training_type')
                                    ->label('Hệ đào tạo tốt nghiệp')
                                    ->options([
                                        'Chính quy' => 'Chính quy',
                                        'Vừa học vừa làm' => 'Vừa học vừa làm',
                                        'Từ xa' => 'Từ xa',
                                        'Khác' => 'Khác',
                                    ]),
                                \Filament\Forms\Components\TextInput::make('intermediate_graduation_year')
                                    ->label('Năm tốt nghiệp')
                                    ->numeric()
                                    ->minValue(1900)
                                    ->maxValue(2100),
                                TextInput::make('intermediate_diploma_number')
                                    ->label('Số hiệu bằng TN TC')
                                    ->maxLength(255),
                                TextInput::make('intermediate_diploma_book_number')
                                    ->label('Số vào sổ cấp bằng TN TC')
                                    ->maxLength(255),
                                \Filament\Forms\Components\DatePicker::make('intermediate_diploma_issue_date')
                                    ->label('Ngày ký bằng TN TC')
                                    ->displayFormat('d/m/Y'),
                                TextInput::make('intermediate_diploma_signer')
                                    ->label('Người ký bằng TN TC')
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                            ])
                            ->columns(3),

                    ]),

                // Right section: 4 columns - checklist hồ sơ nhập học
                Tabs::make('StudentChecklist')
                    ->columnSpan(4)
                    ->tabs([
                        Tabs\Tab::make('Checklist hồ sơ nhập học')
                            ->icon('heroicon-o-check-circle')
                            ->visible(fn() => Auth::user()?->role !== 'ctv')
                            ->schema([
                                \Filament\Forms\Components\CheckboxList::make('document_checklist')
                                    ->label('Danh sách giấy tờ')
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
                            ]),
                    ]),
            ]);
    }
}
