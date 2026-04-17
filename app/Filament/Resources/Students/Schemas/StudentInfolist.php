<?php

namespace App\Filament\Resources\Students\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class StudentInfolist {
    public static function configure(Schema $schema): Schema {
        return $schema
            ->components([
                // Thông tin cơ bản
                TextEntry::make('profile_code')
                    ->label('Mã hồ sơ'),
                TextEntry::make('full_name')
                    ->label('Họ và tên'),
                TextEntry::make('phone')
                    ->label('Số điện thoại'),
                TextEntry::make('email')
                    ->label('Địa chỉ email'),
                TextEntry::make('collaborator.full_name')
                    ->label('Người giới thiệu'),
                TextEntry::make('collaborator.email')
                    ->label('Email người giới thiệu')
                    ->visible(fn($record) => $record->collaborator !== null),
                TextEntry::make('target_university')
                    ->label('Trường muốn học'),
                TextEntry::make('major')
                    ->label('Ngành học'),
                TextEntry::make('program_type')
                    ->label('Hệ liên thông')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'REGULAR' => 'Chính quy',
                        'PART_TIME' => 'Vừa học vừa làm',
                        default => 'Chưa chọn'
                    }),
                TextEntry::make('intake.name')
                    ->label('Đợt tuyển')
                    ->formatStateUsing(fn($state, $record) => $state ?: ($record->intake_month ? "Tháng {$record->intake_month}" : 'Chưa chọn')),
                TextEntry::make('address')
                    ->label('Địa chỉ')
                    ->columnSpanFull(),
                TextEntry::make('source')
                    ->label('Nguồn'),
                TextEntry::make('status')
                    ->label('Trạng thái'),

                // Thông tin cá nhân
                TextEntry::make('dob')
                    ->label('Ngày sinh')
                    ->date('d/m/Y'),
                TextEntry::make('birth_place')
                    ->label('Nơi sinh'),
                TextEntry::make('permanent_residence')
                    ->label('Hộ khẩu thường trú')
                    ->columnSpanFull(),
                TextEntry::make('ethnicity')
                    ->label('Dân tộc'),
                TextEntry::make('gender')
                    ->label('Giới tính')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'male' => 'Nam',
                        'female' => 'Nữ',
                        'other' => 'Khác',
                        default => 'Chưa cập nhật'
                    }),
                TextEntry::make('identity_card')
                    ->label('Số CCCD'),
                TextEntry::make('identity_card_issue_date')
                    ->label('Ngày cấp CCCD')
                    ->date('d/m/Y'),
                TextEntry::make('identity_card_issue_place')
                    ->label('Nơi cấp CCCD')
                    ->columnSpanFull(),

                // Thông tin THPT
                TextEntry::make('high_school_name')
                    ->label('Tên trường THPT')
                    ->columnSpanFull(),
                TextEntry::make('high_school_code')
                    ->label('Mã trường'),
                TextEntry::make('high_school_province')
                    ->label('Tỉnh/TP'),
                TextEntry::make('high_school_province_code')
                    ->label('Mã tỉnh'),
                TextEntry::make('high_school_district')
                    ->label('Quận/Huyện'),
                TextEntry::make('high_school_district_code')
                    ->label('Mã quận/huyện'),
                TextEntry::make('high_school_graduation_year')
                    ->label('Năm tốt nghiệp THPT'),
                TextEntry::make('high_school_academic_performance')
                    ->label('Học lực cả năm'),
                TextEntry::make('high_school_conduct')
                    ->label('Hạnh kiểm'),

                // Thông tin văn bằng Cao đẳng
                TextEntry::make('college_graduation_school')
                    ->label('Trường tốt nghiệp CĐ')
                    ->columnSpanFull(),
                TextEntry::make('college_graduation_major')
                    ->label('Ngành tốt nghiệp CĐ')
                    ->columnSpanFull(),
                TextEntry::make('college_graduation_grade')
                    ->label('Xếp loại'),
                TextEntry::make('college_training_type')
                    ->label('Hệ đào tạo'),
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

                // Thông tin văn bằng Trung cấp
                TextEntry::make('intermediate_graduation_school')
                    ->label('Trường tốt nghiệp TC')
                    ->columnSpanFull(),
                TextEntry::make('intermediate_graduation_major')
                    ->label('Ngành tốt nghiệp TC')
                    ->columnSpanFull(),
                TextEntry::make('intermediate_graduation_grade')
                    ->label('Xếp loại'),
                TextEntry::make('intermediate_training_type')
                    ->label('Hệ đào tạo'),
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

                // File uploads - chỉ hiển thị tên file hoặc link
                TextEntry::make('document_college_diploma')
                    ->label('Bằng TN CĐ')
                    ->formatStateUsing(fn($state) => $state ? 'Đã upload' : 'Chưa upload'),
                TextEntry::make('document_college_transcript')
                    ->label('Bảng điểm CĐ')
                    ->formatStateUsing(fn($state) => $state ? 'Đã upload' : 'Chưa upload'),
                TextEntry::make('document_high_school_diploma')
                    ->label('Bằng TN THPT')
                    ->formatStateUsing(fn($state) => $state ? 'Đã upload' : 'Chưa upload'),
                TextEntry::make('document_birth_certificate')
                    ->label('Giấy khai sinh')
                    ->formatStateUsing(fn($state) => $state ? 'Đã upload' : 'Chưa upload'),
                TextEntry::make('document_identity_card_front')
                    ->label('CCCD (mặt trước)')
                    ->formatStateUsing(fn($state) => $state ? 'Đã upload' : 'Chưa upload'),
                TextEntry::make('document_identity_card_back')
                    ->label('CCCD (mặt sau)')
                    ->formatStateUsing(fn($state) => $state ? 'Đã upload' : 'Chưa upload'),
                TextEntry::make('document_photo')
                    ->label('Ảnh cá nhân')
                    ->formatStateUsing(fn($state) => $state ? 'Đã upload' : 'Chưa upload'),
                TextEntry::make('document_health_certificate')
                    ->label('Giấy khám sức khỏe')
                    ->formatStateUsing(fn($state) => $state ? 'Đã upload' : 'Chưa upload'),

                TextEntry::make('notes')
                    ->label('Ghi chú')
                    ->markdown()
                    ->columnSpanFull(),

                TextEntry::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime('d/m/Y H:i:s'),
                TextEntry::make('updated_at')
                    ->label('Ngày cập nhật')
                    ->dateTime('d/m/Y H:i:s'),
            ]);
    }
}
