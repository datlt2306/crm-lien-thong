<?php

namespace App\Filament\Resources\AuditLogResource\Pages;

use App\Filament\Resources\AuditLogResource;
use App\Models\AuditLog;
use Filament\Resources\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\WithPagination;

class AuditTimeline extends Page implements HasForms
{
    use InteractsWithForms;
    use WithPagination;

    protected static string $resource = AuditLogResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return static::getResource()::canViewAny();
    }

    protected string $view = 'filament.resources.audit-log-resource.pages.audit-timeline';

    protected static ?string $title = 'Dòng thời gian Nhật ký';

    protected static ?string $navigationLabel = 'Dòng thời gian';

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('view_table')
                ->label('Xem Dạng bảng')
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->url(fn (): string => ListAuditLogs::getUrl()),
        ];
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                DatePicker::make('date_from')->label('Từ ngày'),
                DatePicker::make('date_to')->label('Đến ngày'),
                Select::make('event_group')
                    ->label('Nhóm')
                    ->options([
                        'SYSTEM' => 'Hệ thống',
                        'FINANCIAL' => 'Tài chính',
                        'SECURITY' => 'Bảo mật',
                    ]),
                TextInput::make('search')
                    ->label('Tìm kiếm nội dung')
                    ->placeholder('Lý do, thay đổi...'),
            ])
            ->columns(4)
            ->statePath('data');
    }

    public function getLogs(): LengthAwarePaginator
    {
        return static::getResource()::getEloquentQuery()
            ->with(['user', 'student'])
            ->when($this->data['date_from'] ?? null, fn($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($this->data['date_to'] ?? null, fn($q, $date) => $q->whereDate('created_at', '<=', $date))
            ->when($this->data['event_group'] ?? null, fn($q, $group) => $q->where('event_group', $group))
            ->when($this->data['search'] ?? null, fn($q, $search) => $q->where(function($query) use ($search) {
                $query->where('reason', 'like', "%{$search}%")
                      ->orWhere('event_type', 'like', "%{$search}%")
                      ->orWhere('new_values', 'like', "%{$search}%")
                      ->orWhere('old_values', 'like', "%{$search}%");
            }))
            ->latest()
            ->paginate(15);
    }

    public function applyFilters(): void
    {
        $this->resetPage();
    }
}
