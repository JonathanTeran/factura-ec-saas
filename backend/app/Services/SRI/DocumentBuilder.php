<?php

namespace App\Services\SRI;

use App\Models\SRI\ElectronicDocument;
use App\Enums\DocumentType;

class DocumentBuilder
{
    /**
     * Construir array según tipo de documento
     */
    public function build(ElectronicDocument $doc): array
    {
        return match ($doc->document_type) {
            DocumentType::FACTURA => $this->invoice($doc),
            DocumentType::NOTA_CREDITO => $this->creditNote($doc),
            DocumentType::NOTA_DEBITO => $this->debitNote($doc),
            DocumentType::GUIA_REMISION => $this->waybill($doc),
            DocumentType::RETENCION => $this->withholding($doc),
            default => throw new \InvalidArgumentException("Tipo: {$doc->document_type->value}"),
        };
    }

    public function invoice(ElectronicDocument $doc): array
    {
        $company = $doc->company;
        $customer = $doc->customer;
        $branch = $doc->branch;

        return [
            'infoTributaria' => $this->infoTributaria($doc),
            'infoFactura' => [
                'fechaEmision' => $doc->issue_date->format('d/m/Y'),
                'dirEstablecimiento' => $branch->address,
                'contribuyenteEspecial' => $company->special_taxpayer_number,
                'obligadoContabilidad' => $company->obligated_accounting ? 'SI' : 'NO',
                'tipoIdentificacionComprador' => $customer->identification_type->value,
                'razonSocialComprador' => $customer->name,
                'identificacionComprador' => $customer->identification,
                'direccionComprador' => $customer->address ?? '',
                'totalSinImpuestos' => $this->fmt($doc->total - $doc->total_tax - $doc->total_ice),
                'totalDescuento' => $this->fmt($doc->total_discount),
                'importeTotal' => $this->fmt($doc->total),
                'moneda' => $doc->currency,
                'totalConImpuestos' => $this->taxTotals($doc),
                'propina' => $this->fmt($doc->tip),
                'pagos' => $this->payments($doc),
            ],
            'detalles' => $this->items($doc),
            'infoAdicional' => $this->additionalInfo($doc),
        ];
    }

    public function creditNote(ElectronicDocument $doc): array
    {
        return [
            'infoTributaria' => $this->infoTributaria($doc),
            'infoNotaCredito' => [
                'fechaEmision' => $doc->issue_date->format('d/m/Y'),
                'dirEstablecimiento' => $doc->branch->address,
                'tipoIdentificacionComprador' => $doc->customer->identification_type->value,
                'razonSocialComprador' => $doc->customer->name,
                'identificacionComprador' => $doc->customer->identification,
                'contribuyenteEspecial' => $doc->company->special_taxpayer_number,
                'obligadoContabilidad' => $doc->company->obligated_accounting ? 'SI' : 'NO',
                'codDocModificado' => $doc->related_document_type,
                'numDocModificado' => $doc->related_document_number,
                'fechaEmisionDocSustento' => $doc->related_document_date?->format('d/m/Y'),
                'totalSinImpuestos' => $this->fmt($doc->total - $doc->total_tax),
                'valorModificacion' => $this->fmt($doc->total),
                'moneda' => 'DOLAR',
                'totalConImpuestos' => $this->taxTotals($doc),
                'motivo' => $doc->additional_info['motivo'] ?? 'Devolución',
            ],
            'detalles' => $this->items($doc),
        ];
    }

