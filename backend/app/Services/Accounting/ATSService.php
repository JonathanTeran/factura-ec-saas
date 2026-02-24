<?php

namespace App\Services\Accounting;

use App\Models\SRI\ElectronicDocument;
use App\Models\Tenant\Company;
use App\Models\Tenant\Purchase;
use Illuminate\Support\Collection;

class ATSService
{
    private Company $company;

    public function forCompany(Company $company): self
    {
        $this->company = $company;
        return $this;
    }

    /**
     * Generar datos completos del ATS
     */
    public function generate(int $year, int $month): array
    {
        $from = "{$year}-{$month}-01";
        $to = date('Y-m-t', strtotime($from));

        return [
            'year' => $year,
            'month' => str_pad($month, 2, '0', STR_PAD_LEFT),
            'company' => [
                'ruc' => $this->company->ruc,
                'business_name' => $this->company->business_name,
                'obligado_contabilidad' => 'SI',
            ],
            'ventas' => $this->getVentas($from, $to),
            'compras' => $this->getCompras($from, $to),
            'retenciones' => $this->getRetenciones($from, $to),
            'anulados' => $this->getAnulados($from, $to),
        ];
    }

    /**
     * Generar XML del ATS según XSD del SRI
     */
    public function generateXml(int $year, int $month): string
    {
        $data = $this->generate($year, $month);

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><iva/>');
        $xml->addChild('TipoIDInformante', 'R');
        $xml->addChild('IdInformante', $data['company']['ruc']);
        $xml->addChild('razonSocial', $data['company']['business_name']);
        $xml->addChild('Anio', $data['year']);
        $xml->addChild('Mes', $data['month']);
        $xml->addChild('numEstabRuc', str_pad($this->company->branches()->count(), 3, '0', STR_PAD_LEFT));
        $xml->addChild('totalVentas', collect($data['ventas'])->sum('baseImponible'));
        $xml->addChild('codigoOperativo', 'IVA');

        // Compras
        if (!empty($data['compras'])) {
            $compras = $xml->addChild('compras');
            foreach ($data['compras'] as $compra) {
                $detalle = $compras->addChild('detalleCompras');
                foreach ($compra as $key => $value) {
                    $detalle->addChild($key, htmlspecialchars((string) $value));
                }
            }
        }

        // Ventas
        if (!empty($data['ventas'])) {
            $ventas = $xml->addChild('ventas');
            foreach ($data['ventas'] as $venta) {
                $detalle = $ventas->addChild('detalleVentas');
                foreach ($venta as $key => $value) {
                    if (!is_array($value)) {
                        $detalle->addChild($key, htmlspecialchars((string) $value));
                    }
                }
            }
        }

        // Anulados
        if (!empty($data['anulados'])) {
            $anulados = $xml->addChild('anulados');
            foreach ($data['anulados'] as $anulado) {
                $detalle = $anulados->addChild('detalleAnulados');
                foreach ($anulado as $key => $value) {
                    $detalle->addChild($key, htmlspecialchars((string) $value));
                }
            }
        }

        return $xml->asXML();
    }

    private function getVentas(string $from, string $to): array
    {
        $documents = ElectronicDocument::where('company_id', $this->company->id)
            ->whereIn('document_type', ['01', '04', '05', '06'])
            ->where('status', 'authorized')
            ->whereBetween('issue_date', [$from, $to])
            ->with('customer')
            ->get();

        return $documents->map(function ($doc) {
            return [
                'tpIdCliente' => $this->mapIdentificationType($doc->customer?->identification_type),
                'idCliente' => $doc->customer?->identification ?? '9999999999999',
                'parteRelVtas' => 'NO',
                'tipoComprobante' => $doc->document_type->value ?? '01',
                'tipoEmision' => 'E',
                'numeroComprobantes' => 1,
                'baseNoGraIva' => (float) ($doc->subtotal_no_tax ?? 0),
                'baseImponible' => (float) ($doc->subtotal_0 ?? 0),
                'baseImpGrav' => (float) (($doc->subtotal_12 ?? 0) + ($doc->subtotal_15 ?? 0)),
                'montoIva' => (float) ($doc->total_tax ?? 0),
                'montoIce' => 0,
                'valorRetIva' => 0,
                'valorRetRenta' => 0,
            ];
        })->toArray();
    }

