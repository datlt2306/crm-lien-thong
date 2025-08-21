<?php

namespace App\Filament\Resources\Students\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
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
                    ->required(),
                TextInput::make('email')
                    ->label('Email')
                    ->email(),
                \Filament\Forms\Components\Select::make('organization_id')
                    ->label('Tổ chức')
                    ->relationship('organization', 'name')
                    ->required()
                    ->helperText('Chọn tổ chức cho sinh viên'),
                \Filament\Forms\Components\Select::make('collaborator_id')
                    ->label('CTV giới thiệu')
                    ->relationship('collaborator', 'full_name')
                    // ->searchable()
                    ->helperText('Chọn người giới thiệu cho sinh viên này'),
                TextInput::make('current_college')
                    ->label('Trường đang học'),
                TextInput::make('target_university')
                    ->label('Trường muốn học'),
                TextInput::make('major')
                    ->label('Ngành học'),
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
                \Filament\Forms\Components\Select::make('status')
                    ->label('Tình trạng')
                    ->options([
                        'new' => 'Mới',
                        'contacted' => 'Đã liên hệ',
                        'submitted' => 'Đã nộp hồ sơ',
                        'approved' => 'Đã duyệt',
                        'enrolled' => 'Đã nhập học',
                        'rejected' => 'Từ chối',
                        'pending' => 'Chờ xử lý',
                        'interviewed' => 'Đã phỏng vấn',
                        'deposit_paid' => 'Đã đặt cọc',
                        'offer_sent' => 'Đã gửi thư mời',
                        'offer_accepted' => 'Đã nhận thư mời',
                    ])
                    ->required()
                    ->default('new'),
                Textarea::make('notes')
                    ->label('Ghi chú')
                    ->columnSpanFull(),
            ]);
    }
}
