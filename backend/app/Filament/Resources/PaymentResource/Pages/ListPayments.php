<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use App\Models\Billing\Payment;
use App\Enums\PaymentStatus;
use App\Enums\PaymentMethod;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Todos')
                ->badge(Payment::count()),
            'pending_approval' => Tab::make('Por Aprobar')
                ->badge(Payment::where('status', PaymentStatus::PENDING)
                    ->where('payment_method', PaymentMethod::BANK_TRANSFER)
                    ->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('status', PaymentStatus::PENDING)
                    ->where('payment_method', PaymentMethod::BANK_TRANSFER)),
            'completed' => Tab::make('Completados')
                ->badge(Payment::where('status', PaymentStatus::COMPLETED)->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', PaymentStatus::COMPLETED)),
            'pending' => Tab::make('Pendientes')
                ->badge(Payment::where('status', PaymentStatus::PENDING)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', PaymentStatus::PENDING)),
            'failed' => Tab::make('Fallidos')
                ->badge(Payment::where('status', PaymentStatus::FAILED)->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', PaymentStatus::FAILED)),
            'refunded' => Tab::make('Reembolsados')
                ->badge(Payment::whereIn('status', [PaymentStatus::REFUNDED, PaymentStatus::PARTIALLY_REFUNDED])->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', [PaymentStatus::REFUNDED, PaymentStatus::PARTIALLY_REFUNDED])),
        ];
    }
}
