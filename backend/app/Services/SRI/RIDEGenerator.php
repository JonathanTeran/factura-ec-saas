<?php

namespace App\Services\SRI;

use App\Models\SRI\ElectronicDocument;
use App\Enums\DocumentType;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class RIDEGenerator
{
    /**
     * Generar RIDE (PDF) para un documento
     */
    public function generate(ElectronicDocument $doc, array $sriResult = []): string
    {
        $template = $this->getTemplate($doc->document_type);
        $data = $this->prepareData($doc, $sriResult);

        $pdf = Pdf::loadView($template, $data);
        $pdf->setPaper('a4', 'portrait');

        $content = $pdf->output();

        // Guardar en S3
        $path = "tenants/{$doc->tenant_id}/documents/{$doc->id}/ride.pdf";
        Storage::disk('s3')->put($path, $content);

        return $path;
    }

    /**
     * Generar QR Code
     */
    public function generateQRCode(ElectronicDocument $doc): string
    {
        $data = implode('|', [
            $doc->access_key,
            $doc->company->ruc,
            $doc->document_type->value,
            $doc->total,
        ]);

        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel' => QRCode::ECC_M,
            'scale' => 5,
        ]);

        $qrcode = new QRCode($options);

        return $qrcode->render($data);
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
            'qrCode' => $this->generateQRCode($doc),
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
        Storage::disk('s3')->put($path, $content);

        return $path;
    }
}
