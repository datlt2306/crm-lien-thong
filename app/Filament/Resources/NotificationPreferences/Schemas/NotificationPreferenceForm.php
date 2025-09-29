<?php

namespace App\Filament\Resources\NotificationPreferences\Schemas;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class NotificationPreferenceForm {
    public static function configure(Schema $schema): Schema {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('Người dùng')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Section::make('Cài đặt Email')
                    ->description('Cấu hình thông báo qua email')
                    ->schema([
                        Toggle::make('email_payment_verified')
                            ->label('Thanh toán được xác minh')
                            ->default(true),
                        Toggle::make('email_payment_rejected')
                            ->label('Thanh toán bị từ chối')
                            ->default(true),
                        Toggle::make('email_commission_earned')
                            ->label('Nhận hoa hồng')
                            ->default(true),
                        Toggle::make('email_quota_warning')
                            ->label('Cảnh báo chỉ tiêu')
                            ->default(true),
                        Toggle::make('email_student_status_change')
                            ->label('Thay đổi trạng thái học viên')
                            ->default(true),
                        Toggle::make('email_system_updates')
                            ->label('Cập nhật hệ thống')
                            ->default(true),
                    ])
                    ->columns(2),

                Section::make('Cài đặt Push Notifications')
                    ->description('Cấu hình thông báo push')
                    ->schema([
                        Toggle::make('push_payment_verified')
                            ->label('Thanh toán được xác minh')
                            ->default(true),
                        Toggle::make('push_payment_rejected')
                            ->label('Thanh toán bị từ chối')
                            ->default(true),
                        Toggle::make('push_commission_earned')
                            ->label('Nhận hoa hồng')
                            ->default(true),
                        Toggle::make('push_quota_warning')
                            ->label('Cảnh báo chỉ tiêu')
                            ->default(true),
                        Toggle::make('push_student_status_change')
                            ->label('Thay đổi trạng thái học viên')
                            ->default(false),
                        Toggle::make('push_system_updates')
                            ->label('Cập nhật hệ thống')
                            ->default(false),
                    ])
                    ->columns(2),

                Section::make('Cài đặt In-App Notifications')
                    ->description('Cấu hình thông báo trong ứng dụng')
                    ->schema([
                        Toggle::make('in_app_payment_verified')
                            ->label('Thanh toán được xác minh')
                            ->default(true),
                        Toggle::make('in_app_payment_rejected')
                            ->label('Thanh toán bị từ chối')
                            ->default(true),
                        Toggle::make('in_app_commission_earned')
                            ->label('Nhận hoa hồng')
                            ->default(true),
                        Toggle::make('in_app_quota_warning')
                            ->label('Cảnh báo chỉ tiêu')
                            ->default(true),
                        Toggle::make('in_app_student_status_change')
                            ->label('Thay đổi trạng thái học viên')
                            ->default(true),
                        Toggle::make('in_app_system_updates')
                            ->label('Cập nhật hệ thống')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}