    public function debitNote(ElectronicDocument $doc): array
    {
        return [
            'infoTributaria' => $this->infoTributaria($doc),
            'infoNotaDebito' => [
                'fechaEmision' => $doc->issue_date->format('d/m/Y'),
                'dirEstablecimiento' => $doc->branch->address,
                'tipoIdentificacionComprador' => $doc->customer->identification_type->value,
                'razonSocialComprador' => $doc->customer->name,
                'identificacionComprador' => $doc->customer->identification,
                'contribuyenteEspecial' => $doc->company->special_taxpayer_number,
                'obligadoContabilidad' => $doc->company->obligated_accounting ? 'SI' : 'NO',
                'codDocModificado' => $doc->related_document_type,
                'numDocModificado' => $doc->related_document_number,
                'fechaEmisionDocSustento' => $doc->related_document_date?->format('d/m/Y'),
                'totalSinImpuestos' => $this->fmt($doc->total - $doc->total_tax),
                'valorTotal' => $this->fmt($doc->total),
                'pagos' => $this->payments($doc),
            ],
            'motivos' => $doc->additional_info['motivos'] ?? [],
        ];
    }

    public function withholding(ElectronicDocument $doc): array
    {
        return [
            'infoTributaria' => $this->infoTributaria($doc),
            'infoCompRetencion' => [
                'fechaEmision' => $doc->issue_date->format('d/m/Y'),
                'dirEstablecimiento' => $doc->branch->address,
                'contribuyenteEspecial' => $doc->company->special_taxpayer_number,
                'obligadoContabilidad' => $doc->company->obligated_accounting ? 'SI' : 'NO',
                'tipoIdentificacionSujetoRetenido' => $doc->customer->identification_type->value,
                'razonSocialSujetoRetenido' => $doc->customer->name,
                'identificacionSujetoRetenido' => $doc->customer->identification,
                'periodoFiscal' => $doc->issue_date->format('m/Y'),
            ],
            'docsSustento' => $this->withholdingDetails($doc),
        ];
    }

    public function waybill(ElectronicDocument $doc): array
    {
        $info = $doc->additional_info ?? [];

        return [
            'infoTributaria' => $this->infoTributaria($doc),
            'infoGuiaRemision' => [
                'dirEstablecimiento' => $doc->branch->address,
                'dirPartida' => $info['dirPartida'] ?? '',
                'razonSocialTransportista' => $info['razonSocialTransportista'] ?? '',
                'tipoIdentificacionTransportista' => $info['tipoIdTransportista'] ?? '04',
                'rucTransportista' => $info['rucTransportista'] ?? '',
                'obligadoContabilidad' => $doc->company->obligated_accounting ? 'SI' : 'NO',
                'fechaIniTransporte' => $info['fechaIniTransporte'] ?? '',
                'fechaFinTransporte' => $info['fechaFinTransporte'] ?? '',
                'placa' => $info['placa'] ?? '',
            ],
            'destinatarios' => $info['destinatarios'] ?? [],
        ];
    }

    // --- Helpers privados ---

    private function infoTributaria(ElectronicDocument $doc): array
    {
        $company = $doc->company;

        return [
            'ambiente' => $company->sri_environment,
            'razonSocial' => $company->business_name,
            'nombreComercial' => $company->trade_name ?? $company->business_name,
            'ruc' => $company->ruc,
            'estab' => $doc->branch->code,
            'ptoEmi' => $doc->emissionPoint->code,
            'secuencial' => str_pad($doc->sequential, 9, '0', STR_PAD_LEFT),
            'dirMatriz' => $company->address,
            'contribuyenteEspecial' => $company->special_taxpayer_number,
            'obligadoContabilidad' => $company->obligated_accounting ? 'SI' : 'NO',
            'agenteRetencion' => $company->retention_agent_number,
        ];
    }

