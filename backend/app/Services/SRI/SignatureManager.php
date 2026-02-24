<?php

namespace App\Services\SRI;

use App\Exceptions\CertificateExpiredException;
use App\Exceptions\CertificateNotFoundException;
use App\Exceptions\InvalidCertificateException;
use App\Models\Tenant\Company;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;

class SignatureManager
{
    /**
     * Obtener firma desencriptada de una empresa
     */
    public function decrypt(Company $company): array
    {
        if (!$company->hasValidSignature()) {
            throw new CertificateNotFoundException($company->id);
        }

        // Obtener contenido del .p12 desde S3
        $content = Storage::disk('s3')->get($company->signature_path);

        if (!$content) {
            throw new CertificateNotFoundException($company->id);
        }

        return [
            'content' => $content,
            'password' => $company->getDecryptedSignaturePassword(),
        ];
    }

    /**
     * Validar que la firma es válida
     */
    public function validate(string $p12Content, string $password): array
    {
        $certs = [];

        if (!openssl_pkcs12_read($p12Content, $certs, $password)) {
            throw new InvalidCertificateException('No se pudo leer el certificado. Verifique la contrasena.');
        }

        // Obtener información del certificado
        $certInfo = openssl_x509_parse($certs['cert']);

        if (!$certInfo) {
            throw new InvalidCertificateException('No se pudo obtener informacion del certificado');
        }

        // Verificar fecha de expiración
        $validTo = $certInfo['validTo_time_t'] ?? 0;
        $expiresAt = \Carbon\Carbon::createFromTimestamp($validTo);

        if ($expiresAt->isPast()) {
            throw new CertificateExpiredException(null, $expiresAt);
        }

        return [
            'subject' => $certInfo['subject']['CN'] ?? $certInfo['subject']['O'] ?? 'Desconocido',
            'issuer' => $certInfo['issuer']['O'] ?? 'Desconocido',
            'expires_at' => $expiresAt,
            'serial_number' => $certInfo['serialNumberHex'] ?? null,
        ];
    }

    /**
     * Guardar firma electrónica de forma segura
     */
    public function store(Company $company, $file, string $password): array
    {
        $content = file_get_contents($file->getRealPath());

        // Validar la firma
        $info = $this->validate($content, $password);

        // Guardar en S3 con path seguro
        $path = "tenants/{$company->tenant_id}/signatures/{$company->id}/{$company->ruc}.p12";
        Storage::disk('s3')->put($path, $content);

        // Actualizar empresa
        $company->update([
            'signature_path' => $path,
            'signature_password' => $password, // Se encripta automáticamente con el mutator
            'signature_expires_at' => $info['expires_at'],
            'signature_issuer' => $info['issuer'],
            'signature_subject' => $info['subject'],
        ]);

        return $info;
    }

    /**
     * Verificar estado de la firma
     */
    public function checkStatus(Company $company): array
    {
        if (!$company->signature_path) {
            return [
                'status' => 'missing',
                'message' => 'No hay firma electrónica configurada',
            ];
        }

        if (!$company->signature_expires_at) {
            return [
                'status' => 'unknown',
                'message' => 'No se pudo determinar la fecha de expiración',
            ];
        }

        if ($company->signature_expires_at->isPast()) {
            return [
                'status' => 'expired',
                'message' => 'La firma electrónica ha expirado',
                'expired_at' => $company->signature_expires_at,
            ];
        }

        $daysRemaining = $company->signatureDaysRemaining();

        if ($daysRemaining <= 30) {
            return [
                'status' => 'expiring_soon',
                'message' => "La firma expira en {$daysRemaining} días",
                'days_remaining' => $daysRemaining,
                'expires_at' => $company->signature_expires_at,
            ];
        }

        return [
            'status' => 'valid',
            'message' => 'Firma electrónica válida',
            'days_remaining' => $daysRemaining,
            'expires_at' => $company->signature_expires_at,
        ];
    }
}
