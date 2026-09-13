<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FefSyncRunResource\Pages;
use App\Models\Arbitros\FefSyncRun;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Historial de sincronizaciones con la API FEF: qué corrió, cuándo, qué trajo
 * y si falló. Solo lectura; desde aquí el super admin lanza una corrida manual.
 */
class FefSyncRunResource extends Resource
{
    protected static ?string $model = FefSyncRun::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationGroup = 'Árbitros';

    protected static ?string $navigationLabel = 'Sincronización FEF';

    protected static ?string $modelLabel = 'Sincronización';

    protected static ?string $pluralModelLabel = 'Sincronizaciones FEF';

    protected static ?string $slug = 'fef-sync-runs';

    protected static ?int $navigationSort = 50;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    /** Badge rojo con las corridas fallidas de las últimas 24 h. */
    public static function getNavigationBadge(): ?string
    {
        $failed = FefSyncRun::where('status', FefSyncRun::STATUS_FAILED)
            ->where('started_at', '>=', now()->subDay())
            ->count();

        return $failed > 0 ? (string) $failed : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function statusColor(string $status): string
    {
        return match ($status) {
            FefSyncRun::STATUS_SUCCESS => 'success',
            FefSyncRun::STATUS_PARTIAL => 'warning',
            FefSyncRun::STATUS_FAILED => 'danger',
            default => 'info',
        };
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('30s')
            ->defaultSort('started_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('started_at')
                    ->label('Inicio')
                    ->dateTime('d/m/Y H:i:s')
                    ->description(fn (FefSyncRun $r) => $r->started_at?->diffForHumans())
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => FefSyncRun::STATUS_LABELS[$state] ?? $state)
                    ->color(fn (string $state) => self::statusColor($state)),
                Tables\Columns\TextColumn::make('trigger')
                    ->label('Origen')
                    ->formatStateUsing(fn (string $state) => FefSyncRun::TRIGGER_LABELS[$state] ?? $state)
                    ->description(fn (FefSyncRun $r) => $r->triggeredBy?->name),
                Tables\Columns\TextColumn::make('duration_ms')
                    ->label('Duración')
                    ->formatStateUsing(fn ($state) => $state === null ? '—' : round($state / 1000, 1).' s')
                    ->alignRight(),
                Tables\Columns\TextColumn::make('stats.championships')
                    ->label('Campeonatos')
                    ->default(0)
                    ->alignRight(),
                Tables\Columns\TextColumn::make('stats.matches_created')
                    ->label('Partidos nuevos')
                    ->default(0)
                    ->alignRight()
                    ->color(fn ($state) => (int) $state > 0 ? 'success' : null),
                Tables\Columns\TextColumn::make('stats.matches_updated')
                    ->label('Actualizados')
                    ->default(0)
                    ->alignRight()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('stats.clubs')
                    ->label('Clubes nuevos')
                    ->default(0)
                    ->alignRight()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('stats.proposals')
                    ->label('Propuestas')
                    ->default(0)
                    ->alignRight()
                    ->description('a árbitros'),
                Tables\Columns\TextColumn::make('stats.referees')
                    ->label('Árbitros')
                    ->default(0)
                    ->alignRight()
                    ->description(fn (FefSyncRun $r) => $r->stat('referees_linked').' con cuenta')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('api_errors')
                    ->label('Errores API')
                    ->formatStateUsing(fn (FefSyncRun $r) => (string) $r->apiErrorCount())
                    ->color(fn (FefSyncRun $r) => $r->apiErrorCount() > 0 ? 'warning' : null)
                    ->alignRight(),
                Tables\Columns\TextColumn::make('error_message')
                    ->label('Error')
                    ->limit(60)
                    ->tooltip(fn (FefSyncRun $r) => $r->error_message)
                    ->color('danger')
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options(FefSyncRun::STATUS_LABELS),
                Tables\Filters\SelectFilter::make('trigger')
                    ->label('Origen')
                    ->options(FefSyncRun::TRIGGER_LABELS),
                Tables\Filters\Filter::make('with_errors')
                    ->label('Con errores de API')
                    ->query(fn (Builder $q) => $q->whereNotNull('api_errors')->where('api_errors', '!=', '[]')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Detalle'),
            ])
            ->bulkActions([])
            ->emptyStateHeading('Aún no hay sincronizaciones')
            ->emptyStateDescription('Se ejecuta cada hora automáticamente, o pulsa "Sincronizar ahora".');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Corrida')
                ->columns(3)
                ->schema([
                    Infolists\Components\TextEntry::make('status')
                        ->label('Estado')
                        ->badge()
                        ->formatStateUsing(fn (string $state) => FefSyncRun::STATUS_LABELS[$state] ?? $state)
                        ->color(fn (string $state) => self::statusColor($state)),
                    Infolists\Components\TextEntry::make('trigger')
                        ->label('Origen')
                        ->formatStateUsing(fn (string $state) => FefSyncRun::TRIGGER_LABELS[$state] ?? $state),
                    Infolists\Components\TextEntry::make('triggeredBy.name')->label('Lanzada por')->placeholder('—'),
                    Infolists\Components\TextEntry::make('started_at')->label('Inicio')->dateTime('d/m/Y H:i:s'),
                    Infolists\Components\TextEntry::make('finished_at')->label('Fin')->dateTime('d/m/Y H:i:s')->placeholder('—'),
                    Infolists\Components\TextEntry::make('duration_ms')
                        ->label('Duración')
                        ->formatStateUsing(fn ($state) => $state === null ? '—' : round($state / 1000, 1).' s'),
                ]),
            Infolists\Components\Section::make('Resultados')
                ->columns(4)
                ->schema([
                    Infolists\Components\TextEntry::make('stats.championships')->label('Campeonatos')->default(0),
                    Infolists\Components\TextEntry::make('stats.skipped_inactive')->label('Campeonatos inactivos (omitidos)')->default(0),
                    Infolists\Components\TextEntry::make('stats.clubs')->label('Clubes nuevos')->default(0),
                    Infolists\Components\TextEntry::make('stats.matches_created')->label('Partidos nuevos')->default(0),
                    Infolists\Components\TextEntry::make('stats.matches_updated')->label('Partidos actualizados')->default(0),
                    Infolists\Components\TextEntry::make('stats.tenants')->label('Árbitros con nombre configurado')->default(0),
                    Infolists\Components\TextEntry::make('stats.proposals')->label('Propuestas creadas')->default(0),
                    Infolists\Components\TextEntry::make('stats.referees')
                        ->label('Árbitros en el directorio')
                        ->default(0)
                        ->helperText(fn (FefSyncRun $r) => $r->stat('referees_linked').' vinculados a una cuenta'),
                ]),
            Infolists\Components\Section::make('Errores')
                ->schema([
                    Infolists\Components\TextEntry::make('error_message')
                        ->label('Excepción')
                        ->placeholder('Ninguna')
                        ->color('danger')
                        ->columnSpanFull(),
                    Infolists\Components\RepeatableEntry::make('api_errors')
                        ->label('Endpoints de la FEF que no respondieron')
                        ->schema([
                            Infolists\Components\TextEntry::make('path')->label('Endpoint'),
                            Infolists\Components\TextEntry::make('reason')->label('Motivo'),
                        ])
                        ->columns(2)
                        ->placeholder('Ninguno')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFefSyncRuns::route('/'),
        ];
    }
}
