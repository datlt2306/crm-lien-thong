<?php

namespace App\Filament\Resources;

use App\Models\AuditLog;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use App\Filament\Resources\AuditLogResource\Pages\ListAuditLogs;
use App\Filament\Resources\AuditLogResource\Pages\AuditTimeline;
use App\Filament\Resources\AuditLogResource\Pages\ViewAuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;
use BackedEnum;

class AuditLogResource extends Resource
{
    public static function canAccess(array $parameters = []): bool
    {
        return Auth::check() && in_array(Auth::user()->role, ['super_admin', 'admin', 'accountant', 'admissions', 'document', 'ctv']);
    }

    protected static ?string $model = AuditLog::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|UnitEnum|null $navigationGroup = 'Hệ thống';

    protected static ?string $navigationLabel = 'Nhật ký hệ thống';

    protected static ?string $pluralLabel = 'Nhật ký hệ thống';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Thời gian')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Người thực hiện')
                    ->searchable(),
                TextColumn::make('user_role')
                    ->label('Vai trò')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin', 'super_admin' => 'danger',
                        'accountant' => 'success',
                        'admissions' => 'info',
                        'ctv' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('event_group')
                    ->label('Nhóm sự kiện')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        AuditLog::GROUP_FINANCIAL => 'success',
                        AuditLog::GROUP_SECURITY => 'danger',
                        AuditLog::GROUP_ACCOUNT_DELETION => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('event_type')
                    ->label('Hành động')
                    ->searchable(),
                TextColumn::make('auditable_type')
                    ->label('Đối tượng')
                    ->formatStateUsing(fn (string $state): string => str_replace('App\\Models\\', '', $state)),
                TextColumn::make('student.full_name')
                    ->label('Học viên liên quan')
                    ->placeholder('N/A')
                    ->searchable(),
                TextColumn::make('audit_reason')
                    ->label('Lý do')
                    ->limit(30)
                    ->tooltip(fn ($state) => $state),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('event_group')
                    ->label('Nhóm sự kiện')
                    ->options([
                        AuditLog::GROUP_FINANCIAL => 'Biến động tiền',
                        AuditLog::GROUP_SECURITY => 'Bảo mật',
                        AuditLog::GROUP_ACCOUNT_DELETION => 'Xóa tài khoản',
                        AuditLog::GROUP_SYSTEM => 'Hệ thống',
                    ]),
                Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Từ ngày'),
                        Forms\Components\DatePicker::make('until')->label('Đến ngày'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date))
                            ->when($data['until'], fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date));
                    })
            ])
            ->actions([
                ViewAction::make()
                    ->visible(fn () => Auth::user()->role !== 'ctv'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    // No delete or update here for data integrity
                ]),
            ])
            ->poll('30s');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Thông tin chung')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('created_at')->label('Thời gian')->dateTime('d/m/Y H:i:s'),
                                TextEntry::make('user.name')->label('Người thực hiện'),
                                TextEntry::make('user_role')->label('Vai trò thực hiện'),
                                TextEntry::make('event_group')->label('Nhóm sự kiện'),
                                TextEntry::make('event_type')->label('Hành động cụ thể'),
                                                                 TextEntry::make('ip_address')
                                    ->label('Địa chỉ IP')
                                    ->visible(fn () => Auth::user()->role !== 'ctv'),

                            ]),
                        TextEntry::make('user_agent')
                            ->label('Thiết bị/Trình duyệt')
                            ->columnSpanFull()
                            ->visible(fn () => Auth::user()->role !== 'ctv'),
                    ]),

                Section::make('Đối tượng tác động')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('auditable_type')->label('Loại đối tượng'),
                                TextEntry::make('auditable_id')->label('ID đối tượng'),
                                TextEntry::make('student.full_name')->label('Học viên liên quan'),
                                TextEntry::make('audit_reason')->label('Lý do chỉnh sửa/xóa'),
                            ]),
                    ]),

                Section::make('Chi tiết thay đổi')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                KeyValueEntry::make('old_values')
                                    ->label('Giá trị cũ')
                                    ->placeholder('Không có dữ liệu cũ')
                                    ->getStateUsing(fn ($record) => collect($record->old_values)->map(fn ($val) => is_array($val) ? json_encode($val, JSON_UNESCAPED_UNICODE) : $val)->toArray()),
                                KeyValueEntry::make('new_values')
                                    ->label('Giá trị mới')
                                    ->placeholder('Không có dữ liệu mới')
                                    ->getStateUsing(fn ($record) => collect($record->new_values)->map(fn ($val) => is_array($val) ? json_encode($val, JSON_UNESCAPED_UNICODE) : $val)->toArray()),
                            ]),
                        TextEntry::make('amount_diff')
                            ->label('Chênh lệch giá trị')
                            ->money('VND')
                            ->visible(fn ($record) => $record->event_group === AuditLog::GROUP_FINANCIAL),
                    ])
                    ->visible(fn ($record) => Auth::user()->role !== 'ctv' && (!empty($record->old_values) || !empty($record->new_values))),

                Section::make('Snapshot (Dữ liệu trước khi xóa)')
                    ->schema([
                        KeyValueEntry::make('metadata.snapshot')
                            ->label('Toàn bộ dữ liệu')
                            ->getStateUsing(fn ($record) => collect($record->metadata['snapshot'] ?? [])->map(fn ($val) => is_array($val) ? json_encode($val, JSON_UNESCAPED_UNICODE) : $val)->toArray())
                    ])
                    ->visible(fn ($record) => Auth::user()->role !== 'ctv' && $record->event_group === AuditLog::GROUP_ACCOUNT_DELETION),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditLogs::route('/'),
            'timeline' => AuditTimeline::route('/timeline'),
            'view' => ViewAuditLog::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if (!$user) {
            return $query->whereNull('id');
        }

        // Super Admin, Admin & Organization Owner saw everything
        if (in_array($user->role, ['super_admin', 'admin', 'organization_owner']) || (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['super_admin', 'admin', 'organization_owner']))) {
            return $query;
        }

        if ($user->role === 'ctv') {
            $collab = $user->collaborator;
            if ($collab) {
                return $query->whereHas('student', function ($q) use ($collab) {
                    $q->where('collaborator_id', $collab->id);
                });
            }
            return $query->whereRaw('1=0');
        }

        // Others (Accountant, Admissions, etc.)
        return $query;
    }
}
