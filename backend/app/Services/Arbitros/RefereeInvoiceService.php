<?php

namespace App\Services\Arbitros;

use App\Enums\DocumentType;
use App\Jobs\SRI\ProcessDocumentJob;
use App\Models\Arbitros\OfficiatedMatch;
use App\Models\SRI\DocumentItem;
use App\Models\SRI\ElectronicDocument;
use App\Models\Tenant\Customer;
use App\Models\Tenant\EmissionPoint;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Services\SRI\AccessKeyService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Facturación en lote del vertical de árbitros: por CADA partido seleccionado
 * emite UNA factura con el concepto que exige la FEF (§4.4 y §5.1 del spec).
 * Reusa la cañería probada del sistema (ElectronicDocument + DocumentItem +
 * AccessKeyService + ProcessDocumentJob).
 */
class RefereeInvoiceService
{
    public function __construct(private InvoiceWindow $window)
    {
    }

    /**
     * Emite 1 factura por partido. Devuelve el resultado por cada partido.
     *
     * @param  int[]  $matchIds
     * @return array<int, array{id: int, status: string, message: ?string, document_id: ?int}>
     */
    public function invoiceBatch(
        Tenant $tenant,
        array $matchIds,
        Customer $customer,
        User $creator,
        ?EmissionPoint $emissionPoint = null
    ): array {
        $results = [];

        $matches = OfficiatedMatch::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->whereIn('id', $matchIds)
            ->with(['championship', 'homeClub', 'awayClub'])
            ->get();

        $company = $tenant->companies()->where('is_active', true)->first();

        if (! $company) {
            throw new \RuntimeException('La cuenta no tiene una empresa emisora configurada.');
        }

        $emissionPoint ??= $this->defaultEmissionPoint($tenant);

        if (! $emissionPoint) {
            throw new \RuntimeException('No hay un punto de emisión activo configurado.');
        }

        $branch = $emissionPoint->branch;
        $checklist = $company->emissionReadinessChecklist();
        $canSend = $checklist['basic_data'] && $checklist['establishments'] && $checklist['digital_signature'];

        foreach ($matches as $match) {
            $results[] = $this->invoiceOne($tenant, $match, $customer, $creator, $company, $branch, $emissionPoint, $canSend);
        }

        return $results;
    }

    /** @return array{id: int, status: string, message: ?string, document_id: ?int} */
    private function invoiceOne(
        Tenant $tenant,
        OfficiatedMatch $match,
        Customer $customer,
        User $creator,
        $company,
        $branch,
        EmissionPoint $emissionPoint,
        bool $canSend
    ): array {
        $base = ['id' => $match->id, 'document_id' => null];

        // Solo pendientes (o bloqueados que reintenta) son facturables.
        if (! in_array($match->status, [OfficiatedMatch::STATUS_PENDING, OfficiatedMatch::STATUS_BLOCKED_WINDOW], true)) {
            return $base + ['status' => 'skipped', 'message' => 'El partido ya fue facturado o está en proceso.'];
        }

        if ((float) $match->fee <= 0) {
            return $base + ['status' => 'error', 'message' => 'El partido no tiene valor configurado.'];
        }

        // Ventana FEF (§5.2): fuera de fecha queda bloqueado, nunca se pierde.
        $window = $this->window->evaluate($match);
        if (! $window['open']) {
            $match->update(['status' => OfficiatedMatch::STATUS_BLOCKED_WINDOW]);

            return $base + ['status' => 'blocked_window', 'message' => $window['reason']];
        }

        if (! $tenant->canIssueDocuments()) {
            return $base + ['status' => 'error', 'message' => 'Alcanzaste el límite de documentos de tu plan.'];
        }

        $document = DB::transaction(function () use ($tenant, $match, $customer, $creator, $company, $branch, $emissionPoint) {
            $document = ElectronicDocument::create([
                'tenant_id' => $tenant->id,
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'emission_point_id' => $emissionPoint->id,
                'customer_id' => $customer->id,
                'created_by' => $creator->id,
                'document_type' => DocumentType::FACTURA->value,
                'environment' => $company->sri_environment ?? '1',
                'series' => sprintf('%s-%s', $branch->code ?? '001', $emissionPoint->code ?? '001'),
                'sequential' => $this->nextSequential($tenant, $company->id, $emissionPoint->id),
                'status' => 'draft',
                'issue_date' => now()->toDateString(),
                'currency' => 'USD',
                'payment_methods' => [['code' => '01', 'amount' => (float) $match->fee]],
            ]);

            $taxCode = (string) data_get($tenant->settings, 'referee_tax_percentage_code', '0');

            DocumentItem::create([
                'electronic_document_id' => $document->id,
                'main_code' => 'ARB-' . $match->id,
                'description' => $this->concept($match),
                'quantity' => 1,
                'unit_price' => (float) $match->fee,
                'discount' => 0,
                'subtotal' => (float) $match->fee,
                'tax_code' => '2',
                'tax_percentage_code' => $taxCode,
                'tax_rate' => $this->rateFor($taxCode),
                'tax_base' => (float) $match->fee,
                'tax_value' => round((float) $match->fee * $this->rateFor($taxCode) / 100, 2),
            ]);

            $document->load('items');
            $document->calculateTotals();
            $document->update(['access_key' => app(AccessKeyService::class)->generate($document->fresh())]);

            $match->update([
                'status' => OfficiatedMatch::STATUS_QUEUED,
                'electronic_document_id' => $document->id,
            ]);

            return $document;
        });

        if ($canSend) {
            ProcessDocumentJob::dispatch($document);

            return array_merge($base, [
                'status' => 'queued',
                'message' => 'Factura enviada al SRI.',
                'document_id' => $document->id,
            ]);
        }

        return array_merge($base, [
            'status' => 'draft',
            'message' => 'Factura creada en borrador (falta firma o datos del emisor; envíala desde Facturas).',
            'document_id' => $document->id,
        ]);
    }

    /**
     * Concepto automático que exige la FEF (§5.1):
     * "Barcelona - Emelec del 14 de julio de 2025, campeonato Formativa Azul 2".
     * Plantilla configurable via arbitros.concept_template.
     */
    public function concept(OfficiatedMatch $match): string
    {
        $template = (string) config(
            'arbitros.concept_template',
            '{home} - {away} del {date}, campeonato {championship}'
        );

        $date = Carbon::parse($match->match_date)
            ->locale('es')
            ->translatedFormat('j \d\e F \d\e Y');

        return strtr($template, [
            '{home}' => $match->homeClub?->name ?? 'Local',
            '{away}' => $match->awayClub?->name ?? 'Visitante',
            '{date}' => $date,
            '{championship}' => $match->championship?->name ?? '',
        ]);
    }

    private function rateFor(string $taxPercentageCode): float
    {
        return match ($taxPercentageCode) {
            '0', '6', '7' => 0.0,
            '5' => 5.0,
            '8' => 8.0,
            '2' => 12.0,
            '10' => 13.0,
            '4' => 15.0,
            default => 0.0,
        };
    }

    private function nextSequential(Tenant $tenant, int $companyId, int $emissionPointId): string
    {
        $last = ElectronicDocument::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->where('company_id', $companyId)
            ->where('emission_point_id', $emissionPointId)
            ->where('document_type', DocumentType::FACTURA->value)
            ->max('sequential');

        return str_pad($last ? (int) $last + 1 : 1, 9, '0', STR_PAD_LEFT);
    }

    private function defaultEmissionPoint(Tenant $tenant): ?EmissionPoint
    {
        return EmissionPoint::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('id')
            ->first();
    }
}
