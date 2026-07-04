<?php

namespace App\Services\SRI;

use App\Exceptions\CertificateExpiredException;
use App\Exceptions\CertificateNotFoundException;
use App\Exceptions\InvalidCertificateException;
use App\Models\Tenant\Company;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;
use Teran\Sri\Exceptions\CertificateException;
use Teran\Sri\Signing\CertificateLoader;

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

        // Obtener contenido del .p12 desde el disco por defecto
        $content = Storage::get($company->signature_path);

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
        // Muchos certificados emitidos por entidades de certificación ecuatorianas
        // usan cifrados PKCS12 "legacy" (RC2-40, 3DES) que openssl_pkcs12_read()
        // no puede leer en OpenSSL 3.x sin el provider legacy cargado. El
        // CertificateLoader del paquete SRI intenta el lector nativo y, si
        // falla, recurre a un fallback seguro por CLI de openssl con -legacy.
        try {
            $certInfo = (new CertificateLoader())->load($p12Content, $password)->x509Info();
        } catch (CertificateException) {
            throw new InvalidCertificateException('No se pudo leer el certificado. Verifique la contrasena.');
        }

        if (!$certInfo) {
            throw new InvalidCertificateException('No se pudo obtener informacion del certificado');
        }

        // Verificar fecha de expiración
        $validTo = $certInfo['validTo_time_t'] ?? 0;
        $validFrom = $certInfo['validFrom_time_t'] ?? 0;
        $expiresAt = \Carbon\Carbon::createFromTimestamp($validTo);
        $validFromAt = $validFrom ? \Carbon\Carbon::createFromTimestamp($validFrom) : null;

        if ($expiresAt->isPast()) {
            throw new CertificateExpiredException(null, $expiresAt);
        }

        return [
            'subject' => $certInfo['subject']['CN'] ?? $certInfo['subject']['O'] ?? 'Desconocido',
            'issuer' => $certInfo['issuer']['O'] ?? $certInfo['issuer']['CN'] ?? 'Desconocido',
            'identification' => $certInfo['subject']['serialNumber']
                ?? $certInfo['subject']['UID']
                ?? null,
            'expires_at' => $expiresAt,
            'valid_from' => $validFromAt,
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

        // Guardar con path seguro en el disco por defecto
        $path = "tenants/{$company->tenant_id}/signatures/{$company->id}/{$company->ruc}.p12";
        Storage::put($path, $content);

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
