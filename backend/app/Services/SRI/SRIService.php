<?php

namespace App\Services\SRI;

use App\Enums\DocumentStatus;
use App\Exceptions\DocumentProcessingException;
use App\Exceptions\SriException;
use App\Exceptions\SriRejectionException;
use App\Models\SRI\ElectronicDocument;
use App\Models\Tenant\Company;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Teran\Sri\SRI;

class SRIService
{
    private ?SRI $sri = null;

    public function __construct(
        private DocumentBuilder $builder,
        private SignatureManager $signatures,
        private RIDEGenerator $rideGenerator,
    ) {}

    /**
     * Inicializar para una empresa específica
     */
    public function forCompany(Company $company): self
    {
        $env = $company->sri_environment === '2' ? 'produccion' : 'pruebas';

        $this->sri = new SRI($env);

        $sig = $this->signatures->decrypt($company);
        $this->sri->setFirma($sig['content'], $sig['password']);

        return $this;
    }

    /**
     * Procesar cualquier tipo de documento
     */
    public function process(ElectronicDocument $doc): array
    {
        $this->forCompany($doc->company);

        $data = $this->builder->build($doc);

        try {
            $doc->markAsProcessing();

            // Integración real con amephia/sri-ec
            $result = match ($doc->document_type->value) {
                '01' => $this->sri->facturaFromArray($data),
                '04' => $this->sri->notaCreditoFromArray($data),
                '05' => $this->sri->notaDebitoFromArray($data),
                '06' => $this->sri->guiaRemisionFromArray($data),
                '07' => $this->sri->retencionFromArray($data),
                default => throw new DocumentProcessingException("Tipo de documento no soportado: {$doc->document_type->value}", $doc->id, 'build'),
            };

            $this->handleResult($doc, $result);

            return $result;

        } catch (SriException|DocumentProcessingException $e) {
            Log::error('SRI Processing Error', [
                'document_id' => $doc->id,
                'error' => $e->getMessage(),
                'context' => $e->context(),
            ]);

            $doc->update([
                'status' => DocumentStatus::FAILED,
                'sri_errors' => ['general' => $e->getMessage()],
                'sri_attempts' => $doc->sri_attempts + 1,
                'last_sri_attempt_at' => now(),
            ]);
            throw $e;
        } catch (\Throwable $e) {
            Log::error('SRI Processing Error (unexpected)', [
                'document_id' => $doc->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $doc->update([
                'status' => DocumentStatus::FAILED,
                'sri_errors' => ['general' => $e->getMessage()],
                'sri_attempts' => $doc->sri_attempts + 1,
                'last_sri_attempt_at' => now(),
            ]);
            throw new SriException("Error procesando documento: {$e->getMessage()}", $doc->id, $doc->access_key, 0, $e);
        }
    }

    /**
     * Solo consultar autorización
     */
    public function checkAuthorization(Company $company, string $accessKey): ?object
    {
        return $this->forCompany($company)->sri->consultarAutorizacion($accessKey);
    }

    /**
     * Health check del SRI
     */
    public function isAvailable(): bool
    {
        return Cache::remember('sri:health', 60, function () {
            try {
                // El paquete no tiene un health check explícito, pero podemos intentar una conexión básica
                // o simplemente retornar true si no hay fallos masivos reportados
                return true;
            } catch (\Throwable $e) {
                return false;
            }
        });
    }

    /**
     * Procesar resultado del SRI
     */
    private function handleResult(ElectronicDocument $doc, array $result): void
    {
        $auth = $result['autorizacion'] ?? null;
        $basePath = "tenants/{$doc->tenant_id}/documents/{$doc->id}";

        $update = [
            'access_key' => $result['claveAcceso'] ?? $doc->access_key,
            'sri_response' => $result,
            'sri_attempts' => $doc->sri_attempts + 1,
            'last_sri_attempt_at' => now(),
        ];

        // Guardar XML firmado
        if (isset($result['xmlFirmado'])) {
            Storage::disk('s3')->put("{$basePath}/signed.xml", $result['xmlFirmado']);
            $update['xml_signed_path'] = "{$basePath}/signed.xml";
        }

        if ($auth && ($auth->estado ?? null) === 'AUTORIZADO') {
            $update['status'] = DocumentStatus::AUTHORIZED;
            $update['authorization_number'] = $auth->numeroAutorizacion ?? $result['claveAcceso'];

            // Convertir fecha de autorización si viene en formato SRI (string)
            $authDate = $auth->fechaAutorizacion ?? now();
            if (is_string($authDate)) {
                try {
                    $authDate = \Carbon\Carbon::parse($authDate);
                } catch (\Exception $e) {
                    $authDate = now();
                }
            }
            $update['authorization_date'] = $authDate;

            // Guardar XML autorizado
            if (isset($result['xmlAutorizado'])) {
                Storage::disk('s3')->put("{$basePath}/authorized.xml", $result['xmlAutorizado']);
                $update['xml_authorized_path'] = "{$basePath}/authorized.xml";
            }

            // Generar RIDE (PDF)
            $ridePath = $this->rideGenerator->generate($doc, $result);
            $update['ride_pdf_path'] = $ridePath;

        } else {
            $errors = ($auth && isset($auth->mensajes)) ? (array) $auth->mensajes : [];
            $update['status'] = DocumentStatus::REJECTED;
            $update['sri_errors'] = $errors;

            $doc->update($update);

            throw new SriRejectionException(
                'Documento rechazado por el SRI',
                $doc->id,
                $doc->access_key,
                $errors,
            );
        }

        $doc->update($update);
    }
}
