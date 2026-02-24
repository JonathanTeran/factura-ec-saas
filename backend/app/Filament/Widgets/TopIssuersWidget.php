<?php

namespace App\Filament\Widgets;

use App\Enums\DocumentStatus;
use App\Models\Tenant\Tenant;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

class TopIssuersWidget extends BaseWidget
{
    protected static ?string $heading = 'Top Emisores de Documentos (30 dias)';

    protected static ?int $sort = 13;

    protected int | string | array $columnSpan = 2;

    public function table(Table $table): Table
    {
        $authorizedStatus = DocumentStatus::AUTHORIZED->value;

        return $table
            ->query(
                Tenant::query()
                    ->select([
                        'tenants.id',
                        'tenants.name',
                        DB::raw('COUNT(electronic_documents.id) as documents_count'),
                        DB::raw("SUM(CASE WHEN electronic_documents.status = '{$authorizedStatus}' THEN 1 ELSE 0 END) as authorized_count"),
                        DB::raw('COALESCE(SUM(electronic_documents.total), 0) as billed_total'),
                    ])
                    ->leftJoin('electronic_documents', function (JoinClause $join) {
                        $join->on('electronic_documents.tenant_id', '=', 'tenants.id')
                            ->whereNull('electronic_documents.deleted_at')
                            ->where('electronic_documents.created_at', '>=', now()->subDays(30));
                    })
                    ->whereNull('tenants.deleted_at')
                    ->groupBy('tenants.id', 'tenants.name')
                    ->orderByDesc('documents_count')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Tenant')
                    ->searchable()
                    ->limit(24),
                Tables\Columns\TextColumn::make('documents_count')
                    ->label('Docs')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('authorized_count')
                    ->label('Autorizados')
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('auth_rate')
                    ->label('% Aprobacion')
                    ->getStateUsing(function (Tenant $record): string {
                        if ((int) $record->documents_count === 0) {
                            return '0%';
                        }

                        return round(((int) $record->authorized_count / (int) $record->documents_count) * 100, 1) . '%';
                    }),
                Tables\Columns\TextColumn::make('billed_total')
                    ->label('Facturado')
                    ->money('USD'),
            ])
            ->actions([
                Tables\Actions\Action::make('ver')
                    ->url(fn (Tenant $record) => route('filament.admin.resources.tenants.view', $record))
                    ->icon('heroicon-o-eye'),
            ])
            ->paginated(false)
            ->emptyStateHeading('Sin emision reciente')
            ->emptyStateDescription('No hay documentos emitidos en los ultimos 30 dias.')
            ->emptyStateIcon('heroicon-o-document-text');
    }
}
