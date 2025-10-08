<?php

namespace App\Filament\Resources\CollaboratorRegistrations\Schemas;

use App\Models\Collaborator;
use App\Models\Organization;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CollaboratorRegistrationForm {
    public static function configure(Schema $schema): Schema {
        return $schema
            ->components([
                Section::make('Thông tin cơ bản')
                    ->schema([
                        TextInput::make('full_name')
                            ->label('Họ và tên')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('phone')
                            ->label('Số điện thoại')
                            ->required()
                            ->tel()
                            ->unique(ignoreRecord: true)
                            ->maxLength(20),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Section::make('Thông tin tổ chức')
                    ->schema([
                        Select::make('organization_id')
                            ->label('Tổ chức')
                            ->options(Organization::where('status', 'active')->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->visible(fn() => \Illuminate\Support\Facades\Auth::user()->role === 'super_admin'),

                        Select::make('upline_id')
                            ->label('Cộng tác viên giới thiệu')
                            ->options(function ($get) {
                                $organizationId = $get('organization_id');
                                if (!$organizationId) {
                                    return [];
                                }
                                return Collaborator::where('organization_id', $organizationId)
                                    ->where('status', 'active')
                                    ->pluck('full_name', 'id')
                                    ->map(function ($name, $id) {
                                        $collaborator = Collaborator::find($id);
                                        return $name . ' (' . $collaborator->ref_id . ')';
                                    });
                            })
                            ->searchable()
                            ->preload()
                            ->placeholder('Chọn CTV giới thiệu (tùy chọn)'),
                    ])
                    ->columns(2),

                Section::make('Thông tin bổ sung')
                    ->schema([
                        Textarea::make('note')
                            ->label('Ghi chú')
                            ->rows(3)
                            ->columnSpanFull(),

                        Select::make('status')
                            ->label('Trạng thái')
                            ->options([
                                'pending' => 'Chờ duyệt',
                                'approved' => 'Đã duyệt',
                                'rejected' => 'Từ chối',
                            ])
                            ->required()
                            ->default('pending')
                            ->disabled(fn($record) => $record?->status !== 'pending'),

                        Textarea::make('rejection_reason')
                            ->label('Lý do từ chối')
                            ->rows(2)
                            ->visible(fn($get) => $get('status') === 'rejected')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
