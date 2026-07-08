<?php

namespace App\Services\SRI;

use App\Enums\DocumentStatus;
use App\Exceptions\DocumentProcessingException;
use App\Exceptions\SriCommunicationException;
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

        // Descripción de marca en la firma XAdES (etsi:Description). Sin esto el
        // paquete usa un texto neutral; acá ponemos la marca propia. Nunca la de
        // terceros. Configurable por env SRI_SIGNATURE_DESCRIPTION.
        $this->sri->setDescripcionFirma(
            config('services.sri.signature_description', 'Comprobante electrónico emitido con Facturón EC · amephia.com')
        );

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
                '03' => $this->sri->liquidacionCompraFromArray($data),
                '04' => $this->sri->notaCreditoFromArray($data),
                '05' => $this->sri->notaDebitoFromArray($data),
                '06' => $this->sri->guiaRemisionFromArray($data),
                '07' => $this->sri->retencionFromArray($data),
                default => throw new DocumentProcessingException("Tipo de documento no soportado: {$doc->document_type->value}", $doc->id, 'build'),
            };

            $this->handleResult($doc, $result);

            return $result;

        } catch (SriCommunicationException $e) {
            Log::warning('SRI Processing Communication Warning', [
                'document_id' => $doc->id,
                'error' => $e->getMessage(),
                'context' => $e->context(),
            ]);

            $this->markAsContingency($doc, $e->getMessage());

            throw $e;
        } catch (SriRejectionException $e) {
            // handleResult() ya persistió status=REJECTED con el motivo real del
            // SRI. No sobrescribir con un mensaje genérico: solo propagar.
            Log::warning('SRI Processing Rejected', [
                'document_id' => $doc->id,
                'errors' => $e->errors,
            ]);

            throw $e;
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

            if ($this->isTransientFailure($e)) {
                $message = "SRI no disponible temporalmente: {$e->getMessage()}";
                $this->markAsContingency($doc, $message);

                throw new SriCommunicationException($message, $doc->id, $doc->access_key, 0, $e);
            }

            // Rechazo/validación del SRI con detalle (recepción DEVUELTA, XSD,
            // etc.): preservar los mensajes reales y marcar como RECHAZADO.
            if ($e instanceof \Teran\Sri\Exceptions\ValidationException) {
                $detail = $e->getErrors();

                // "CLAVE ACCESO REGISTRADA": un envío anterior ya llegó al SRI.
                // No se reenvía; se consulta la autorización real de esa clave.
                if ($doc->access_key && in_array('CLAVE ACCESO REGISTRADA', $detail, true)) {
                    $auth = $this->sri->consultarAutorizacion($doc->access_key);
                    $this->handleResult($doc, [
                        'autorizacion' => $auth,
                        'claveAcceso' => $doc->access_key,
                    ]);

                    return ['claveAcceso' => $doc->access_key];
                }

                $doc->update([
                    'status' => DocumentStatus::REJECTED,
                    'sri_errors' => ! empty($detail) ? $detail : ['general' => $e->getMessage()],
                    'sri_attempts' => $doc->sri_attempts + 1,
                    'last_sri_attempt_at' => now(),
                ]);
                throw new SriRejectionException($e->getMessage(), $doc->id, $doc->access_key, $detail, 0, $e);
            }

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
     * Re-consulta la autorización del SRI de un documento EN PROCESO y aplica
     * el resultado (autorizado / rechazado / sigue en proceso). Idempotente y
     * seguro: si no hay novedad, no cambia nada. Usado por el polling de la app.
     */
    public function refreshStatus(ElectronicDocument $doc): void
    {
        $pending = [DocumentStatus::PROCESSING, DocumentStatus::SENT, DocumentStatus::SIGNED];
        if (! in_array($doc->status, $pending, true) || empty($doc->access_key)) {
            return;
        }

        // Consultamos el SOAP crudo y lo parseamos nosotros mismos, sin depender
        // del DTO del vendor (que en 2.x no desenvuelve RespuestaAutorizacion-
        // Comprobante y reporta como 'ERROR' comprobantes que están AUTORIZADOS).
        // Así, aunque un composer install revierta el parche del vendor, el
        // polling de la app/web sigue reflejando el estado real del SRI.
        $env = $doc->company->sri_environment === '2' ? 'produccion' : 'pruebas';
        $raw = (new \Teran\Sri\Soap\SriSoapClient(30, 2))->autorizar($doc->access_key, $env);

        [$auth, $xmlAutorizado] = $this->parseAuthorizationSoap($raw);

        try {
            // Reutiliza el manejo de autorización (autorizado/rechazado/pending).
            $this->handleResult($doc, [
                'autorizacion' => $auth,
                'claveAcceso' => $doc->access_key,
                'xmlAutorizado' => $xmlAutorizado,
            ]);
        } catch (SriRejectionException|SriCommunicationException $e) {
            // handleResult ya persistió el estado (rejected / sigue en proceso).
        }
    }

    /**
     * Parsea la respuesta SOAP cruda de autorización del SRI, desenvolviendo el
     * nodo RespuestaAutorizacionComprobante. Devuelve [AutorizacionResponse|null,
     * xmlAutorizado|null].
     *
     * @return array{0: ?\Teran\Sri\Dto\AutorizacionResponse, 1: ?string}
     */
    private function parseAuthorizationSoap(object $raw): array
    {
        $root = $raw->RespuestaAutorizacionComprobante ?? $raw;

        $nodo = $root->autorizaciones->autorizacion ?? null;
        if (is_array($nodo)) {
            $nodo = $nodo[0] ?? null;
        }

        if (! $nodo) {
            // Sin nodo de autorización: el SRI aún no resolvió (o no lo recibió).
            return [new \Teran\Sri\Dto\AutorizacionResponse('ERROR', null, null, null, []), null];
        }

        $mensajes = [];
        $rawMsgs = $nodo->mensajes->mensaje ?? null;
        if ($rawMsgs !== null) {
            foreach (is_array($rawMsgs) ? $rawMsgs : [$rawMsgs] as $m) {
                $mensajes[] = [
                    'identificador' => (string) ($m->identificador ?? ''),
                    'mensaje' => (string) ($m->mensaje ?? ''),
                    'informacionAdicional' => (string) ($m->informacionAdicional ?? ''),
                    'tipo' => (string) ($m->tipo ?? ''),
                ];
            }
        }

        $auth = new \Teran\Sri\Dto\AutorizacionResponse(
            (string) ($nodo->estado ?? 'NO AUTORIZADO'),
            isset($nodo->numeroAutorizacion) ? (string) $nodo->numeroAutorizacion : null,
            isset($nodo->fechaAutorizacion) ? (string) $nodo->fechaAutorizacion : null,
            isset($nodo->comprobante) ? (string) $nodo->comprobante : null,
            $mensajes,
        );

        return [$auth, isset($nodo->comprobante) ? (string) $nodo->comprobante : null];
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
            Storage::put("{$basePath}/signed.xml", $result['xmlFirmado']);
            $update['xml_signed_path'] = "{$basePath}/signed.xml";
        }

        if ($auth && ($auth->estado ?? null) === 'AUTORIZADO') {
            $update['status'] = DocumentStatus::AUTHORIZED;
            $update['authorization_number'] = $auth->numeroAutorizacion ?? $result['claveAcceso'];
            // Limpiar residuos de intentos previos (contingencia, errores):
            // sin esto, la app/web muestran "el SRI aún está procesando" y un
            // "Detalle del error" sobre un documento ya AUTORIZADO.
            $update['sri_errors'] = null;

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
                Storage::put("{$basePath}/authorized.xml", $result['xmlAutorizado']);
                $update['xml_authorized_path'] = "{$basePath}/authorized.xml";
            }

            // Generar RIDE (PDF)
            $ridePath = $this->rideGenerator->generate($doc, $result);
            $update['ride_pdf_path'] = $ridePath;

        } else {
            $estado = $auth->estado ?? null;
            $errors = ($auth && isset($auth->mensajes)) ? (array) $auth->mensajes : [];

            // La autorización del SRI es ASÍNCRONA: tras "RECIBIDA", la consulta
            // puede volver sin autorización todavía (EN PROCESAMIENTO, o 'ERROR'
            // = lista de autorizaciones vacía en el DTO). Eso NO es un rechazo:
            // se deja EN PROCESO y se re-consulta más tarde.
            $pendiente = empty($errors) && in_array($estado, ['EN PROCESAMIENTO', 'ERROR', '', null], true);
            if ($pendiente) {
                $this->markAsContingency($doc, 'El SRI está procesando el comprobante; se consultará la autorización nuevamente.');
                throw new SriCommunicationException(
                    'El SRI aún está procesando el comprobante.',
                    $doc->id,
                    $doc->access_key,
                );
            }

            // Rechazo real (con motivo o estado NO AUTORIZADO/RECHAZADA).
            if (empty($errors)) {
                $errors = ['El SRI no autorizó el comprobante ('.($estado ?: 'sin estado').').'];
            }

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

    private function markAsContingency(ElectronicDocument $doc, string $message): void
    {
        $errors = $doc->sri_errors ?? [];
        $errors['contingency_active'] = true;
        $errors['contingency_message'] = $message;
        $errors['contingency_entered_at'] = $errors['contingency_entered_at'] ?? now()->toIso8601String();

        $doc->update([
            'status' => DocumentStatus::PROCESSING,
            'sri_errors' => $errors,
            'sri_attempts' => $doc->sri_attempts + 1,
            'last_sri_attempt_at' => now(),
        ]);
    }

    private function isTransientFailure(\Throwable $e): bool
    {
        $message = strtolower($e->getMessage());

        foreach ([
            'timeout',
            'timed out',
            'connection',
            'could not connect',
            'temporarily unavailable',
            'service unavailable',
            'network',
            'ssl',
            'curl',
            'failed to open stream',
        ] as $needle) {
            if (str_contains($message, $needle)) {
                return true;
            }
        }

        return false;
    }
}
