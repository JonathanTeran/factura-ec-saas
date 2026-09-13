<?php

namespace App\Filament\Resources\ApiKeyResource\Pages;

use App\Filament\Resources\ApiKeyResource;
use App\Models\Tenant\ApiKey;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditApiKey extends EditRecord
{
    protected static string $resource = ApiKeyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            ApiKeyResource::rotateAction(Actions\Action::make('rotate')),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['grant_api_access'], $data['tenant_id']);

        $data['permissions'] = array_values(array_intersect(array_keys(ApiKey::SCOPES), (array) ($data['permissions'] ?? [])));

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return ApiKeyResource::getUrl('view', ['record' => $this->getRecord()]);
    }
}
