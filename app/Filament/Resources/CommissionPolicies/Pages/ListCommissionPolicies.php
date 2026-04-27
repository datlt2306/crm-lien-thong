<?php

namespace App\Filament\Resources\CommissionPolicies\Pages;

use App\Filament\Resources\CommissionPolicies\CommissionPolicyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCommissionPolicies extends ListRecords {
    protected static string $resource = CommissionPolicyResource::class;

    public function getMaxContentWidth(): string
    {
        return 'full';
    }

    protected function getHeaderActions(): array {
        return [
            CreateAction::make()
                ->label('Thêm chính sách mới'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'active' => \Filament\Schemas\Components\Tabs\Tab::make('Chính sách hoa hồng')
                ->modifyQueryUsing(fn ($query) => $query->whereNull('commission_policies.deleted_at'))
                ->badge(fn() => \App\Models\CommissionPolicy::whereNull('deleted_at')->count())
                ->badgeColor('success'),
            'trash' => \Filament\Schemas\Components\Tabs\Tab::make('Thùng rác')
                ->icon('heroicon-o-trash')
                ->modifyQueryUsing(fn ($query) => $query->onlyTrashed())
                ->badge(fn() => \App\Models\CommissionPolicy::onlyTrashed()->count())
                ->badgeColor('danger'),
        ];
    }

    public function getTitle(): string {
        return 'Cấu hình hoa hồng';
    }

    public function getBreadcrumb(): string {
        return 'Cấu hình hoa hồng';
    }
}
