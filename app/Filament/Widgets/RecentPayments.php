<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use Filament\Actions\Action;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;

class RecentPayments extends BaseWidget {
    protected static ?string $heading = 'Payments gần đây';
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table {
        return $table
            ->query($this->recentPaymentsQuery())
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('student.full_name')->label('Sinh viên')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('amount')->label('Số tiền')->money('VND', true),
                Tables\Columns\BadgeColumn::make('status')->label('Trạng thái')->colors([
                    'warning' => Payment::STATUS_SUBMITTED,
                    'success' => Payment::STATUS_VERIFIED,
                    'gray' => Payment::STATUS_NOT_PAID,
                ])->formatStateUsing(fn(string $state) => Payment::getStatusOptions()[$state] ?? $state),
                Tables\Columns\TextColumn::make('created_at')->label('Tạo lúc')->since(),
            ])
            ->actions([
                Action::make('view')
                    ->label('Xem')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Chi tiết thanh toán')
                    ->modalWidth('2xl')
                    ->modalContent(function (Payment $record) {
                        $student = $record->student?->full_name ?? '—';
                        $amount = number_format((float) $record->amount, 0, '.', ',') . ' ₫';
                        $status = Payment::getStatusOptions()[$record->status] ?? $record->status;
                        $created = optional($record->created_at)->format('d/m/Y H:i');
                        return <<<HTML
<div class="space-y-2">
  <div><span class="font-medium">ID:</span> {$record->id}</div>
  <div><span class="font-medium">Sinh viên:</span> {$student}</div>
  <div><span class="font-medium">Số tiền:</span> {$amount}</div>
  <div><span class="font-medium">Trạng thái:</span> {$status}</div>
  <div><span class="font-medium">Tạo lúc:</span> {$created}</div>
</div>
HTML;
                    }),
                Action::make('verify')->label('Verify')->icon('heroicon-o-check')
                    ->visible(fn(Payment $r) => $r->status === Payment::STATUS_SUBMITTED && Gate::allows('verify_payment'))
                    ->requiresConfirmation()
                    ->action(function (Payment $record) {
                        $record->markAsVerified(Auth::id());
                    }),
                Action::make('reject')->label('Reject')->icon('heroicon-o-x-mark')
                    ->visible(fn(Payment $r) => $r->status === Payment::STATUS_SUBMITTED && Gate::allows('verify_payment'))
                    ->requiresConfirmation()
                    ->action(function (Payment $record) {
                        $record->update(['status' => Payment::STATUS_NOT_PAID]);
                    }),
            ])
            ->defaultPaginationPageOption(10);
    }

    protected function recentPaymentsQuery() {
        return Payment::query()
            ->with(['student'])
            ->latest('created_at')
            ->limit(10);
    }
}
