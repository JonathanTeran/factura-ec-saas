<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OfficiatedMatchResource\Pages;
use App\Models\Arbitros\OfficiatedMatch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OfficiatedMatchResource extends Resource
{
    protected static ?string $model = OfficiatedMatch::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Árbitros';

    protected static ?string $navigationLabel = 'Partidos de árbitros';

    protected static ?string $modelLabel = 'Partido de árbitro';

    protected static ?string $pluralModelLabel = 'Partidos de árbitros';

    protected static ?int $navigationSort = 40;

    public const STATUSES = [
        'pending' => 'Pendiente',
        'queued' => 'En cola',
        'invoiced' => 'Facturado',
        'blocked_window' => 'Bloqueado (ventana)',
    ];

    public const ROLES = [
        'arbitro' => 'Árbitro',
        'asistente_1' => 'Asistente 1',
        'asistente_2' => 'Asistente 2',
        'cuarto' => 'Cuarto',
        'var' => 'VAR',
        'comisario' => 'Comisario',
        'delegado' => 'Delegado',
    ];

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScope('tenant')
            ->with(['tenant', 'championship', 'homeClub', 'awayClub']);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Partido de árbitro')
                    ->schema([
                        Forms\Components\DatePicker::make('match_date')
                            ->label('Fecha del partido'),
                        Forms\Components\Select::make('role')
                            ->label('Rol')
                            ->options(self::ROLES),
                        Forms\Components\TextInput::make('fee')
                            ->label('Tarifa')
                            ->prefix('$')
                            ->numeric(),
                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options(self::STATUSES),
                        Forms\Components\TextInput::make('source')
                            ->label('Origen'),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notas')
                            ->rows(2),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Árbitro/Cuenta')
                    ->searchable(),
                Tables\Columns\TextColumn::make('match_date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('championship.name')
                    ->label('Campeonato')
                    ->limit(25),
                Tables\Columns\TextColumn::make('home_club_id')
                    ->label('Partido')
                    ->formatStateUsing(fn ($record) => ($record->homeClub?->name ?? '—') . ' vs ' . ($record->awayClub?->name ?? '—'))
                    ->searchable(false),
                Tables\Columns\TextColumn::make('role')
                    ->label('Rol')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => self::ROLES[$state] ?? $state),
                Tables\Columns\TextColumn::make('fee')
                    ->label('Tarifa')
                    ->money('USD'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => self::STATUSES[$state] ?? $state)
                    ->color(fn (?string $state): string => match ($state) {
                        'pending' => 'warning',
                        'queued' => 'info',
                        'invoiced' => 'success',
                        'blocked_window' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('source')
                    ->label('Origen'),
                Tables\Columns\TextColumn::make('invoiced_at')
                    ->label('Facturado el')
                    ->dateTime('d/m/Y H:i'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options(self::STATUSES),
                Tables\Filters\SelectFilter::make('role')
                    ->label('Rol')
                    ->options(self::ROLES),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('match_date', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOfficiatedMatches::route('/'),
        ];
    }
}
