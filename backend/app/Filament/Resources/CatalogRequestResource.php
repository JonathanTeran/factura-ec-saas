<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CatalogRequestResource\Pages;
use App\Models\Arbitros\CatalogRequest;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Solicitudes de campeonatos/clubes faltantes enviadas por los árbitros.
 * Aprobar crea la entrada en el catálogo automáticamente.
 */
class CatalogRequestResource extends Resource
{
    protected static ?string $model = CatalogRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static ?string $navigationGroup = 'Árbitros';

    protected static ?string $navigationLabel = 'Solicitudes de catálogo';

    protected static ?string $modelLabel = 'Solicitud de catálogo';

    protected static ?string $pluralModelLabel = 'Solicitudes de catálogo';

    protected static ?int $navigationSort = 50;

    public const TYPES = [
        'championship' => 'Campeonato',
        'club' => 'Club',
    ];

    public const STATUSES = [
        'pending' => 'Pendiente',
        'approved' => 'Aprobada',
        'rejected' => 'Rechazada',
    ];

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScope('tenant')
            ->with('tenant');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = CatalogRequest::withoutTenantScope()
            ->where('status', CatalogRequest::STATUS_PENDING)
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Section::make('Solicitud')
                ->schema([
                    Forms\Components\Select::make('type')
                        ->label('Tipo')
                        ->options(self::TYPES)
                        ->disabled(),
                    Forms\Components\TextInput::make('name')
                        ->label('Nombre solicitado')
                        ->helperText('Puedes corregirlo antes de aprobar: se creará con este nombre exacto.')
                        ->required()
                        ->maxLength(150),
                    Forms\Components\Textarea::make('comment')
                        ->label('Comentario del árbitro')
                        ->disabled()
                        ->rows(2),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Solicitante')
                    ->searchable()
                    ->limit(25),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => self::TYPES[$state] ?? $state),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre solicitado')
                    ->searchable()
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('comment')
                    ->label('Comentario')
                    ->limit(30)
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => self::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Solicitada')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options(self::STATUSES),
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options(self::TYPES),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Aprobar y crear')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (CatalogRequest $record) => $record->status === CatalogRequest::STATUS_PENDING)
                    ->requiresConfirmation()
                    ->modalDescription(fn (CatalogRequest $record) => sprintf(
                        'Se creará el %s "%s" en el catálogo y quedará disponible para todos los árbitros.',
                        strtolower(self::TYPES[$record->type] ?? $record->type),
                        $record->name
                    ))
                    ->action(function (CatalogRequest $record) {
                        $record->approve();
                        Notification::make()
                            ->title('Solicitud aprobada')
                            ->body("\"{$record->name}\" ya está en el catálogo.")
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Rechazar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (CatalogRequest $record) => $record->status === CatalogRequest::STATUS_PENDING)
                    ->form([
                        Forms\Components\Textarea::make('note')
                            ->label('Motivo (visible para el árbitro)')
                            ->rows(2)
                            ->maxLength(255),
                    ])
                    ->action(function (CatalogRequest $record, array $data) {
                        $record->reject($data['note'] ?? null);
                        Notification::make()
                            ->title('Solicitud rechazada')
                            ->warning()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCatalogRequests::route('/'),
            'edit' => Pages\EditCatalogRequest::route('/{record}/edit'),
        ];
    }
}
