<?php

namespace App\Filament\Pages;

use App\Services\Payment\PayPal\PayPalCheckoutService;
use App\Services\Payment\PayPal\PayPalClient;
use App\Services\Payment\PayPal\PayPalException;
use App\Services\Settings\PayPalSettings as Settings;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Cobro de suscripciones con PayPal: credenciales de la app REST, entorno,
 * webhook y activación del método en el panel de los clientes.
 */
class PayPalSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'PayPal';

    protected static ?string $title = 'Pagos con PayPal';

    protected static ?string $navigationGroup = 'Sistema';

    protected static ?int $navigationSort = 31;

    protected static ?string $slug = 'paypal-settings';

    protected static string $view = 'filament.pages.paypal-settings';

    public ?array $data = [];

    public function mount(Settings $settings): void
    {
        $this->form->fill([
            'enabled' => $settings->isEnabled(),
            'mode' => $settings->mode(),
            'client_id' => $settings->clientId(),
            'client_secret' => null,
            'webhook_id' => $settings->webhookId(),
        ]);
    }

    public function form(Forms\Form $form): Forms\Form
    {
        $settings = app(Settings::class);

        return $form
            ->schema([
                Forms\Components\Section::make('Estado')
                    ->description('Con PayPal activo, tus clientes pagan su plan con saldo PayPal o con tarjeta dentro de PayPal y la suscripción se activa sola al confirmarse el cobro. La transferencia bancaria sigue disponible.')
                    ->schema([
                        Forms\Components\Toggle::make('enabled')
                            ->label('Aceptar pagos con PayPal en el panel')
                            ->helperText('Requiere el Client ID y el Secret del entorno elegido.'),
                        Forms\Components\Radio::make('mode')
                            ->label('Entorno')
                            ->options([
                                Settings::MODE_SANDBOX => 'Sandbox: pruebas con cuentas ficticias, sin dinero real',
                                Settings::MODE_LIVE => 'Live: cobros reales',
                            ])
                            ->required(),
                    ]),
                Forms\Components\Section::make('Credenciales de la app REST')
                    ->description('En developer.paypal.com, entra a Apps & Credentials, elige el entorno, crea una app y copia su Client ID y su Secret. Sandbox y Live tienen credenciales distintas.')
                    ->schema([
                        Forms\Components\TextInput::make('client_id')
                            ->label('Client ID')
                            ->maxLength(255)
                            ->autocomplete(false),
                        Forms\Components\TextInput::make('client_secret')
                            ->label('Secret')
                            ->password()
                            ->autocomplete('new-password')
                            ->maxLength(255)
                            ->placeholder($settings->hasClientSecret() ? 'Guardado. Déjalo vacío para conservarlo.' : null)
                            ->helperText('Se guarda cifrado y no se vuelve a mostrar.'),
                    ]),
                Forms\Components\Section::make('Webhook')
                    ->description('PayPal avisa a Facturón cuando un cobro se completa, queda en revisión o se reembolsa. Cubre al cliente que cierra la página antes de volver de PayPal. Regístralo después de guardar las credenciales.')
                    ->schema([
                        Forms\Components\Placeholder::make('webhook_url')
                            ->label('URL del webhook')
                            ->content($settings->webhookUrl()),
                        Forms\Components\TextInput::make('webhook_id')
                            ->label('ID del webhook')
                            ->helperText('Se completa con "Registrar webhook". Si lo creaste a mano en developer.paypal.com, pega aquí su ID.')
                            ->maxLength(100),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(Settings $settings): void
    {
        $state = $this->form->getState();
        $hasSecret = filled($state['client_secret'] ?? null) || $settings->hasClientSecret();

        if (($state['enabled'] ?? false) && (blank($state['client_id'] ?? null) || ! $hasSecret)) {
            Notification::make()
                ->danger()
                ->title('Faltan credenciales')
                ->body('Para activar PayPal carga el Client ID y el Secret.')
                ->send();

            return;
        }

        $modeChanged = ($state['mode'] ?? Settings::MODE_SANDBOX) !== $settings->mode();
        $newSecret = filled($state['client_secret'] ?? null);

        $settings->save($state);
        $this->data['client_secret'] = null;

        Notification::make()
            ->success()
            ->title('Configuración de PayPal guardada')
            ->body(($state['enabled'] ?? false)
                ? 'Tus clientes ya pueden pagar con PayPal. Usa "Probar conexión" para confirmar las credenciales.'
                : 'PayPal está desactivado en el panel de los clientes.')
            ->send();

        if ($modeChanged && ! $newSecret) {
            Notification::make()
                ->warning()
                ->title('Cambiaste de entorno')
                ->body('Las credenciales de Sandbox no sirven en Live, ni al revés. Carga el Client ID y el Secret del nuevo entorno y vuelve a registrar el webhook.')
                ->persistent()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('testConnection')
                ->label('Probar conexión')
                ->icon('heroicon-o-signal')
                ->color('gray')
                ->action(function (): void {
                    $settings = app(Settings::class);

                    try {
                        app(PayPalClient::class)->accessToken(fresh: true);

                        Notification::make()
                            ->success()
                            ->title('Conexión correcta')
                            ->body('PayPal aceptó las credenciales guardadas ('.($settings->isSandbox() ? 'Sandbox' : 'Live').').')
                            ->send();
                    } catch (PayPalException $e) {
                        Notification::make()->danger()->title('No se pudo conectar con PayPal')->body($e->getMessage())->send();
                    }
                }),
            Action::make('registerWebhook')
                ->label('Registrar webhook')
                ->icon('heroicon-o-bolt')
                ->requiresConfirmation()
                ->modalHeading('Registrar el webhook en PayPal')
                ->modalDescription(fn () => 'Se creará en tu cuenta de PayPal un webhook hacia '.app(Settings::class)->webhookUrl().' con los eventos de pagos, revisiones y reembolsos. Si ya existe uno con esa URL, se reutiliza.')
                ->modalSubmitActionLabel('Registrar')
                ->action(function (): void {
                    try {
                        $webhookId = app(PayPalCheckoutService::class)->registerWebhook();
                        $this->data['webhook_id'] = $webhookId;

                        Notification::make()
                            ->success()
                            ->title('Webhook registrado')
                            ->body("ID del webhook: {$webhookId}")
                            ->send();
                    } catch (PayPalException $e) {
                        Notification::make()->danger()->title('No se pudo registrar el webhook')->body($e->getMessage())->send();
                    }
                }),
        ];
    }
}
