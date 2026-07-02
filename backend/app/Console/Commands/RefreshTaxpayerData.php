<?php

namespace App\Console\Commands;

use App\Models\Tenant\Company;
use App\Notifications\TaxpayerDataChangedNotification;
use App\Services\SRI\RucLookupService;
use Illuminate\Console\Command;

class RefreshTaxpayerData extends Command
{
    protected $signature = 'sri:refresh-taxpayer-data';
    protected $description = 'Re-consulta el catastro del SRI y actualiza régimen y flags tributarios de cada empresa activa';

    public function handle(RucLookupService $lookupService): int
    {
        $updated = 0;

        Company::query()
            ->withoutGlobalScopes()
            ->where('is_active', true)
            ->with('tenant.owner')
            ->chunkById(50, function ($companies) use ($lookupService, &$updated) {
                foreach ($companies as $company) {
                    $taxpayer = $lookupService->lookup($company->ruc);

                    if ($taxpayer === null) {
                        $this->warn("SRI sin respuesta para {$company->ruc}, omitiendo.");
                        continue;
                    }

                    $newValues = [
                        'rimpe_type' => match ($taxpayer['regime']) {
                            'rimpe_emprendedor' => 'emprendedor',
                            'rimpe_popular' => 'negocio_popular',
                            default => 'none',
                        },
                        'obligated_accounting' => $taxpayer['obligated_accounting'],
                        'special_taxpayer' => $taxpayer['special_taxpayer'],
                    ];

                    $changes = [];
                    foreach ($newValues as $field => $newValue) {
                        $oldValue = $field === 'rimpe_type'
                            ? ($company->{$field} ?? 'none')
                            : (bool) $company->{$field};

                        if ($oldValue !== $newValue) {
                            $changes[$field] = ['old' => $oldValue, 'new' => $newValue];
                        }
                    }

                    if ($changes === []) {
                        continue;
                    }

                    $company->update($newValues);
                    $updated++;

                    $this->line("Actualizada {$company->business_name}: " . implode(', ', array_keys($changes)));

                    $company->tenant?->owner?->notify(
                        new TaxpayerDataChangedNotification($company, $changes)
                    );
                }
            });

        $this->info("Se actualizaron {$updated} empresa(s) desde el catastro del SRI.");

        return self::SUCCESS;
    }
}
