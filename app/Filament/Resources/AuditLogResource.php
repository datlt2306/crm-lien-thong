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

    public static function getNavigationLabel(): string
    {
        return Auth::user()?->role === 'ctv' ? 'Nhật ký học viên' : 'Nhật ký hệ thống';
    }

    public static function getPluralLabel(): string
    {
        return Auth::user()?->role === 'ctv' ? 'Nhật ký học viên' : 'Nhật ký hệ thống';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Thời gian')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
                TextColumn::make('event_group')
                    ->label('Nhóm nhật ký')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        \App\Models\AuditLog::GROUP_FINANCIAL => 'Tài chính',
                        \App\Models\AuditLog::GROUP_SYSTEM => 'Hệ thống',
                        \App\Models\AuditLog::GROUP_ACCOUNT_DELETION => 'Xóa dữ liệu',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        \App\Models\AuditLog::GROUP_FINANCIAL => 'success',
                        \App\Models\AuditLog::GROUP_ACCOUNT_DELETION => 'danger',
                        default => 'gray',
                    })
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('Người thực hiện')
                    ->searchable(),
                TextColumn::make('user_role')
                    ->label('Vai trò')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'super_admin' => 'Quản trị cấp cao',
                        'admin' => 'Quản trị viên',
                        'accountant' => 'Kế toán',
                        'admissions' => 'Tuyển sinh',
                        'document' => 'Hồ sơ',
                        'ctv' => 'Cộng tác viên',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'admin', 'super_admin' => 'danger',
                        'accountant' => 'success',
                        'admissions' => 'info',
                        'ctv' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('event_type')
                    ->label('Hành động')
                    ->formatStateUsing(fn ($record): string => $record->getFriendlyActionName())
                    ->badge()
                    ->color(fn (string $state): string => match (strtoupper($state)) {
                        'CREATED' => 'success',
                        'UPDATED' => 'warning',
                        'DELETED' => 'danger',
                        'RESTORED' => 'info',
                        default => 'gray',
                    })
                    ->searchable(),
                TextColumn::make('auditable_type')
                    ->label('Đối tượng')
                    ->formatStateUsing(fn ($record): string => $record->getFriendlyModelName()),
                TextColumn::make('student.full_name')
                    ->label('Học viên liên quan')
                    ->placeholder('N/A')
                    ->searchable(),
                TextColumn::make('reason')
                    ->label('Lý do/Ghi chú')
                    ->limit(30)
                    ->placeholder('-'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('event_group')
                    ->label('Nhóm nhật ký')
                    ->options([
                        \App\Models\AuditLog::GROUP_FINANCIAL => 'Tài chính',
                        \App\Models\AuditLog::GROUP_SYSTEM => 'Hệ thống',
                        \App\Models\AuditLog::GROUP_ACCOUNT_DELETION => 'Xóa dữ liệu',
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
        $isAdmin = in_array(Auth::user()?->role, ['super_admin', 'admin', 'organization_owner']);

        return $schema
            ->components([
                // === PHẦN 1: TÓM TẮT HÀNH ĐỘNG (Ai cũng xem được) ===
                Section::make('Tóm tắt hành động')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        TextEntry::make('summary')
                            ->hiddenLabel()
                            ->getStateUsing(fn ($record) => $record->getHumanSummary())
                            ->markdown(false)
                            ->formatStateUsing(fn ($state) => nl2br(e($state)))
                            ->html()
                            ->columnSpanFull(),
                    ]),

                // === PHẦN 2: LÝ DO (nếu có) ===
                Section::make('Ghi chú')
                    ->icon('heroicon-o-pencil-square')
                    ->schema([
                        TextEntry::make('reason')
                            ->hiddenLabel()
                            ->placeholder('Không có ghi chú'),
                    ])
                    ->visible(fn ($record) => !empty($record->reason)),

                // === PHẦN 3: CHI TIẾT KỸ THUẬT (Chỉ Admin xem) ===
                Section::make('Chi tiết kỹ thuật (Chỉ Admin)')
                    ->icon('heroicon-o-code-bracket')
                    ->collapsed()
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('auditable_type')->label('Model'),
                                TextEntry::make('auditable_id')->label('ID'),
                                TextEntry::make('event_type')->label('Event'),
                                TextEntry::make('ip_address')->label('IP'),
                                TextEntry::make('user_agent')->label('User Agent')->columnSpan(2),
                            ]),
                        Grid::make(2)
                            ->schema([
                                KeyValueEntry::make('old_values')
                                    ->label('Giá trị cũ')
                                    ->placeholder('—')
                                    ->getStateUsing(fn ($record) => collect($record->old_values ?? [])->map(fn ($v) => is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : $v)->toArray()),
                                KeyValueEntry::make('new_values')
                                    ->label('Giá trị mới')
                                    ->placeholder('—')
                                    ->getStateUsing(fn ($record) => collect($record->new_values ?? [])->map(fn ($v) => is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : $v)->toArray()),
                            ]),
                    ])
                    ->visible(fn () => $isAdmin),

                // === PHẦN 4: SNAPSHOT XÓA DỮ LIỆU (Chỉ Admin) ===
                Section::make('Snapshot (Dữ liệu trước khi xóa)')
                    ->schema([
                        KeyValueEntry::make('metadata.snapshot')
                            ->label('Toàn bộ dữ liệu')
                            ->getStateUsing(fn ($record) => collect($record->metadata['snapshot'] ?? [])->map(fn ($val) => is_array($val) ? json_encode($val, JSON_UNESCAPED_UNICODE) : $val)->toArray())
                    ])
                    ->visible(fn ($record) => $isAdmin && $record->event_group === AuditLog::GROUP_ACCOUNT_DELETION),
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

        // 1. Quản trị viên: Xem được toàn bộ nhật ký (Hệ thống, Tài chính, Xóa dữ liệu)
        if (in_array($user->role, ['super_admin', 'admin', 'organization_owner'])) {
            return $query;
        }

        // 2. CTV: Chỉ xem được nhật ký liên quan đến học viên của mình
        if ($user->role === 'ctv') {
            $collab = $user->collaborator;
            if ($collab) {
                return $query->whereHas('student', function ($q) use ($collab) {
                    $q->where('collaborator_id', $collab->id);
                });
            }
            return $query->whereRaw('1=0');
        }

        // 3. Kế toán: Xem được nhật ký Tài chính và các nhật ký liên quan đến học viên
        if ($user->role === 'accountant' || ($user->roles && $user->roles->contains('name', 'accountant'))) {
            return $query->where(function (Builder $q) {
                $q->where('event_group', AuditLog::GROUP_FINANCIAL)
                  ->orWhereNotNull('student_id');
            });
        }

        // 4. Các vai trò khác: Chỉ xem được nhật ký liên quan đến học viên, ẩn hoàn toàn Nhật ký hệ thống & Xóa dữ liệu
        return $query->whereNotNull('student_id');
    }

    /**
     * Chuyển đổi các Key và Value kỹ thuật sang tiếng Việt cho Kế toán
     */
    protected static function formatAuditValues(?array $values): array
    {
        if (!$values) return [];

        return collect($values)->mapWithKeys(function ($val, $key) {
            $label = match ($key) {
                'status' => 'Trạng thái',
                'payment_confirmed_at' => 'Ngày xác nhận chi',
                'payment_confirmed_by' => 'Người xác nhận chi (ID)',
                'amount', 'fee' => 'Số tiền',
                'receipt_number' => 'Mã phiếu thu',
                'receipt_path' => 'File phiếu thu',
                'payment_bill_path' => 'Chứng từ chuyển khoản',
                'meta' => 'Thông tin bổ sung',
                'audit_reason' => 'Lý do',
                default => $key,
            };

            $formattedVal = $val;

            // Xử lý riêng cho trường Meta (chứa nhiều thông tin kỹ thuật)
            if ($key === 'meta' && is_array($val)) {
                $metaLines = [];
                foreach ($val as $mKey => $mVal) {
                    $mLabel = match ($mKey) {
                        'program_type' => 'Loại chương trình',
                        'payment_id' => 'ID Phiếu thu',
                        'fee_closing_at' => 'Thời gian chốt phí',
                        'fee_closing_title' => 'Tên đợt chốt phí',
                        'collector_user_id' => 'ID Người thu',
                        'rollback_history' => 'Lịch sử hoàn tác',
                        'audit_reason' => 'Lý do hệ thống',
                        default => $mKey,
                    };

                    if ($mKey === 'rollback_history' && is_array($mVal)) {
                        foreach ($mVal as $history) {
                            $reason = $history['reason'] ?? 'N/A';
                            $at = isset($history['at']) ? date('d/m/Y H:i', strtotime($history['at'])) : 'N/A';
                            $by = $history['by'] ?? 'N/A';
                            $metaLines[] = "• Hoàn tác: $reason (Lúc $at bởi $by)";
                        }
                    } else {
                        $displayVal = match($mVal) {
                            'REGULAR' => 'Chính quy',
                            'NON_REGULAR' => 'Vừa học vừa làm',
                            default => is_array($mVal) ? json_encode($mVal, JSON_UNESCAPED_UNICODE) : $mVal,
                        };
                        $metaLines[] = "• $mLabel: $displayVal";
                    }
                }
                $formattedVal = implode("\n", $metaLines);
            }

            if ($key === 'status') {
                $formattedVal = match ($val) {
                    'paid' => 'Đã thanh toán',
                    'payment_confirmed' => 'Đã chốt & Đã chi',
                    'payable' => 'Có thể thanh toán',
                    'pending' => 'Chờ xử lý',
                    'verified' => 'Đã xác nhận',
                    default => $val,
                };
            }

            if (is_array($val)) {
                $formattedVal = json_encode($val, JSON_UNESCAPED_UNICODE);
            }

            return [$label => $formattedVal];
        })->toArray();
    }
}
