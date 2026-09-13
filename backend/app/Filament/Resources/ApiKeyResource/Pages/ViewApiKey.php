<?php

namespace App\Filament\Resources\ApiKeyResource\Pages;

use App\Filament\Resources\ApiKeyResource;
use App\Models\Tenant\ApiKey;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewApiKey extends ViewRecord
{
    protected static string $resource = ApiKeyResource::class;

    /** Llave en claro recién creada/rotada: se lee de la sesión una sola vez. */
    public ?string $plainKey = null;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->plainKey = session()->pull(ApiKeyResource::plainKeySessionKey($this->getRecord()));
    }

    public function infolist(Infolist $infolist): Infolist
    {
        $infolist = static::getResource()::infolist($infolist);

        if (! $this->plainKey) {
            return $infolist;
        }

        $plain = $this->plainKey;

        return $infolist->schema([
            Infolists\Components\Section::make('Llave nueva — cópiala ahora')
                ->description('Por seguridad solo se guarda un hash: al salir de esta página no podrás volver a verla. Entrégala a la plataforma que se integra.')
                ->icon('heroicon-o-exclamation-triangle')
                ->iconColor('warning')
                ->schema([
                    Infolists\Components\TextEntry::make('plain_key')
                        ->label('API key')
                        ->state($plain)
                        ->fontFamily('mono')
                        ->size('lg')
                        ->weight('bold')
                        ->copyable()
                        ->copyMessage('Llave copiada'),
                ]),
            ...$infolist->getComponents(),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            ApiKeyResource::rotateAction(Actions\Action::make('rotate')),
            Actions\Action::make('toggle')
                ->label(fn (ApiKey $record) => $record->is_active ? 'Desactivar' : 'Activar')
                ->icon(fn (ApiKey $record) => $record->is_active ? 'heroicon-o-pause-circle' : 'heroicon-o-play-circle')
                ->color(fn (ApiKey $record) => $record->is_active ? 'warning' : 'success')
                ->requiresConfirmation()
                ->action(function (ApiKey $record) {
                    $record->update(['is_active' => ! $record->is_active]);
                    $this->refreshFormData(['is_active']);
                }),
            Actions\DeleteAction::make(),
        ];
    }
}