    private function taxTotals(ElectronicDocument $doc): array
    {
        $taxes = [];

        if ($doc->subtotal_0 > 0) {
            $taxes[] = [
                'codigo' => '2',
                'codigoPorcentaje' => '0',
                'baseImponible' => $this->fmt($doc->subtotal_0),
                'valor' => '0.00',
            ];
        }

        if ($doc->subtotal_5 > 0) {
            $taxes[] = [
                'codigo' => '2',
                'codigoPorcentaje' => '5',
                'baseImponible' => $this->fmt($doc->subtotal_5),
                'valor' => $this->fmt($doc->subtotal_5 * 0.05),
            ];
        }

        if ($doc->subtotal_12 > 0) {
            $taxes[] = [
                'codigo' => '2',
                'codigoPorcentaje' => '2',
                'baseImponible' => $this->fmt($doc->subtotal_12),
                'valor' => $this->fmt($doc->subtotal_12 * 0.12),
            ];
        }

        if ($doc->subtotal_15 > 0) {
            $taxes[] = [
                'codigo' => '2',
                'codigoPorcentaje' => '4',
                'baseImponible' => $this->fmt($doc->subtotal_15),
                'valor' => $this->fmt($doc->subtotal_15 * 0.15),
            ];
        }

        if ($doc->subtotal_no_tax > 0) {
            $taxes[] = [
                'codigo' => '2',
                'codigoPorcentaje' => '6',
                'baseImponible' => $this->fmt($doc->subtotal_no_tax),
                'valor' => '0.00',
            ];
        }

        return $taxes;
    }

    private function payments(ElectronicDocument $doc): array
    {
        $methods = $doc->payment_methods ?? [];

        if (empty($methods)) {
            return [['formaPago' => '01', 'total' => $this->fmt($doc->total)]];
        }

        return array_map(fn($p) => [
            'formaPago' => $p['code'],
            'total' => $this->fmt($p['amount']),
            'plazo' => $p['term'] ?? '',
            'unidadTiempo' => $p['time_unit'] ?? '',
        ], $methods);
    }

    private function items(ElectronicDocument $doc): array
    {
        return $doc->items->map(fn($item) => [
            'codigoPrincipal' => $item->main_code,
            'codigoAuxiliar' => $item->aux_code ?? '',
            'descripcion' => $item->description,
            'cantidad' => number_format($item->quantity, 6, '.', ''),
            'precioUnitario' => number_format($item->unit_price, 6, '.', ''),
            'descuento' => $this->fmt($item->discount),
            'precioTotalSinImpuesto' => $this->fmt($item->subtotal),
            'impuestos' => [[
                'codigo' => $item->tax_code,
                'codigoPorcentaje' => $item->tax_percentage_code,
                'tarifa' => $this->fmt($item->tax_rate),
                'baseImponible' => $this->fmt($item->tax_base),
                'valor' => $this->fmt($item->tax_value),
            ]],
        ])->toArray();
    }

    private function additionalInfo(ElectronicDocument $doc): array
    {
        $info = [];

        if ($doc->customer?->email) {
            $info[] = ['nombre' => 'email', 'valor' => $doc->customer->email];
        }
        if ($doc->customer?->phone) {
            $info[] = ['nombre' => 'teléfono', 'valor' => $doc->customer->phone];
        }
        if ($doc->customer?->address) {
            $info[] = ['nombre' => 'dirección', 'valor' => $doc->customer->address];
        }

        return $info;
    }

    private function withholdingDetails(ElectronicDocument $doc): array
    {
        return $doc->withholdingDetails->groupBy('support_doc_number')->map(function ($group) {
            $first = $group->first();

            return [
                'codSustento' => $first->support_reason_code ?? '01',
                'codDocSustento' => $first->support_doc_code,
                'numDocSustento' => $first->support_doc_number,
                'fechaEmisionDocSustento' => $first->support_doc_date->format('d/m/Y'),
                'totalSinImpuestos' => $this->fmt($first->support_doc_total ?? 0),
                'importeTotal' => $this->fmt($first->support_doc_total ?? 0),
                'retenciones' => $group->map(fn($r) => [
                    'codigo' => $r->tax_type === 'renta' ? '1' : '2',
                    'codigoRetencion' => $r->retention_code,
                    'baseImponible' => $this->fmt($r->tax_base),
                    'porcentajeRetener' => $this->fmt($r->retention_rate),
                    'valorRetenido' => $this->fmt($r->retained_value),
                ])->toArray(),
            ];
        })->values()->toArray();
    }

    private function fmt(float $value): string
    {
        return number_format($value, 2, '.', '');
    }
}
