<?php

namespace App\Services\SRI;

use App\Enums\DocumentType;
use App\Enums\IdentificationType;
use App\Models\SRI\ElectronicDocument;

/**
 * Valida un comprobante contra las reglas de negocio del SRI ANTES de enviarlo
 * al webservice. Así evitamos gastar llamadas de recepción/autorización y que
 * el SRI devuelva el comprobante (DEVUELTA / NO AUTORIZADO) por errores que
 * podemos detectar localmente.
 *
 * Devuelve una lista de mensajes legibles (vacía = el documento pasa las
 * validaciones locales). NO reemplaza la validación del SRI, solo atrapa los
 * errores más comunes por adelantado.
 */
class SriPreValidator
{
    /** Identificación de consumidor final del SRI. */
    private const CONSUMIDOR_FINAL = '9999999999999';

    /** Tope en USD para emitir a Consumidor Final (regla SRI, mensaje id 69). */
    private const TOPE_CONSUMIDOR_FINAL = 50.0;

    /**
     * @return array<int, string> errores locales (vacío = OK)
     */
    public function validate(ElectronicDocument $doc): array
    {
        $errors = [];

        $customer = $doc->customer;
        if (! $customer) {
            // Guía de remisión no siempre lleva "customer"; el resto sí.
            if ($doc->document_type !== DocumentType::GUIA_REMISION) {
                $errors[] = 'El documento no tiene un cliente/receptor asignado.';
            }

            return $errors;
        }

        $idType = $customer->identification_type;
        $id = trim((string) $customer->identification);
        $total = (float) $doc->total;

        // 1. Razón social del receptor.
        if (blank($customer->name)) {
            $errors[] = 'El cliente no tiene razón social / nombre.';
        }

        // 2. Identificación válida según el tipo.
        foreach ($this->validateIdentification($idType, $id) as $e) {
            $errors[] = $e;
        }

        // 3. Regla del SRI: una factura mayor a $50 no puede ir a Consumidor Final.
        $esConsumidorFinal = $idType === IdentificationType::CONSUMIDOR_FINAL
            || $id === self::CONSUMIDOR_FINAL;
        if ($doc->document_type === DocumentType::FACTURA
            && $esConsumidorFinal
            && $total > self::TOPE_CONSUMIDOR_FINAL) {
            $errors[] = sprintf(
                'Una factura mayor a $%s no puede emitirse a Consumidor Final. '
                .'Asigná un cliente identificado (cédula o RUC).',
                number_format(self::TOPE_CONSUMIDOR_FINAL, 0)
            );
        }

        // 4. Detalle y totales para comprobantes que los requieren.
        $requiereItems = in_array($doc->document_type, [
            DocumentType::FACTURA,
            DocumentType::LIQUIDACION_COMPRA,
            DocumentType::NOTA_CREDITO,
            DocumentType::NOTA_DEBITO,
        ], true);

        if ($requiereItems) {
            if ($doc->items()->count() === 0) {
                $errors[] = 'El documento no tiene líneas de detalle (productos/servicios).';
            }
            if ($total <= 0) {
                $errors[] = 'El total del documento debe ser mayor a $0.';
            }
        }

        return array_values(array_unique($errors));
    }

    /**
     * Valida la identificación según su tipo (con dígito verificador para
     * cédula y RUC ecuatorianos).
     *
     * @return array<int, string>
     */
    private function validateIdentification(?IdentificationType $type, string $id): array
    {
        if ($type === null) {
            return ['El cliente no tiene tipo de identificación.'];
        }

        if ($id === '') {
            return ['El cliente no tiene número de identificación.'];
        }

        return match ($type) {
            IdentificationType::CONSUMIDOR_FINAL => $id === self::CONSUMIDOR_FINAL
                ? []
                : ['La identificación de Consumidor Final debe ser '.self::CONSUMIDOR_FINAL.'.'],

            IdentificationType::CEDULA => $this->validateCedula($id)
                ? []
                : ['La cédula del cliente no es válida (dígito verificador incorrecto).'],

            IdentificationType::RUC => $this->validateRuc($id)
                ? []
                : ['El RUC del cliente no es válido (debe tener 13 dígitos, terminar en 001 y pasar el dígito verificador).'],

            IdentificationType::PASAPORTE,
            IdentificationType::EXTERIOR => preg_match('/^[A-Za-z0-9]{3,20}$/', $id) === 1
                ? []
                : ['La identificación del exterior/pasaporte no tiene un formato válido.'],
        };
    }

    /**
     * Dígito verificador de cédula ecuatoriana (módulo 10).
     */
    private function validateCedula(string $cedula): bool
    {
        if (preg_match('/^[0-9]{10}$/', $cedula) !== 1) {
            return false;
        }

        $provincia = (int) substr($cedula, 0, 2);
        // Provincias 01-24, más 30 (extranjeros con cédula).
        if (($provincia < 1 || $provincia > 24) && $provincia !== 30) {
            return false;
        }

        $coeficientes = [2, 1, 2, 1, 2, 1, 2, 1, 2];
        $suma = 0;
        for ($i = 0; $i < 9; $i++) {
            $valor = (int) $cedula[$i] * $coeficientes[$i];
            $suma += $valor >= 10 ? $valor - 9 : $valor;
        }

        $residuo = $suma % 10;
        $verificador = $residuo === 0 ? 0 : 10 - $residuo;

        return $verificador === (int) $cedula[9];
    }

    /**
     * Valida un RUC ecuatoriano. Persona natural: cédula válida + "001".
     * Sociedades privadas (3er dígito 9) y públicas (3er dígito 6) usan otros
     * módulos; para esos casos validamos solo estructura para no rechazar RUCs
     * legítimos por diferencias de algoritmo.
     */
    private function validateRuc(string $ruc): bool
    {
        if (preg_match('/^[0-9]{13}$/', $ruc) !== 1) {
            return false;
        }

        if (substr($ruc, -3) !== '001') {
            return false;
        }

        $tercerDigito = (int) $ruc[2];

        // Persona natural (0-5): el núcleo debe ser una cédula válida.
        if ($tercerDigito <= 5) {
            return $this->validateCedula(substr($ruc, 0, 10));
        }

        // Pública (6) o privada (9): estructura válida (evita falsos rechazos).
        return in_array($tercerDigito, [6, 9], true);
    }
}
