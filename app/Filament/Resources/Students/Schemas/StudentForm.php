<?php

namespace App\Filament\Resources\Students\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class StudentForm {
    public static function configure(Schema $schema): Schema {
        return $schema
            ->components([
                TextInput::make('full_name')
                    ->required(),
                TextInput::make('phone')
                    ->tel()
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                TextInput::make('organization_id')
                    ->required()
                    ->numeric(),
                TextInput::make('collaborator_id')
                    ->numeric(),
                TextInput::make('current_college'),
                TextInput::make('target_university'),
                TextInput::make('major'),
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
                    ->columnSpanFull(),
            ]);
    }
}
