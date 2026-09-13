<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FefRefereeResource\Pages;
use App\Models\Arbitros\FefReferee;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Directorio de árbitros detectados en los partidos FEF y su vínculo con las
 * cuentas de Facturón. Solo lectura: lo mantiene la sincronización.
 */
class FefRefereeResource extends Resource
{
    protected static ?string $model = FefReferee::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationGroup = 'Árbitros';

    protected static ?string $navigationLabel = 'Árbitros FEF';

    protected static ?string $modelLabel = 'Árbitro FEF';

    protected static ?string $pluralModelLabel = 'Árbitros FEF';

    protected static ?string $slug = 'fef-referees';

    protected static ?int $navigationSort = 45;

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

    public static function getNavigationBadge(): ?string
    {
        $total = FefReferee::count();

        return $total > 0 ? (string) $total : null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('last_seen_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre (según la FEF)')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('matches_count')
                    ->label('Partidos')
                    ->sortable()
                    ->alignRight(),
                Tables\Columns\TextColumn::make('roles')
                    ->label('Roles')
                    ->formatStateUsing(fn (FefReferee $r) => $r->rolesSummary())
                    ->wrap(),
                Tables\Columns\TextColumn::make('first_seen_at')
                    ->label('Primer partido')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('last_seen_at')
                    ->label('Último partido')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Cuenta en Facturón')
                    ->badge()
                    ->color('success')
                    ->placeholder('Sin cuenta')
                    ->url(fn (FefReferee $r) => $r->tenant_id ? url("/admin/tenants/{$r->tenant_id}") : null),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('tenant_id')
                    ->label('Cuenta en Facturón')
                    ->placeholder('Todos')
                    ->trueLabel('Con cuenta')
                    ->falseLabel('Sin cuenta')
                    ->queries(
                        true: fn (Builder $q) => $q->whereNotNull('tenant_id'),
                        false: fn (Builder $q) => $q->whereNull('tenant_id'),
                    ),
                Tables\Filters\Filter::make('recent')
                    ->label('Activos en los últimos 60 días')
                    ->query(fn (Builder $q) => $q->where('last_seen_at', '>=', now()->subDays(60))),
            ])
            ->actions([])
            ->bulkActions([])
            ->emptyStateHeading('Todavía no hay árbitros detectados')
            ->emptyStateDescription('Se llena con la sincronización FEF (Árbitros → Sincronización FEF → Sincronizar ahora).');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFefReferees::route('/'),
        ];
    }
}
