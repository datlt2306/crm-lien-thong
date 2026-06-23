<?php

namespace App\Filament\Resources\Students\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class StudentInfolist {
    private static function getProgramLabel(?string $programCode): string {
        return match (strtolower((string) $programCode)) {
            'regular' => 'Chính quy',
            'part_time' => 'Vừa học vừa làm',
            'distance' => 'Đào tạo từ xa',
            default => $programCode ?: 'Chưa điền',
        };
    }

    public static function configure(Schema $schema): Schema {
        TextEntry::configureUsing(fn (TextEntry $entry) => $entry->placeholder('Chưa điền'));

        return $schema
            ->columns(12)
            ->components([
                // Left section: 9 columns (or 12 for collaborator)
                Tabs::make('StudentInformation')
                    ->columnSpan(fn() => Auth::user()?->role === 'collaborator' ? 12 : 9)
                    ->tabs([
                        Tab::make('Thông tin cơ bản')
                            ->icon('heroicon-o-identification')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextEntry::make('profile_code')
                                            ->label('Mã hồ sơ'),
                                        TextEntry::make('full_name')
                                            ->label('Họ và tên'),
                                        TextEntry::make('collaborator.full_name')
                                            ->label('Người giới thiệu'),
                                        TextEntry::make('phone')
                                            ->label('Số điện thoại'),
                                        TextEntry::make('email')
                                            ->label('Email'),
                                        TextEntry::make('intake.name')
                                            ->label('Đợt đăng ký liên thông')
                                            ->formatStateUsing(fn($state, $record) => $state ?: ($record->intake_month ? "Tháng {$record->intake_month}" : 'Chưa điền')),
                                        TextEntry::make('major')
                                            ->label('Ngành đăng ký liên thông'),
                                        TextEntry::make('program_type')
                                            ->label('Hệ liên thông / Hệ đào tạo')
                                            ->formatStateUsing(fn($state) => self::getProgramLabel($state)),
                                        TextEntry::make('source')
                                            ->label('Hình thức tuyển sinh')
                                            ->formatStateUsing(fn($state) => match ($state) {
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
                                                default => $state
                                            }),
                                        TextEntry::make('status')
                                            ->label('Trạng thái'),
                                        TextEntry::make('address')
                                            ->label('Địa chỉ'),
                                        TextEntry::make('notes')
                                            ->label('Ghi chú')
                                            ->columnSpanFull(),
                                    ])
                            ]),

                        Tab::make('Thông tin cá nhân')
                            ->icon('heroicon-o-user')
                            ->visible(fn() => Auth::user()?->role !== 'collaborator')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextEntry::make('dob')
                                            ->label('Ngày sinh')
                                            ->date('d/m/Y'),
                                        TextEntry::make('birth_place')
                                            ->label('Nơi sinh'),
                                        TextEntry::make('gender')
                                            ->label('Giới tính')
                                            ->formatStateUsing(fn($state) => match ($state) {
                                                'male' => 'Nam',
                                                'female' => 'Nữ',
                                                'other' => 'Khác',
                                                default => 'Chưa điền'
                                            }),
                                        TextEntry::make('ethnicity')
                                            ->label('Dân tộc'),
                                        TextEntry::make('identity_card')
                                            ->label('Số CCCD'),
                                        TextEntry::make('identity_card_issue_date')
                                            ->label('Ngày cấp CCCD')
                                            ->date('d/m/Y'),
                                        TextEntry::make('identity_card_issue_place')
                                            ->label('Nơi cấp CCCD')
                                            ->columnSpanFull(),
                                        TextEntry::make('permanent_residence')
                                            ->label('Hộ khẩu thường trú')
                                            ->columnSpanFull(),
                                    ])
                            ]),

                        Tab::make('Thông tin THPT')
                            ->icon('heroicon-o-academic-cap')
                            ->visible(fn() => Auth::user()?->role !== 'collaborator')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        TextEntry::make('high_school_name')
                                            ->label('Tên trường THPT')
                                            ->columnSpanFull(),
                                        TextEntry::make('high_school_code')
                                            ->label('Mã trường'),
                                        TextEntry::make('high_school_province')
                                            ->label('Tên tỉnh/TP'),
                                        TextEntry::make('high_school_province_code')
                                            ->label('Mã tỉnh'),
                                        TextEntry::make('high_school_district')
                                            ->label('Tên quận/huyện'),
                                        TextEntry::make('high_school_district_code')
                                            ->label('Mã quận/huyện'),
                                        TextEntry::make('priority_area')
                                            ->label('Khu vực ưu tiên'),
                                        TextEntry::make('high_school_graduation_year')
                                            ->label('Năm tốt nghiệp THPT'),
                                        TextEntry::make('high_school_academic_performance')
                                            ->label('Học lực cả năm'),
                                        TextEntry::make('high_school_conduct')
                                            ->label('Hạnh kiểm'),
                                    ])
                            ]),

                        Tab::make('Thông tin văn bằng Cao đẳng')
                            ->icon('heroicon-o-document-text')
                            ->visible(fn() => Auth::user()?->role !== 'collaborator')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        TextEntry::make('college_graduation_school')
                                            ->label('Trường tốt nghiệp CĐ')
                                            ->columnSpanFull(),
                                        TextEntry::make('college_graduation_major')
                                            ->label('Ngành tốt nghiệp CĐ')
                                            ->columnSpanFull(),
                                        TextEntry::make('college_graduation_grade')
                                            ->label('Xếp loại'),
                                        TextEntry::make('college_training_type')
                                            ->label('Hệ đào tạo tốt nghiệp'),
                                        TextEntry::make('college_graduation_year')
                                            ->label('Năm tốt nghiệp'),
                                        TextEntry::make('college_diploma_number')
                                            ->label('Số hiệu bằng TN CĐ'),
                                        TextEntry::make('college_diploma_book_number')
                                            ->label('Số vào sổ cấp bằng TN CĐ'),
                                        TextEntry::make('college_diploma_issue_date')
                                            ->label('Ngày ký bằng TN CĐ')
                                            ->date('d/m/Y'),
                                        TextEntry::make('college_diploma_signer')
                                            ->label('Người ký bằng TN CĐ')
                                            ->columnSpanFull(),
                                        TextEntry::make('college_gpa')
                                            ->label('Điểm trung bình tích lũy toàn khóa'),
                                    ])
                            ]),

                        Tab::make('Thông tin văn bằng Trung cấp')
                            ->icon('heroicon-o-document')
                            ->visible(fn() => Auth::user()?->role !== 'collaborator')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        TextEntry::make('intermediate_graduation_school')
                                            ->label('Trường tốt nghiệp TC')
                                            ->columnSpanFull(),
                                        TextEntry::make('intermediate_graduation_major')
                                            ->label('Ngành tốt nghiệp TC')
                                            ->columnSpanFull(),
                                        TextEntry::make('intermediate_graduation_grade')
                                            ->label('Xếp loại'),
                                        TextEntry::make('intermediate_training_type')
                                            ->label('Hệ đào tạo tốt nghiệp'),
                                        TextEntry::make('intermediate_graduation_year')
                                            ->label('Năm tốt nghiệp'),
                                        TextEntry::make('intermediate_diploma_number')
                                            ->label('Số hiệu bằng TN TC'),
                                        TextEntry::make('intermediate_diploma_book_number')
                                            ->label('Số vào sổ cấp bằng TN TC'),
                                        TextEntry::make('intermediate_diploma_issue_date')
                                            ->label('Ngày ký bằng TN TC')
                                            ->date('d/m/Y'),
                                        TextEntry::make('intermediate_diploma_signer')
                                            ->label('Người ký bằng TN TC')
                                            ->columnSpanFull(),
                                        TextEntry::make('intermediate_gpa')
                                            ->label('Điểm trung bình tích lũy toàn khóa'),
                                    ])
                            ]),

                        Tab::make('Giấy tờ')
                            ->icon('heroicon-o-paper-clip')
                            ->visible(fn() => Auth::user()?->role !== 'collaborator')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextEntry::make('document_identity_card_front')
                                            ->label('File CCCD (mặt trước)')
                                            ->formatStateUsing(fn($state) => $state ? 'Đã upload' : 'Chưa upload'),
                                        TextEntry::make('document_identity_card_back')
                                            ->label('File CCCD (mặt sau)')
                                            ->formatStateUsing(fn($state) => $state ? 'Đã upload' : 'Chưa upload'),
                                        TextEntry::make('document_college_diploma')
                                            ->label('Bằng tốt nghiệp CĐ')
                                            ->formatStateUsing(fn($state) => $state ? 'Đã upload' : 'Chưa upload'),
                                        TextEntry::make('college_diploma_copy_type')
                                            ->label('Bằng tốt nghiệp CĐ (BS/BG)'),
                                        TextEntry::make('document_college_transcript')
                                            ->label('Bảng điểm CĐ')
                                            ->formatStateUsing(fn($state) => $state ? 'Đã upload' : 'Chưa upload'),
                                        TextEntry::make('college_transcript_copy_type')
                                            ->label('Bảng điểm CĐ (BS/BG)'),
                                        TextEntry::make('document_high_school_diploma')
                                            ->label('Bằng tốt nghiệp THPT')
                                            ->formatStateUsing(fn($state) => $state ? 'Đã upload' : 'Chưa upload'),
                                        TextEntry::make('high_school_diploma_copy_type')
                                            ->label('Bằng tốt nghiệp THPT (BS/BG)'),
                                        TextEntry::make('document_intermediate_diploma')
                                            ->label('Bằng Trung cấp')
                                            ->formatStateUsing(fn($state) => $state ? 'Đã upload' : 'Chưa upload'),
                                        TextEntry::make('document_intermediate_transcript')
                                            ->label('Bảng điểm Trung cấp')
                                            ->formatStateUsing(fn($state) => $state ? 'Đã upload' : 'Chưa upload'),
                                        TextEntry::make('document_birth_certificate')
                                            ->label('Giấy khai sinh')
                                            ->formatStateUsing(fn($state) => $state ? 'Đã upload' : 'Chưa upload'),
                                        TextEntry::make('birth_certificate_copy_type')
                                            ->label('Giấy khai sinh (BS/BG)'),
                                        TextEntry::make('document_photo')
                                            ->label('Ảnh thẻ')
                                            ->formatStateUsing(fn($state) => $state ? 'Đã upload' : 'Chưa upload'),
                                        TextEntry::make('document_health_certificate')
                                            ->label('Giấy khám sức khỏe')
                                            ->formatStateUsing(fn($state) => $state ? 'Đã upload' : 'Chưa upload'),
                                        TextEntry::make('health_certificate_copy_type')
                                            ->label('Giấy khám sức khỏe (BS/BG)'),
                                    ])
                            ]),
                    ]),

                // Right section: 3 columns (only for admin)
                Tabs::make('StudentChecklist')
                    ->columnSpan(3)
                    ->visible(fn() => Auth::user()?->role !== 'collaborator')
                    ->tabs([
                        Tab::make('Checklist hồ sơ nhập học')
                            ->icon('heroicon-o-check-circle')
                            ->schema([
                                TextEntry::make('document_checklist')
                                    ->label('Giấy tờ đã nộp')
                                    ->badge()
                                    ->formatStateUsing(fn($state) => match ($state) {
                                        'phieu_tuyen_sinh' => '📄 Phiếu tuyển sinh',
                                        'phieu_xet_tuyen' => '📄 Phiếu xét tuyển',
                                        'bang_cao_dang' => '📄 Bằng CĐ công chứng',
                                        'bang_thpt' => '📄 Bằng THPT công chứng',
                                        'bang_diem' => '📄 Bảng điểm công chứng',
                                        'giay_khai_sinh' => '📄 Giấy khai sinh công chứng',
                                        'cccd' => '📄 CCCD công chứng',
                                        'giay_kham_suc_khoe' => '📷 Giấy khám sức khỏe',
                                        'anh_4x6' => '📷 4 ảnh 4x6',
                                        default => $state
                                    })
                                    ->columnSpanFull(),
                            ]),
                    ])
            ]);
    }
}
