<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'document_type' => $this->document_type,
            'document_type_label' => $this->document_type?->label(),
            'document_number' => $this->document_number,
            'access_key' => $this->access_key,
            'environment' => $this->environment,
            'environment_label' => $this->environment === '2' ? 'Producción' : 'Pruebas',
            'establishment_code' => $this->branch?->code,
            'emission_point_code' => $this->emissionPoint?->code,
            'series' => $this->series,
            'sequential' => $this->sequential,
            'issue_date' => $this->issue_date?->toDateString(),
            'issue_datetime' => $this->issue_date?->toISOString(),
            'currency' => $this->currency,
            'subtotal_no_tax' => (float) $this->subtotal_no_tax,
            'subtotal_0' => (float) $this->subtotal_0,
            'subtotal_5' => (float) $this->subtotal_5,
            'subtotal_12' => (float) $this->subtotal_12,
            'subtotal_15' => (float) $this->subtotal_15,
            'total_discount' => (float) $this->total_discount,
            'total_tax' => (float) $this->total_tax,
            'tip' => (float) $this->tip,
            'total' => (float) $this->total,
            'payment_methods' => $this->payment_methods,
            'status' => $this->status,
            'status_label' => $this->status?->label(),
            'status_color' => $this->status?->color(),
            'authorization_number' => $this->authorization_number,
            'authorization_date' => $this->authorization_date?->toISOString(),
            'sri_messages' => $this->sri_response['messages'] ?? null,
            'sri_errors' => $this->sri_errors,
            'error_details' => $this->errorDetails(),
            'contingency_active' => (bool) data_get($this->sri_errors, 'contingency_active', false),
            'contingency_message' => data_get($this->sri_errors, 'contingency_message'),
            'additional_info' => $this->additional_info,
            'email_sent' => (bool) $this->email_sent,
            'email_sent_at' => $this->email_sent_at?->toISOString(),
            'email_sent_to' => $this->email_sent_to,
            'has_ride' => ! empty($this->ride_pdf_path ?? $this->ride_path),
            'has_xml' => ! empty($this->xml_signed_path),
            'customer' => $this->whenLoaded('customer', fn () => new CustomerResource($this->customer)),
            'company' => $this->whenLoaded('company', fn () => new CompanyResource($this->company)),
            'items' => $this->whenLoaded('items', fn () => DocumentItemResource::collection($this->items)),
            'withholding_details' => $this->whenLoaded('withholdingDetails', fn () => $this->withholdingDetails->map(fn ($detail) => [
                'id' => $detail->id,
                'support_doc_code' => $detail->support_doc_code,
                'support_doc_number' => $detail->support_doc_number,
                'support_doc_date' => $detail->support_doc_date?->toDateString(),
                'support_doc_total' => (float) $detail->support_doc_total,
                'support_reason_code' => $detail->support_reason_code,
                'tax_type' => $detail->tax_type,
                'retention_code' => $detail->retention_code,
                'tax_base' => (float) $detail->tax_base,
                'retention_rate' => (float) $detail->retention_rate,
                'retained_value' => (float) $detail->retained_value,
            ])),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }

    /**
     * Lista legible con TODO el detalle de errores/observaciones: errores
     * fatales de firma/envío (sri_errors) y mensajes del SRI (sri_response).
     *
     * @return array<int, string>
     */
    protected function errorDetails(): array
    {
        $out = [];
        $errors = $this->sri_errors;

        if (is_array($errors)) {
            if (! empty($errors['fatal'])) {
                $out[] = (string) $errors['fatal'];
            }
            foreach (($errors['messages'] ?? $errors['errors'] ?? []) as $m) {
                $out[] = $this->formatSriMessage($m);
            }
            foreach ($errors as $key => $value) {
                if (in_array($key, ['fatal', 'messages', 'errors', 'contingency_active', 'contingency_message'], true)) {
                    continue;
                }
                if (is_string($value) && $value !== '') {
                    $out[] = $value;
                } elseif (is_array($value) && array_is_list($value)) {
                    // Lista (validaciones locales = strings; rechazos SRI = objetos).
                    foreach ($value as $item) {
                        $out[] = is_string($item) ? $item : $this->formatSriMessage($item);
                    }
                } elseif (is_array($value)) {
                    // Mensaje de rechazo del SRI (identificador/mensaje/info).
                    $out[] = $this->formatSriMessage($value);
                }
            }
        }

        foreach (($this->sri_response['messages'] ?? []) as $m) {
            $out[] = $this->formatSriMessage($m);
        }

        return array_values(array_unique(array_filter($out, fn ($v) => trim((string) $v) !== '')));
    }

    protected function formatSriMessage(mixed $m): string
    {
        if (is_array($m)) {
            $id = $m['identificador'] ?? '';
            $msg = $m['mensaje'] ?? ($m['message'] ?? '');
            $extra = $m['informacionAdicional'] ?? ($m['info_adicional'] ?? '');
            $head = trim(($id !== '' ? "[$id] " : '').$msg);
            if ($extra !== '') {
                return trim($head === '' ? $extra : "$head — $extra");
            }

            return $head !== '' ? $head : (string) json_encode($m, JSON_UNESCAPED_UNICODE);
        }

        return (string) $m;
    }
}
