<?php

namespace App\Filament\Resources\WalletResource\Pages;

use App\Filament\Resources\WalletResource;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Auth;

class ViewMyWallet extends Page
{
    protected static string $resource = WalletResource::class;

    public ?array $data = [];

    public function mount(): void
    {
        $user = Auth::user();
        $collaborator = \App\Models\Collaborator::where('email', $user->email)->first();
        
        if ($collaborator && $collaborator->wallet) {
            $this->data = [
                'balance' => $collaborator->wallet->balance,
                'total_received' => $collaborator->wallet->total_received,
                'total_paid' => $collaborator->wallet->total_paid,
                'collaborator_name' => $collaborator->full_name,
                'organization_name' => $collaborator->organization->name ?? 'N/A',
            ];
        }
    }

    protected function getViewData(): array
    {
        return [
            'data' => $this->data,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('view_transactions')
                ->label('Xem giao dịch')
                ->icon('heroicon-o-arrow-right')
                ->url(route('filament.admin.resources.wallet-transactions.index'))
                ->color('primary'),
        ];
    }
}
