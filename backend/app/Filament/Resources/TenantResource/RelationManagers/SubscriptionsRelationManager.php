<?php

namespace App\Filament\Resources\TenantResource\RelationManagers;

use App\Enums\SubscriptionStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SubscriptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'subscriptions';

    protected static ?string $title = 'Suscripciones';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('plan_id')
                    ->label('Plan')
                    ->relationship('plan', 'name')
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label('Estado')
                    ->options(collect(SubscriptionStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()]))
                    ->required(),
                Forms\Components\Select::make('billing_cycle')
                    ->label('Ciclo')
                    ->options([
                        'monthly' => 'Mensual',
                        'yearly' => 'Anual',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('amount')
                    ->label('Monto')
                    ->numeric()
                    ->prefix('$')
                    ->required(),
                Forms\Components\DateTimePicker::make('starts_at')
                    ->label('Inicio'),
                Forms\Components\DateTimePicker::make('ends_at')
                    ->label('Fin'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('plan.name')
                    ->label('Plan'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (SubscriptionStatus $state): string => $state->color()),
                Tables\Columns\TextColumn::make('billing_cycle')
                    ->label('Ciclo')
                    ->formatStateUsing(fn ($state) => $state === 'yearly' ? 'Anual' : 'Mensual'),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Monto')
                    ->money('USD'),
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Inicio')
                    ->date('d/m/Y'),
                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Fin')
                    ->date('d/m/Y'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
