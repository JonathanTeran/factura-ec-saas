<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FootballMatchResource\Pages;
use App\Models\Arbitros\FootballMatch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FootballMatchResource extends Resource
{
    protected static ?string $model = FootballMatch::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Árbitros';

    protected static ?string $navigationLabel = 'Partidos';

    protected static ?string $modelLabel = 'Partido';

    protected static ?string $pluralModelLabel = 'Partidos';

    protected static ?int $navigationSort = 30;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Partido')
                    ->schema([
                        Forms\Components\Select::make('championship_id')
                            ->label('Campeonato')
                            ->relationship('championship', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\DatePicker::make('match_date')
                            ->label('Fecha del partido')
                            ->required(),
                        Forms\Components\Select::make('home_club_id')
                            ->label('Club local')
                            ->relationship('homeClub', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('away_club_id')
                            ->label('Club visitante')
                            ->relationship('awayClub', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('stage')
                            ->label('Etapa'),
                        Forms\Components\Select::make('source')
                            ->label('Origen')
                            ->options([
                                'scraper' => 'Scraper',
                                'manual' => 'Manual',
                            ])
                            ->default('manual'),
                        Forms\Components\KeyValue::make('officials')
                            ->label('Cuerpo arbitral')
                            ->keyLabel('Rol')
                            ->valueLabel('Nombre')
                            ->helperText('center, assistant_1, assistant_2, fourth')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('match_date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('championship.name')
                    ->label('Campeonato')
                    ->limit(30)
                    ->searchable(),
                Tables\Columns\TextColumn::make('home_club_id')
                    ->label('Partido')
                    ->formatStateUsing(fn ($record) => ($record->homeClub?->name ?? '—') . ' vs ' . ($record->awayClub?->name ?? '—'))
                    ->searchable(false),
                Tables\Columns\TextColumn::make('center_official')
                    ->label('Árbitro central')
                    ->getStateUsing(fn ($record) => $record->officials['center'] ?? '—'),
                Tables\Columns\TextColumn::make('source')
                    ->label('Origen')
                    ->badge(),
                Tables\Columns\TextColumn::make('published_at')
                    ->label('Publicado')
                    ->since(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('championship')
                    ->label('Campeonato')
                    ->relationship('championship', 'name'),
                Tables\Filters\SelectFilter::make('source')
                    ->label('Origen')
                    ->options([
                        'scraper' => 'Scraper',
                        'manual' => 'Manual',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('match_date', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFootballMatches::route('/'),
            'create' => Pages\CreateFootballMatch::route('/create'),
            'edit' => Pages\EditFootballMatch::route('/{record}/edit'),
        ];
    }
}
