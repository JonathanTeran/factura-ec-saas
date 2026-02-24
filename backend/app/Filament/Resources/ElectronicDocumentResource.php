<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ElectronicDocumentResource\Pages;
use App\Models\SRI\ElectronicDocument;
use App\Enums\DocumentType;
use App\Enums\DocumentStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;

class ElectronicDocumentResource extends Resource
{
    protected static ?string $model = ElectronicDocument::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Monitoreo';

    protected static ?string $navigationLabel = 'Documentos Electrónicos';

    protected static ?string $modelLabel = 'Documento';

    protected static ?string $pluralModelLabel = 'Documentos Electrónicos';

    protected static ?int $navigationSort = 30;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::withoutGlobalScopes()->failed()->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Documento')
                    ->schema([
                        Forms\Components\Select::make('tenant_id')
                            ->label('Tenant')
                            ->relationship('tenant', 'name')
                            ->disabled(),
                        Forms\Components\Select::make('document_type')
                            ->label('Tipo')
                            ->options(collect(DocumentType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()]))
                            ->disabled(),
                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options(collect(DocumentStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()]))
                            ->disabled(),
                        Forms\Components\TextInput::make('access_key')
                            ->label('Clave de Acceso')
                            ->disabled()
                            ->columnSpanFull(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Tenant')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('document_type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        DocumentType::FACTURA => 'info',
                        DocumentType::NOTA_CREDITO => 'warning',
                        DocumentType::NOTA_DEBITO => 'orange',
                        DocumentType::RETENCION => 'purple',
                        DocumentType::GUIA_REMISION => 'gray',
                        DocumentType::LIQUIDACION_COMPRA => 'pink',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => $state->shortLabel()),
                Tables\Columns\TextColumn::make('series')
                    ->label('Serie')
                    ->searchable()
                    ->formatStateUsing(fn ($record) => $record->getDocumentNumber()),
                Tables\Columns\TextColumn::make('customer.business_name')
                    ->label('Cliente')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->customer?->business_name),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('USD')
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->money('USD')),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn ($state) => $state->color())
                    ->icon(fn ($state) => $state->icon())
                    ->formatStateUsing(fn ($state) => $state->label()),
                Tables\Columns\TextColumn::make('authorization_number')
                    ->label('Autorización')
                    ->searchable()
                    ->limit(15)
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('environment')
                    ->label('Ambiente')
                    ->badge()
                    ->color(fn ($state) => $state === '2' ? 'success' : 'warning')
                    ->formatStateUsing(fn ($state) => $state === '2' ? 'Producción' : 'Pruebas'),
                Tables\Columns\TextColumn::make('issue_date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sri_attempts')
                    ->label('Intentos')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('email_sent')
                    ->label('Email')
                    ->boolean()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tenant_id')
                    ->label('Tenant')
                    ->relationship('tenant', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('document_type')
                    ->label('Tipo')
                    ->options(collect(DocumentType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()])),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options(collect(DocumentStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])),
                Tables\Filters\SelectFilter::make('environment')
                    ->label('Ambiente')
                    ->options([
                        '1' => 'Pruebas',
                        '2' => 'Producción',
                    ]),
                Tables\Filters\Filter::make('failed')
                    ->label('Con errores')
                    ->query(fn (Builder $query) => $query->failed()),
                Tables\Filters\Filter::make('issue_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Desde'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('issue_date', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('issue_date', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('download_xml')
                    ->label('XML')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('gray')
                    ->visible(fn ($record) => $record->xml_authorized_path)
                    ->url(fn ($record) => route('admin.documents.download-xml', $record)),
                Tables\Actions\Action::make('download_ride')
                    ->label('RIDE')
                    ->icon('heroicon-o-document')
                    ->color('danger')
                    ->visible(fn ($record) => $record->ride_pdf_path)
                    ->url(fn ($record) => route('admin.documents.download-ride', $record)),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Información General')
                    ->schema([
                        Infolists\Components\TextEntry::make('tenant.name')
                            ->label('Tenant'),
                        Infolists\Components\TextEntry::make('document_type')
                            ->label('Tipo de Documento')
                            ->badge()
                            ->formatStateUsing(fn ($state) => $state->label()),
                        Infolists\Components\TextEntry::make('status')
                            ->label('Estado')
                            ->badge()
                            ->color(fn ($state) => $state->color())
                            ->formatStateUsing(fn ($state) => $state->label()),
                        Infolists\Components\TextEntry::make('environment')
                            ->label('Ambiente')
                            ->badge()
                            ->color(fn ($state) => $state === '2' ? 'success' : 'warning')
                            ->formatStateUsing(fn ($state) => $state === '2' ? 'Producción' : 'Pruebas'),
                    ])->columns(4),

                Infolists\Components\Section::make('Datos del Documento')
                    ->schema([
                        Infolists\Components\TextEntry::make('series')
                            ->label('Número')
                            ->formatStateUsing(fn ($record) => $record->getDocumentNumber()),
                        Infolists\Components\TextEntry::make('access_key')
                            ->label('Clave de Acceso')
                            ->copyable()
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('authorization_number')
                            ->label('Número de Autorización')
                            ->copyable()
                            ->placeholder('Sin autorización'),
                        Infolists\Components\TextEntry::make('authorization_date')
                            ->label('Fecha Autorización')
                            ->dateTime('d/m/Y H:i:s')
                            ->placeholder('Sin autorización'),
                        Infolists\Components\TextEntry::make('issue_date')
                            ->label('Fecha Emisión')
                            ->date('d/m/Y'),
                        Infolists\Components\TextEntry::make('due_date')
                            ->label('Fecha Vencimiento')
                            ->date('d/m/Y')
                            ->placeholder('-'),
                    ])->columns(2),

                Infolists\Components\Section::make('Cliente')
                    ->schema([
                        Infolists\Components\TextEntry::make('customer.identification')
                            ->label('Identificación'),
                        Infolists\Components\TextEntry::make('customer.business_name')
                            ->label('Razón Social'),
                        Infolists\Components\TextEntry::make('customer.email')
                            ->label('Email'),
                        Infolists\Components\TextEntry::make('customer.phone')
                            ->label('Teléfono')
                            ->placeholder('-'),
                    ])->columns(4),

                Infolists\Components\Section::make('Totales')
                    ->schema([
                        Infolists\Components\TextEntry::make('subtotal_0')
                            ->label('Subtotal 0%')
                            ->money('USD'),
                        Infolists\Components\TextEntry::make('subtotal_12')
                            ->label('Subtotal 12%')
                            ->money('USD'),
                        Infolists\Components\TextEntry::make('subtotal_15')
                            ->label('Subtotal 15%')
                            ->money('USD'),
                        Infolists\Components\TextEntry::make('total_discount')
                            ->label('Descuento')
                            ->money('USD'),
                        Infolists\Components\TextEntry::make('total_tax')
                            ->label('IVA')
                            ->money('USD'),
                        Infolists\Components\TextEntry::make('total')
                            ->label('TOTAL')
                            ->money('USD')
                            ->weight('bold')
                            ->size('lg'),
                    ])->columns(6),

                Infolists\Components\Section::make('Respuesta SRI')
                    ->schema([
                        Infolists\Components\TextEntry::make('sri_attempts')
                            ->label('Intentos'),
                        Infolists\Components\TextEntry::make('last_sri_attempt_at')
                            ->label('Último Intento')
                            ->dateTime('d/m/Y H:i:s')
                            ->placeholder('Sin intentos'),
                        Infolists\Components\KeyValueEntry::make('sri_response')
                            ->label('Respuesta')
                            ->columnSpanFull(),
                        Infolists\Components\KeyValueEntry::make('sri_errors')
                            ->label('Errores')
                            ->columnSpanFull(),
                    ])->columns(2)
                    ->collapsible(),

                Infolists\Components\Section::make('Envíos')
                    ->schema([
                        Infolists\Components\IconEntry::make('email_sent')
                            ->label('Email Enviado')
                            ->boolean(),
                        Infolists\Components\TextEntry::make('email_sent_at')
                            ->label('Fecha Email')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('-'),
                        Infolists\Components\IconEntry::make('whatsapp_sent')
                            ->label('WhatsApp Enviado')
                            ->boolean(),
                        Infolists\Components\TextEntry::make('whatsapp_sent_at')
                            ->label('Fecha WhatsApp')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('-'),
                    ])->columns(4)
                    ->collapsible(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ElectronicDocumentResource\RelationManagers\ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListElectronicDocuments::route('/'),
            'view' => Pages\ViewElectronicDocument::route('/{record}'),
        ];
    }
}
