<?php

namespace App\Services\SRI;

use App\Models\SRI\ElectronicDocument;
use App\Enums\DocumentType;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Picqer\Barcode\BarcodeGeneratorPNG;

class RIDEGenerator
{
    /**
     * Versión del diseño de las plantillas RIDE. Súbela al cambiar el diseño
     * para invalidar las vistas previas cacheadas de documentos no editados.
     */
    private const TEMPLATE_VERSION = 7;

    /**
     * Generar RIDE (PDF) para un documento.
     *
     * En modo $preview (documentos sin autorizar) el PDF lleva marca de agua
     * "BORRADOR" y se guarda en una ruta aparte que se regenera siempre,
     * porque el borrador puede seguir cambiando.
     */
    public function generate(ElectronicDocument $doc, array $sriResult = [], bool $preview = false): string
    {
        $dir = "tenants/{$doc->tenant_id}/documents/{$doc->id}";

        // La vista previa se cachea por versión del documento + versión del
        // diseño: se regenera si cambia cualquiera de las dos.
        if ($preview) {
            $path = "{$dir}/ride-preview-v".self::TEMPLATE_VERSION."-{$doc->updated_at->getTimestamp()}.pdf";
            if (Storage::exists($path)) {
                return $path;
            }
        } else {
            $path = "{$dir}/ride.pdf";
        }

        $template = $this->getTemplate($doc->document_type);
        $data = $this->prepareData($doc, $sriResult);
        $data['preview'] = $preview;

        $pdf = Pdf::loadView($template, $data);
        $pdf->setPaper('a4', 'portrait');

        // Guardar en el disco por defecto (local en dev, s3 en producción)
        Storage::put($path, $pdf->output());

        if ($preview) {
            $this->purgeStalePreviews($dir, $path);
        }

        return $path;
    }

    /** Elimina vistas previas de versiones anteriores del documento. */
    private function purgeStalePreviews(string $dir, string $currentPath): void
    {
        foreach (Storage::files($dir) as $file) {
            if ($file !== $currentPath && str_contains(basename($file), 'ride-preview')) {
                Storage::delete($file);
            }
        }
    }

    /**
     * Código de barras Code 128 de la clave de acceso, como exige el formato
     * oficial del RIDE. Null si el documento aún no tiene clave.
     */
    public function generateBarcode(ElectronicDocument $doc): ?string
    {
        if (empty($doc->access_key)) {
            return null;
        }

        $generator = new BarcodeGeneratorPNG();
        $png = $generator->getBarcode($doc->access_key, BarcodeGeneratorPNG::TYPE_CODE_128, 2, 46);

        return 'data:image/png;base64,'.base64_encode($png);
    }

    /**
     * Obtener template según tipo de documento
     */
    private function getTemplate(DocumentType $type): string
    {
        return match ($type) {
            DocumentType::FACTURA => 'pdf.ride.factura',
            DocumentType::NOTA_CREDITO => 'pdf.ride.nota-credito',
            DocumentType::NOTA_DEBITO => 'pdf.ride.nota-debito',
            DocumentType::RETENCION => 'pdf.ride.retencion',
            DocumentType::GUIA_REMISION => 'pdf.ride.guia-remision',
            DocumentType::LIQUIDACION_COMPRA => 'pdf.ride.liquidacion',
            default => 'pdf.ride.factura',
        };
    }

    /**
     * Preparar datos para la vista
     */
    private function prepareData(ElectronicDocument $doc, array $sriResult): array
    {
        return [
            'document' => $doc,
            'company' => $doc->company,
            'branch' => $doc->branch,
            'emissionPoint' => $doc->emissionPoint,
            'customer' => $doc->customer,
            'items' => $doc->items,
            'accessKey' => $doc->access_key,
            'authorizationNumber' => $doc->authorization_number,
            'authorizationDate' => $doc->authorization_date,
            'barcode' => $this->generateBarcode($doc),
            'documentNumber' => $doc->getDocumentNumber(),
            'sriResult' => $sriResult,
        ];
    }

    /**
     * Generar RIDE en formato térmico (58mm o 80mm)
     */
    public function generateThermal(ElectronicDocument $doc, string $size = '80mm'): string
    {
        $template = 'pdf.ride.thermal-' . $size;
        $data = $this->prepareData($doc, []);

        $width = $size === '58mm' ? 164 : 226; // puntos

        $pdf = Pdf::loadView($template, $data);
        $pdf->setPaper([0, 0, $width, 1000], 'portrait');

        $content = $pdf->output();

        $path = "tenants/{$doc->tenant_id}/documents/{$doc->id}/ride-thermal.pdf";
        Storage::put($path, $content);

        return $path;
    }
}
