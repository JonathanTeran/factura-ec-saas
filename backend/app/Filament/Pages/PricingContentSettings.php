<?php

namespace App\Filament\Pages;

use App\Services\Settings\PricingContentSettings as PricingSettings;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class PricingContentSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'Textos de precios (web)';

    protected static ?string $navigationGroup = 'Sistema';

    protected static ?int $navigationSort = 31;

    protected static string $view = 'filament.pages.pricing-content-settings';

    public ?array $data = [];

    public function mount(PricingSettings $settings): void
    {
        $this->form->fill($settings->all());
    }

    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Encabezado de la sección de precios')
                    ->description('Estos textos se muestran en la página pública (landing), sobre las tarjetas de planes.')
                    ->schema([
                        Forms\Components\TextInput::make('eyebrow')
                            ->label('Etiqueta pequeña')
                            ->helperText('Texto en mayúsculas sobre el título.')
                            ->maxLength(40)
                            ->required(),
                        Forms\Components\TextInput::make('title')
                            ->label('Título')
                            ->maxLength(120)
                            ->required(),
                        Forms\Components\Textarea::make('subtitle')
                            ->label('Subtítulo')
                            ->rows(2)
                            ->maxLength(250)
                            ->required(),
                    ])->columns(1),

                Forms\Components\Section::make('Badge diferenciador')
                    ->schema([
                        Forms\Components\Toggle::make('badge_enabled')
                            ->label('Mostrar badge'),
                        Forms\Components\TextInput::make('badge_text')
                            ->label('Texto del badge')
                            ->maxLength(120)
                            ->visible(fn (Forms\Get $get) => (bool) $get('badge_enabled')),
                    ])->columns(1),

                Forms\Components\Section::make('Nota al pie')
                    ->schema([
                        Forms\Components\Textarea::make('footer_note')
                            ->label('Nota bajo las tarjetas')
                            ->rows(2)
                            ->maxLength(250)
                            ->required(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(PricingSettings $settings): void
    {
        $settings->save($this->form->getState());
        $this->form->fill($settings->all());

        Notification::make()
            ->title('Textos de precios actualizados')
            ->body('Los cambios ya se ven en la página pública.')
            ->success()
            ->send();
    }
}
