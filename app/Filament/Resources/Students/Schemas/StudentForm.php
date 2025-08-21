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
                \Filament\Forms\Components\Select::make('current_college')
                    ->label('Trường đang học')
                    ->options(function () {
                        return \App\Models\Organization::orderBy('name')->pluck('name', 'name')->toArray();
                    })
                    ->searchable(),
                \Filament\Forms\Components\TextInput::make('target_university')
                    ->label('Trường muốn học')
                    ->disabled()
                    ->dehydrateStateUsing(function ($state, callable $get) {
                        // Tự động set theo tên tổ chức
                        $organizationId = $get('organization_id');
                        if (!$organizationId) return $state;
                        $org = \App\Models\Organization::find($organizationId);
                        return $org?->name;
                    })
                    ->helperText('Tự động theo tên đơn vị'),
                \Filament\Forms\Components\Select::make('program_type')
                    ->label('Hệ liên thông')
                    ->options(function () {
                        return \App\Models\Program::where('is_active', true)->orderBy('name')->pluck('name', 'code')->toArray();
                    })
                    ->searchable()
                    ->helperText('Chọn hệ dự kiến cho sinh viên'),
                \Filament\Forms\Components\Select::make('major')
                    ->label('Ngành học')
                    ->options(function (callable $get) {
                        $orgId = $get('organization_id');
                        if (!$orgId) {
                            return \App\Models\Major::where('is_active', true)->orderBy('name')->pluck('name', 'name')->toArray();
                        }
                        $org = \App\Models\Organization::with('majors')->find($orgId);
                        return $org?->majors()->where('is_active', true)->orderBy('name')->pluck('name', 'name')->toArray() ?? [];
                    })
                    ->searchable(),
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