    private function getCompras(string $from, string $to): array
    {
        $purchases = Purchase::where('company_id', $this->company->id)
            ->where('status', '!=', 'voided')
            ->whereBetween('issue_date', [$from, $to])
            ->with('supplier')
            ->get();

        return $purchases->map(function ($purchase) {
            return [
                'codSustento' => '01',
                'tpIdProv' => $this->mapIdentificationType($purchase->supplier?->identification_type),
                'idProv' => $purchase->supplier?->identification ?? '',
                'tipoComprobante' => $purchase->document_type ?? '01',
                'parteRel' => 'NO',
                'fechaRegistro' => $purchase->issue_date->format('d/m/Y'),
                'establecimiento' => substr($purchase->supplier_document_number ?? '000', 0, 3),
                'puntoEmision' => substr($purchase->supplier_document_number ?? '000-000', 4, 3),
                'secuencial' => substr($purchase->supplier_document_number ?? '', 8),
                'fechaEmision' => $purchase->issue_date->format('d/m/Y'),
                'autorizacion' => $purchase->supplier_authorization ?? '',
                'baseNoGraIva' => (float) ($purchase->subtotal_no_tax ?? 0),
                'baseImponible' => (float) ($purchase->subtotal_0 ?? 0),
                'baseImpGrav' => (float) (($purchase->subtotal_12 ?? 0) + ($purchase->subtotal_15 ?? 0) + ($purchase->subtotal_5 ?? 0)),
                'baseImpExe' => 0,
                'montoIce' => 0,
                'montoIva' => (float) ($purchase->total_tax ?? 0),
                'valRetBien10' => 0,
                'valRetServ20' => 0,
                'valorRetBienes' => 0,
                'valRetServ50' => 0,
                'valorRetServicios' => 0,
                'valRetServ100' => 0,
            ];
        })->toArray();
    }

    private function getRetenciones(string $from, string $to): array
    {
        return ElectronicDocument::where('company_id', $this->company->id)
            ->where('document_type', '07')
            ->where('status', 'authorized')
            ->whereBetween('issue_date', [$from, $to])
            ->get()
            ->map(function ($doc) {
                return [
                    'estabRetencion1' => substr($doc->access_key ?? '', 24, 3),
                    'ptoEmiRetencion1' => substr($doc->access_key ?? '', 27, 3),
                    'secRetencion1' => substr($doc->access_key ?? '', 30, 9),
                    'autRetencion1' => $doc->authorization_number ?? '',
                    'fechaEmiRet1' => $doc->issue_date?->format('d/m/Y') ?? '',
                ];
            })->toArray();
    }

    private function getAnulados(string $from, string $to): array
    {
        return ElectronicDocument::where('company_id', $this->company->id)
            ->where('status', 'voided')
            ->whereBetween('issue_date', [$from, $to])
            ->get()
            ->map(function ($doc) {
                return [
                    'tipoComprobante' => $doc->document_type->value ?? '01',
                    'establecimiento' => substr($doc->access_key ?? '', 24, 3),
                    'puntoEmision' => substr($doc->access_key ?? '', 27, 3),
                    'secuencialInicio' => substr($doc->access_key ?? '', 30, 9),
                    'secuencialFin' => substr($doc->access_key ?? '', 30, 9),
                    'autorizacion' => $doc->authorization_number ?? '',
                ];
            })->toArray();
    }

    private function mapIdentificationType(?object $type): string
    {
        $value = $type?->value ?? $type ?? '07';

        return match ($value) {
            'ruc', 'RUC', '04' => '04',
            'cedula', 'CEDULA', '05' => '05',
            'pasaporte', 'PASAPORTE', '06' => '06',
            default => '07',
        };
    }
}
