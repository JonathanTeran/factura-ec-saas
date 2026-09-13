<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApiKeyResource\Pages;
use App\Models\Tenant\ApiKey;
use App\Models\Tenant\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

/**
 * Integraciones por API para el super admin: llaves `fec_…` de todas las
 * cuentas. Permite dar de alta una plataforma externa (ERP, tienda en línea,
 * sistema del cliente) en nombre de un tenant para que emita al SRI por
 * /api/v1/ext, ver su uso y rotar/desactivar credenciales.
 */
class ApiKeyResource extends Resource
{
    protected static ?string $model = ApiKey::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationGroup = 'Integraciones';

    protected static ?string $navigationLabel = 'Llaves de API';

    protected static ?string $modelLabel = 'Llave de API';

    protected static ?string $pluralModelLabel = 'Llaves de API';

    protected static ?string $slug = 'api-keys';

    protected static ?int $navigationSort = 10;

    public static function getEloquentQuery(): Builder
    {
        // El super admin no tiene tenant: se listan las llaves de todas las cuentas.
        return parent::getEloquentQuery()
            ->withoutGlobalScopes()
            ->with(['tenant.currentPlan', 'creator']);
    }

    public static function getNavigationBadge(): ?string
    {
        $active = ApiKey::withoutGlobalScopes()->where('is_active', true)->count();

        return $active > 0 ? (string) $active : null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Cuenta e integración')
                    ->description('La plataforma externa usará esta llave para emitir en nombre de la cuenta elegida.')
                    ->schema([
                        Forms\Components\Select::make('tenant_id')
                            ->label('Cuenta (tenant)')
                            ->relationship('tenant', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->disabled(fn (?ApiKey $record) => $record !== null)
                            ->dehydrated(fn (?ApiKey $record) => $record === null)
                            ->afterStateUpdated(function (Set $set, $state) {
                                $tenant = $state ? Tenant::with('currentPlan')->find($state) : null;
                                $set('rate_limit_per_minute', ApiKey::rateLimitForPlanSlug($tenant?->currentPlan?->slug));
                            }),
                        Forms\Components\Placeholder::make('api_access_state')
                            ->label('Acceso API de la cuenta')
                            ->content(function (Get $get, ?ApiKey $record) {
                                $tenantId = $record?->tenant_id ?? $get('tenant_id');
                                $tenant = $tenantId ? Tenant::with('currentPlan')->find($tenantId) : null;

                                if (! $tenant) {
                                    return 'Elige una cuenta para ver su plan.';
                                }

                                $plan = $tenant->currentPlan?->name ?? 'sin plan';
                                $limit = ApiKey::rateLimitForPlanSlug($tenant->currentPlan?->slug);

                                return $tenant->has_api_access
                                    ? "Plan {$plan} · API habilitada · hasta {$limit} peticiones/min"
                                    : "Plan {$plan} · la cuenta NO tiene acceso API: habilítalo abajo o las llamadas responderán 403.";
                            }),
                        Forms\Components\Toggle::make('grant_api_access')
                            ->label('Habilitar acceso API en esta cuenta')
                            ->helperText('Activa has_api_access en el tenant aunque su plan no lo incluya (integración gestionada por Facturón).')
                            ->default(true)
                            ->visible(function (Get $get, ?ApiKey $record) {
                                $tenantId = $record?->tenant_id ?? $get('tenant_id');
                                $tenant = $tenantId ? Tenant::find($tenantId) : null;

                                return $tenant !== null && ! $tenant->has_api_access;
                            }),
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre de la integración')
                            ->placeholder('ERP Contífico · Sucursal Quito')
                            ->helperText('Plataforma o sistema que usará la llave.')
                            ->required()
                            ->maxLength(100),
                    ])->columns(2),

                Forms\Components\Section::make('Permisos y límites')
                    ->schema([
                        Forms\Components\CheckboxList::make('permissions')
                            ->label('Alcances')
                            ->options(ApiKey::SCOPES)
                            ->default(['documents:read', 'documents:write', 'customers:read', 'customers:write', 'products:read'])
                            ->columns(2)
                            ->required()
                            ->helperText('Consultar catálogos del SRI está siempre incluido.'),
                        Forms\Components\TextInput::make('rate_limit_per_minute')
                            ->label('Peticiones por minuto')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(1000)
                            ->default(ApiKey::DEFAULT_RATE_LIMIT)
                            ->helperText('El límite real es el menor entre este valor y el del plan de la cuenta.'),
                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Caduca el')
                            ->helperText('Vacío = sin caducidad.')
                            ->seconds(false),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Activa')
                            ->default(true)
                            ->visible(fn (?ApiKey $record) => $record !== null),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Cuenta')
                    ->searchable()
                    ->sortable()
                    ->description(fn (ApiKey $r) => $r->tenant?->currentPlan?->name
                        ? 'Plan '.$r->tenant->currentPlan->name.($r->tenant->has_api_access ? '' : ' · sin acceso API')
                        : null)
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Integración')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('key_prefix')
                    ->label('Llave')
                    ->formatStateUsing(fn (string $state) => $state.'…')
                    ->fontFamily('mono')
                    ->copyable()
                    ->copyMessage('Prefijo copiado'),
                Tables\Columns\TextColumn::make('permissions')
                    ->label('Alcances')
                    ->formatStateUsing(fn (ApiKey $r) => in_array('*', $r->scopes(), true) ? 'Todos' : implode(', ', $r->scopes()))
                    ->wrap()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('rate_limit_per_minute')
                    ->label('Límite/min')
                    ->formatStateUsing(fn (ApiKey $r) => $r->effectiveRateLimit())
                    ->alignRight()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('last_used_at')
                    ->label('Último uso')
                    ->since()
                    ->placeholder('Nunca')
                    ->tooltip(fn (ApiKey $r) => $r->last_used_ip ? 'IP '.$r->last_used_ip : null)
                    ->sortable(),
                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Caduca')
                    ->dateTime('d/m/Y')
                    ->placeholder('Nunca')
                    ->color(fn (ApiKey $r) => $r->isExpired() ? 'danger' : null)
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activa')
                    ->boolean()
                    ->tooltip(fn (ApiKey $r) => $r->isExpired() ? 'Caducada' : null),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tenant_id')
                    ->label('Cuenta')
                    ->relationship('tenant', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Activa'),
                Tables\Filters\Filter::make('unused')
                    ->label('Sin uso en 30 días')
                    ->query(fn (Builder $q) => $q->where(fn ($w) => $w->whereNull('last_used_at')->orWhere('last_used_at', '<', now()->subDays(30)))),
                Tables\Filters\Filter::make('expired')
                    ->label('Caducadas')
                    ->query(fn (Builder $q) => $q->whereNotNull('expires_at')->where('expires_at', '<', now())),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                self::rotateAction(Tables\Actions\Action::make('rotate')),
                Tables\Actions\Action::make('toggle')
                    ->label(fn (ApiKey $r) => $r->is_active ? 'Desactivar' : 'Activar')
                    ->icon(fn (ApiKey $r) => $r->is_active ? 'heroicon-o-pause-circle' : 'heroicon-o-play-circle')
                    ->color(fn (ApiKey $r) => $r->is_active ? 'warning' : 'success')
                    ->requiresConfirmation()
                    ->action(fn (ApiKey $r) => $r->update(['is_active' => ! $r->is_active])),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        $base = rtrim((string) config('app.url'), '/');

        return $infolist
            ->schema([
                Infolists\Components\Section::make('Integración')
                    ->schema([
                        Infolists\Components\TextEntry::make('tenant.name')->label('Cuenta'),
                        Infolists\Components\TextEntry::make('tenant.currentPlan.name')->label('Plan')->placeholder('Sin plan'),
                        Infolists\Components\TextEntry::make('tenant.has_api_access')
                            ->label('Acceso API de la cuenta')
                            ->formatStateUsing(fn ($state) => $state ? 'Habilitado' : 'NO habilitado (las llamadas responden 403)')
                            ->color(fn ($state) => $state ? 'success' : 'danger'),
                        Infolists\Components\TextEntry::make('name')->label('Integración'),
                        Infolists\Components\TextEntry::make('key_prefix')->label('Prefijo')->fontFamily('mono')->copyable(),
                        Infolists\Components\TextEntry::make('permissions')
                            ->label('Alcances')
                            ->formatStateUsing(fn (ApiKey $r) => in_array('*', $r->scopes(), true) ? 'Todos' : implode(', ', $r->scopes())),
                        Infolists\Components\TextEntry::make('rate_limit_per_minute')
                            ->label('Límite efectivo')
                            ->formatStateUsing(fn (ApiKey $r) => $r->effectiveRateLimit().' peticiones/min'),
                        Infolists\Components\TextEntry::make('last_used_at')->label('Último uso')->since()->placeholder('Nunca'),
                        Infolists\Components\TextEntry::make('last_used_ip')->label('Última IP')->placeholder('—'),
                        Infolists\Components\TextEntry::make('expires_at')->label('Caduca')->dateTime('d/m/Y H:i')->placeholder('Nunca'),
                        Infolists\Components\IconEntry::make('is_active')->label('Activa')->boolean(),
                        Infolists\Components\TextEntry::make('creator.name')->label('Creada por')->placeholder('—'),
                    ])->columns(3),
                Infolists\Components\Section::make('Cómo se integra la plataforma')
                    ->schema([
                        Infolists\Components\TextEntry::make('base_url')
                            ->label('URL base')
                            ->state($base.'/api/v1/ext')
                            ->fontFamily('mono')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('docs_url')
                            ->label('Documentación')
                            ->state($base.'/docs/api')
                            ->url($base.'/docs/api', shouldOpenInNewTab: true),
                        Infolists\Components\TextEntry::make('example')
                            ->label('Ejemplo')
                            ->state(new HtmlString('<code>curl -H "Authorization: Bearer fec_…" '.e($base).'/api/v1/ext/documents</code>'))
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    /** Rota la credencial y lleva a la vista, donde la nueva llave se muestra una sola vez. */
    public static function rotateAction($action)
    {
        return $action
            ->label('Rotar llave')
            ->icon('heroicon-o-arrow-path')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Rotar llave de API')
            ->modalDescription('La llave actual deja de funcionar de inmediato. La nueva se mostrará una sola vez.')
            ->action(function (ApiKey $record, $livewire) {
                $plain = $record->rotate();
                session()->put(self::plainKeySessionKey($record), $plain);

                $livewire->redirect(self::getUrl('view', ['record' => $record]));
            });
    }

    public static function plainKeySessionKey(ApiKey|int $key): string
    {
        return 'admin.api_key_plain.'.($key instanceof ApiKey ? $key->id : $key);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApiKeys::route('/'),
            'create' => Pages\CreateApiKey::route('/create'),
            'view' => Pages\ViewApiKey::route('/{record}'),
            'edit' => Pages\EditApiKey::route('/{record}/edit'),
        ];
    }
}
