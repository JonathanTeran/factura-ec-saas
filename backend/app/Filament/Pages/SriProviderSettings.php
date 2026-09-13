<?php

namespace App\Filament\Pages;

use App\Services\Settings\SriProviderSettings as ProviderSettings;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * RUC del proveedor del sistema (Resolución NAC-DGERCGC26-00000027 / Ficha
 * Técnica 2.34): se incluye automáticamente en la información adicional de
 * todos los comprobantes que emite la plataforma.
 */
class SriProviderSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationLabel = 'RUC proveedor SRI';

    protected static ?string $title = 'RUC del proveedor del sistema (SRI)';

    protected static ?string $navigationGroup = 'Sistema';

    protected static ?int $navigationSort = 30;

    protected static string $view = 'filament.pages.sri-provider-settings';

    public ?array $data = [];

    public function mount(ProviderSettings $settings): void
    {
        $this->form->fill($settings->all());
    }

    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Resolución NAC-DGERCGC26-00000027')
                    ->description('Los emisores que usan un sistema de terceros deben incluir el RUC del proveedor del sistema en la información adicional de cada comprobante electrónico (facturas, notas de crédito y débito, guías, liquidaciones y retenciones). Facturón lo agrega automáticamente al XML con el valor configurado aquí. Plazo de la ficha técnica 2.34: 26 de septiembre de 2026.')
                    ->schema([
                        Forms\Components\Toggle::make('enabled')
                            ->label('Incluir el RUC del proveedor en los comprobantes')
                            ->helperText('Desactívalo solo si el SRI deja de exigirlo.')
                            ->default(true),
                        Forms\Components\TextInput::make('provider_ruc')
                            ->label('RUC del proveedor (AmePhia / Facturón)')
                            ->helperText('13 dígitos. Debe ser el RUC con el establecimiento y la actividad J62021002 / J62021003 registrados en el SRI.')
                            ->placeholder('1790000000001')
                            ->maxLength(13)
                            ->regex('/^[0-9]{13}$/')
                            ->validationMessages(['regex' => 'El RUC debe tener exactamente 13 dígitos.'])
                            ->required(fn (Forms\Get $get) => (bool) $get('enabled')),
                        Forms\Components\TextInput::make('field_name')
                            ->label('Nombre del campo adicional')
                            ->helperText('Según la Ficha Técnica 2.34 del SRI es "RUC Proveedor". Cámbialo solo si el SRI publica otro nombre.')
                            ->default(ProviderSettings::FIELD_NAME)
                            ->maxLength(300)
                            ->required(),
                    ])->columns(1),
            ])
            ->statePath('data');
    }

    public function save(ProviderSettings $settings): void
    {
        $settings->save($this->form->getState());

        Notification::make()
            ->title('RUC del proveedor guardado')
            ->body('Los próximos comprobantes que se envíen al SRI incluirán el campo en su información adicional.')
            ->success()
            ->send();
    }
}
