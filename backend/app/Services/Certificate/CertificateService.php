<?php

namespace App\Services\Certificate;

use App\Exceptions\CertificateExpiredException;
use App\Exceptions\CertificateNotFoundException;
use App\Exceptions\InvalidCertificateException;
use App\Models\Tenant\Company;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class CertificateService
{
    /**
     * Subir y procesar certificado digital (.p12).
     */
    public function upload(Company $company, UploadedFile $file, string $password): array
    {
        // Validar que sea un certificado válido
        $certInfo = $this->validateCertificate($file->getRealPath(), $password);

        if (!$certInfo['valid']) {
            throw new InvalidCertificateException($certInfo['error'], $company->id);
        }

        // Guardar certificado encriptado
        $path = "tenants/{$company->tenant_id}/certificates/{$company->id}";
        $filename = 'certificate_' . now()->format('Ymd_His') . '.p12';

        // Encriptar el contenido del certificado
        $content = file_get_contents($file->getRealPath());
        $encryptedContent = Crypt::encryptString($content);

        Storage::disk('local')->put("{$path}/{$filename}", $encryptedContent);

        // Encriptar la contraseña
        $encryptedPassword = Crypt::encryptString($password);

        // Actualizar empresa
        $company->update([
            'certificate_path' => "{$path}/{$filename}",
            'certificate_password' => $encryptedPassword,
            'certificate_valid_from' => $certInfo['valid_from'],
            'certificate_valid_until' => $certInfo['valid_until'],
            'certificate_issued_to' => $certInfo['issued_to'],
            'certificate_issued_by' => $certInfo['issued_by'],
            'certificate_serial' => $certInfo['serial_number'],
            'certificate_uploaded_at' => now(),
        ]);

        return [
            'success' => true,
            'info' => $certInfo,
        ];
    }

    /**
     * Validar certificado y extraer información.
     */
    public function validateCertificate(string $path, string $password): array
    {
        $content = file_get_contents($path);

        if (!$content) {
            return ['valid' => false, 'error' => 'No se pudo leer el archivo'];
        }

        // Intentar abrir el PKCS#12
        $certs = [];
        if (!openssl_pkcs12_read($content, $certs, $password)) {
            $error = openssl_error_string();
            return ['valid' => false, 'error' => 'Contraseña incorrecta o certificado inválido: ' . $error];
        }

        // Extraer información del certificado
        $certInfo = openssl_x509_parse($certs['cert']);

        if (!$certInfo) {
            return ['valid' => false, 'error' => 'No se pudo parsear el certificado'];
        }

        // Verificar fechas
        $validFrom = \DateTime::createFromFormat('ymdHise', $certInfo['validFrom']);
        $validUntil = \DateTime::createFromFormat('ymdHise', $certInfo['validTo']);

        if (!$validFrom || !$validUntil) {
            // Intentar formato alternativo
            $validFrom = new \DateTime('@' . $certInfo['validFrom_time_t']);
            $validUntil = new \DateTime('@' . $certInfo['validTo_time_t']);
        }

        // Verificar si está expirado
        if ($validUntil < now()) {
            return [
                'valid' => false,
                'error' => 'El certificado expiró el ' . $validUntil->format('d/m/Y'),
            ];
        }

        // Verificar si aún no es válido
        if ($validFrom > now()) {
            return [
                'valid' => false,
                'error' => 'El certificado no es válido hasta ' . $validFrom->format('d/m/Y'),
            ];
        }

        return [
            'valid' => true,
            'issued_to' => $certInfo['subject']['CN'] ?? 'Desconocido',
            'issued_by' => $certInfo['issuer']['CN'] ?? 'Desconocido',
            'valid_from' => $validFrom->format('Y-m-d H:i:s'),
            'valid_until' => $validUntil->format('Y-m-d H:i:s'),
            'serial_number' => $certInfo['serialNumberHex'] ?? $certInfo['serialNumber'] ?? null,
            'days_remaining' => now()->diffInDays($validUntil, false),
        ];
    }

    /**
     * Obtener certificado desencriptado para firmar.
     */
    public function getCertificate(Company $company): array
    {
        if (!$company->certificate_path) {
            throw new CertificateNotFoundException($company->id);
        }

        // Verificar expiración
        if ($company->certificate_valid_until && $company->certificate_valid_until->isPast()) {
            throw new CertificateExpiredException($company->id, $company->certificate_valid_until);
        }

        // Leer y desencriptar
        $encryptedContent = Storage::disk('local')->get($company->certificate_path);
        $content = Crypt::decryptString($encryptedContent);
        $password = Crypt::decryptString($company->certificate_password);

        return [
            'content' => $content,
            'password' => $password,
        ];
    }

    /**
     * Verificar si el certificado está próximo a expirar.
     */
    public function isExpiringSoon(Company $company, int $days = 30): bool
    {
        if (!$company->certificate_valid_until) {
            return false;
        }

        return $company->certificate_valid_until->diffInDays(now(), false) <= $days;
    }

    /**
     * Obtener empresas con certificados próximos a expirar.
     */
    public function getExpiringCertificates(int $days = 30): \Illuminate\Database\Eloquent\Collection
    {
        return Company::whereNotNull('certificate_valid_until')
            ->where('certificate_valid_until', '<=', now()->addDays($days))
            ->where('certificate_valid_until', '>', now())
            ->get();
    }

    /**
     * Eliminar certificado.
     */
    public function deleteCertificate(Company $company): void
    {
        if ($company->certificate_path) {
            Storage::disk('local')->delete($company->certificate_path);
        }

        $company->update([
            'certificate_path' => null,
            'certificate_password' => null,
            'certificate_valid_from' => null,
            'certificate_valid_until' => null,
            'certificate_issued_to' => null,
            'certificate_issued_by' => null,
            'certificate_serial' => null,
            'certificate_uploaded_at' => null,
        ]);
    }

    /**
     * Renovar certificado (subir nuevo).
     */
    public function renewCertificate(Company $company, UploadedFile $file, string $password): array
    {
        // Guardar backup del anterior
        if ($company->certificate_path) {
            $oldPath = $company->certificate_path;
            $backupPath = str_replace('.p12', '_backup_' . now()->format('Ymd') . '.p12', $oldPath);
            Storage::disk('local')->copy($oldPath, $backupPath);
        }

        // Subir nuevo certificado
        return $this->upload($company, $file, $password);
    }
}
