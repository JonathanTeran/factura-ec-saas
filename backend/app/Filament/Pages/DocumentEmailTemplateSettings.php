<?php

namespace App\Filament\Pages;

use App\Services\Settings\DocumentEmailTemplateSettings as TemplateSettings;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\HtmlString;

class DocumentEmailTemplateSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationLabel = 'Correo de documentos';

    protected static ?string $navigationGroup = 'Sistema';

    protected static ?int $navigationSort = 30;

    protected static string $view = 'filament.pages.document-email-template-settings';

    public ?array $data = [];

    public function mount(TemplateSettings $settings): void
    {
        $this->form->fill($settings->all());
    }

    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Plantilla')
                    ->schema([
                        Forms\Components\TextInput::make('subject_template')
                            ->label('Asunto')
                            ->required(),
                        Forms\Components\TextInput::make('header_title')
                            ->label('Título del encabezado')
                            ->required(),
                        Forms\Components\TextInput::make('header_subtitle')
                            ->label('Subtítulo del encabezado'),
                        Forms\Components\TextInput::make('badge_text')
                            ->label('Texto de insignia')
                            ->required(),
                        Forms\Components\ColorPicker::make('accent_color')
                            ->label('Color principal')
                            ->required(),
                        Forms\Components\TextInput::make('cta_label')
                            ->label('Texto del botón')
                            ->required(),
                        Forms\Components\Toggle::make('show_portal_button')
                            ->label('Mostrar botón al portal'),
                    ])->columns(2),
                Forms\Components\Section::make('Contenido HTML')
                    ->description('Puedes usar HTML simple y placeholders dinámicos.')
                    ->schema([
                        Forms\Components\Textarea::make('body_html')
                            ->label('Cuerpo del correo')
                            ->rows(10)
                            ->required(),
                        Forms\Components\Textarea::make('footer_html')
                            ->label('Pie del correo')
                            ->rows(5)
                            ->required(),
                    ]),
                Forms\Components\Section::make('Placeholders disponibles')
                    ->schema([
                        Forms\Components\Placeholder::make('placeholders')
                            ->label('')
                            ->content(function (TemplateSettings $settings) {
                                $items = collect($settings->availablePlaceholders())
                                    ->map(fn (string $item) => "<code>{$item}</code>")
                                    ->implode(' · ');

                                return new HtmlString($items);
                            }),
                    ]),
                Forms\Components\Section::make('Vista previa')
                    ->schema([
                        Forms\Components\Placeholder::make('preview')
                            ->label('')
                            ->content(function (callable $get, TemplateSettings $settings) {
                                $compiled = $settings->compileFromData([
                                    'subject_template' => $get('subject_template'),
                                    'header_title' => $get('header_title'),
                                    'header_subtitle' => $get('header_subtitle'),
                                    'badge_text' => $get('badge_text'),
                                    'accent_color' => $get('accent_color'),
                                    'body_html' => $get('body_html'),
                                    'footer_html' => $get('footer_html'),
                                    'cta_label' => $get('cta_label'),
                                    'show_portal_button' => $get('show_portal_button'),
                                ], $settings->previewPlaceholders());

                                return new HtmlString(view('emails.document-authorized', [
                                    'mailTemplate' => $compiled,
                                    'summaryRows' => [
                                        'Tipo de documento' => 'Factura',
                                        'Número' => '001-001-000000123',
                                        'Fecha de emisión' => now()->format('d/m/Y'),
                                        'No. Autorización' => '1234567890123456789012345678901234567890123456789',
                                        'Total' => '$112.00',
                                    ],
                                    'accessKey' => '1234567890123456789012345678901234567890123456789',
                                    'ctaUrl' => route('portal.login'),
                                    'attachmentNames' => ['001-001-000000123.pdf', '001-001-000000123.xml'],
                                ])->render());
                            }),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(TemplateSettings $settings): void
    {
        $settings->save($this->form->getState());
        $this->form->fill($settings->all());

        Notification::make()
            ->title('Plantilla actualizada')
            ->success()
            ->send();
    }
}
