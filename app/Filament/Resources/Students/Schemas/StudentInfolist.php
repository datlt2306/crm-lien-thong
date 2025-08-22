<?php

namespace App\Filament\Resources\Students\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class StudentInfolist {
    public static function configure(Schema $schema): Schema {
        return $schema
            ->components([
                TextEntry::make('full_name')
                    ->label('Họ và tên'),
                TextEntry::make('phone')
                    ->label('Số điện thoại'),
                TextEntry::make('email')
                    ->label('Địa chỉ email'),
                TextEntry::make('organization.name')
                    ->label('Tổ chức'),
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
                TextEntry::make('dob')
                    ->label('Ngày sinh')
                    ->date('d/m/Y'),
                TextEntry::make('intake_month')
                    ->label('Đợt tuyển')
                    ->formatStateUsing(fn($state) => $state ? "Tháng {$state}" : 'Chưa chọn'),
                TextEntry::make('address')
                    ->label('Địa chỉ')
                    ->columnSpanFull(),
                TextEntry::make('source'),
                TextEntry::make('status'),
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
