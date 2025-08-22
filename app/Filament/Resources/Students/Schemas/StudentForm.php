<?php

namespace App\Filament\Resources\Students\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use App\Models\Student;

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
                \Filament\Forms\Components\Select::make('status')
                    ->label('Tình trạng')
                    ->options(Student::getStatusOptions())
                    ->required()
                    ->default(Student::STATUS_NEW)
                    ->helperText('Quản lý hành trình nhập học của sinh viên'),
                Textarea::make('notes')
                    ->label('Ghi chú')
                    ->columnSpanFull(),
            ]);
    }
}
