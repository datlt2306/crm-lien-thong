<?php

namespace App\Filament\Resources\WalletResource\Pages;

use App\Filament\Resources\WalletResource;
use Filament\Resources\Pages\Page;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
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

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Thông tin ví tiền')
                    ->description('Thông tin chi tiết về ví tiền của bạn')
                    ->schema([
                        TextInput::make('collaborator_name')
                            ->label('Tên cộng tác viên')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('organization_name')
                            ->label('Tổ chức')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('balance')
                            ->label('Số dư hiện tại')
                            ->prefix('₫')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('total_received')
                            ->label('Tổng đã nhận')
                            ->prefix('₫')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('total_paid')
                            ->label('Tổng đã chi')
                            ->prefix('₫')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2),
            ]);
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfolistSection::make('Thông tin ví tiền')
                    ->description('Thông tin chi tiết về ví tiền của bạn')
                    ->schema([
                        TextEntry::make('collaborator_name')
                            ->label('Tên cộng tác viên'),
                        TextEntry::make('organization_name')
                            ->label('Tổ chức'),
                        TextEntry::make('balance')
                            ->label('Số dư hiện tại')
                            ->money('VND')
                            ->color('success'),
                        TextEntry::make('total_received')
                            ->label('Tổng đã nhận')
                            ->money('VND')
                            ->color('info'),
                        TextEntry::make('total_paid')
                            ->label('Tổng đã chi')
                            ->money('VND')
                            ->color('warning'),
                    ])
                    ->columns(2),
            ]);
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
