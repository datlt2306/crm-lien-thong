<?php

namespace App\Filament\Resources\PermissionManagement\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\CheckboxList;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;

class PermissionManagementForm {
    public static function configure(Schema $schema): Schema {
        return $schema
            ->components([
                Section::make('📋 Thông tin vai trò')
                    ->description('Thiết lập thông tin cơ bản của vai trò')
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        TextInput::make('name')
                            ->label('Tên vai trò')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Nhập tên vai trò...')
                            ->helperText('Tên vai trò phải duy nhất trong hệ thống'),

                        Select::make('guard_name')
                            ->label('Guard')
                            ->options([
                                'web' => 'Web',
                                'api' => 'API',
                            ])
                            ->required()
                            ->default('web')
                            ->helperText('Guard xác định cách xác thực vai trò'),
                    ])
                    ->columns(1)
                    ->collapsible(),

                Section::make('🔐 Phân quyền chi tiết')
                    ->description('Chọn các quyền cho vai trò này (Đã phân theo nhóm)')
                    ->icon('heroicon-o-key')
                    ->schema([
                        self::getGroupSection('🎓 Sinh viên', 'student_'),
                        self::getGroupSection('💰 Tài chính', 'payment_'),
                        self::getGroupSection('📈 Hoa hồng', 'commission_'),
                        self::getGroupSection('📅 Tuyển sinh & Chỉ tiêu', ['intake_', 'quota_', 'annual_quota_']),
                        self::getGroupSection('👥 Nhân viên', 'user_'),
                        self::getGroupSection('🤝 Cộng tác viên', 'collaborator_'),
                        self::getGroupSection('⚙️ Hệ thống & Báo cáo', ['audit_log_', 'role_', 'setting_', 'report_', 'database_']),
                    ])
                    ->columns(1)
                    ->collapsible(),
            ])
            ->columns(1);
    }

    protected static function getGroupSection(string $label, string|array $prefixes): Section {
        $prefixes = (array) $prefixes;
        
        return Section::make($label)
            ->schema([
                CheckboxList::make('perms_' . (is_array($prefixes) ? $prefixes[0] : $prefixes))
                    ->label('')
                    ->options(function () use ($prefixes) {
                        $query = \Spatie\Permission\Models\Permission::query();
                        $query->where(function ($q) use ($prefixes) {
                            foreach ($prefixes as $prefix) {
                                $q->orWhere('name', 'like', $prefix . '%');
                            }
                        });
                        
                        return $query->get()->pluck('name', 'id')->map(fn($name) => __("permissions.{$name}") ?? $name);
                    })
                    ->dehydrated(true)
                    ->afterStateHydrated(function (CheckboxList $component, ?\Spatie\Permission\Models\Role $record) use ($prefixes) {
                        if (!$record) return;
                        
                        // Chỉ lấy các permission ID thuộc về group này (dựa trên prefix)
                        $groupPermissionIds = $record->permissions()
                            ->where(function ($q) use ($prefixes) {
                                foreach ($prefixes as $prefix) {
                                    $q->orWhere('name', 'like', $prefix . '%');
                                }
                            })
                            ->pluck('id')
                            ->toArray();
                            
                        $component->state($groupPermissionIds);
                    })
                    ->gridDirection('row')
                    ->columns(1) // Một cột cho mỗi nhóm để nhìn rõ hơn
                    ->bulkToggleable(),
            ])
            ->compact()
            ->collapsible();
    }
}
