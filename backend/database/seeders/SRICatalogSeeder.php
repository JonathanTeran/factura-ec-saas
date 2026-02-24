<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SRICatalogSeeder extends Seeder
{
    public function run(): void
    {
        // Tipos de identificación
        $this->seedCatalog('identification_type', [
            ['code' => '04', 'name' => 'RUC', 'description' => 'Registro Único de Contribuyentes'],
            ['code' => '05', 'name' => 'Cédula', 'description' => 'Cédula de Identidad'],
            ['code' => '06', 'name' => 'Pasaporte', 'description' => 'Pasaporte'],
            ['code' => '07', 'name' => 'Consumidor Final', 'description' => 'Venta a Consumidor Final'],
            ['code' => '08', 'name' => 'Identificación del Exterior', 'description' => 'Identificación del Exterior'],
        ]);

        // Tipos de comprobante
        $this->seedCatalog('document_type', [
            ['code' => '01', 'name' => 'Factura', 'description' => 'Factura'],
            ['code' => '03', 'name' => 'Liquidación de Compra', 'description' => 'Liquidación de Compra de Bienes y Prestación de Servicios'],
            ['code' => '04', 'name' => 'Nota de Crédito', 'description' => 'Nota de Crédito'],
            ['code' => '05', 'name' => 'Nota de Débito', 'description' => 'Nota de Débito'],
            ['code' => '06', 'name' => 'Guía de Remisión', 'description' => 'Guía de Remisión'],
            ['code' => '07', 'name' => 'Comprobante de Retención', 'description' => 'Comprobante de Retención'],
        ]);

        // Formas de pago
        $this->seedCatalog('payment_method', [
            ['code' => '01', 'name' => 'Sin utilización del sistema financiero', 'description' => 'Efectivo'],
            ['code' => '15', 'name' => 'Compensación de deudas', 'description' => 'Compensación de deudas'],
            ['code' => '16', 'name' => 'Tarjeta de débito', 'description' => 'Tarjeta de débito'],
            ['code' => '17', 'name' => 'Dinero electrónico', 'description' => 'Dinero electrónico'],
            ['code' => '18', 'name' => 'Tarjeta prepago', 'description' => 'Tarjeta prepago'],
            ['code' => '19', 'name' => 'Tarjeta de crédito', 'description' => 'Tarjeta de crédito'],
            ['code' => '20', 'name' => 'Otros con utilización del sistema financiero', 'description' => 'Transferencia, cheque, etc.'],
            ['code' => '21', 'name' => 'Endoso de títulos', 'description' => 'Endoso de títulos'],
        ]);

        // Tarifas de IVA
        $this->seedCatalog('tax_rate', [
            ['code' => '0', 'name' => '0%', 'description' => 'Tarifa 0% de IVA', 'percentage' => 0.00],
            ['code' => '5', 'name' => '5%', 'description' => 'Tarifa 5% de IVA', 'percentage' => 5.00],
            ['code' => '2', 'name' => '12%', 'description' => 'Tarifa 12% de IVA', 'percentage' => 12.00],
            ['code' => '3', 'name' => '14%', 'description' => 'Tarifa 14% de IVA', 'percentage' => 14.00],
            ['code' => '4', 'name' => '15%', 'description' => 'Tarifa 15% de IVA (vigente)', 'percentage' => 15.00],
            ['code' => '6', 'name' => 'No Objeto de Impuesto', 'description' => 'No Objeto de Impuesto', 'percentage' => 0.00],
            ['code' => '7', 'name' => 'Exento de IVA', 'description' => 'Exento de IVA', 'percentage' => 0.00],
        ]);

        // Códigos de retención IR
        $this->seedCatalog('retention_ir', [
            ['code' => '303', 'name' => 'Honorarios profesionales', 'percentage' => 10.00],
            ['code' => '304', 'name' => 'Servicios predomina intelecto', 'percentage' => 8.00],
            ['code' => '307', 'name' => 'Servicios predomina mano de obra', 'percentage' => 2.00],
            ['code' => '308', 'name' => 'Servicios entre sociedades', 'percentage' => 2.00],
            ['code' => '309', 'name' => 'Servicios publicidad y comunicación', 'percentage' => 1.75],
            ['code' => '310', 'name' => 'Transporte privado o público', 'percentage' => 1.00],
            ['code' => '312', 'name' => 'Transferencia de bienes muebles', 'percentage' => 1.75],
            ['code' => '319', 'name' => 'Arrendamiento bienes inmuebles', 'percentage' => 8.00],
            ['code' => '320', 'name' => 'Arrendamiento bienes muebles', 'percentage' => 8.00],
            ['code' => '322', 'name' => 'Seguros y reaseguros', 'percentage' => 1.75],
            ['code' => '323', 'name' => 'Rendimientos financieros', 'percentage' => 2.00],
            ['code' => '332', 'name' => 'Pagos de bienes o servicios no sujetos', 'percentage' => 0.00],
            ['code' => '340', 'name' => 'Otras retenciones 1%', 'percentage' => 1.00],
            ['code' => '341', 'name' => 'Otras retenciones 2%', 'percentage' => 2.00],
            ['code' => '342', 'name' => 'Otras retenciones 8%', 'percentage' => 8.00],
            ['code' => '343', 'name' => 'Otras retenciones 25%', 'percentage' => 25.00],
        ]);

        // Códigos de retención IVA
        $this->seedCatalog('retention_iva', [
            ['code' => '721', 'name' => 'Retención 10% IVA', 'percentage' => 10.00],
            ['code' => '723', 'name' => 'Retención 20% IVA', 'percentage' => 20.00],
            ['code' => '725', 'name' => 'Retención 30% IVA', 'percentage' => 30.00],
            ['code' => '727', 'name' => 'Retención 50% IVA', 'percentage' => 50.00],
            ['code' => '729', 'name' => 'Retención 70% IVA', 'percentage' => 70.00],
            ['code' => '731', 'name' => 'Retención 100% IVA', 'percentage' => 100.00],
        ]);

        // Códigos de sustento
        $this->seedCatalog('sustento_code', [
            ['code' => '01', 'name' => 'Crédito Tributario para declaración de IVA'],
            ['code' => '02', 'name' => 'Costo o Gasto para declaración de IR'],
            ['code' => '03', 'name' => 'Activo Fijo - Crédito Tributario para declaración de IVA'],
            ['code' => '04', 'name' => 'Activo Fijo - Costo o Gasto para declaración de IR'],
            ['code' => '05', 'name' => 'Liquidación Gastos de Viaje, Hospedaje y Alimentación'],
            ['code' => '06', 'name' => 'Inventario - Crédito Tributario para declaración de IVA'],
            ['code' => '07', 'name' => 'Inventario - Costo o Gasto para declaración de IR'],
            ['code' => '08', 'name' => 'Valor pagado para solicitar Reembolso de Gasto'],
            ['code' => '09', 'name' => 'Reembolso por Siniestros'],
            ['code' => '10', 'name' => 'Distribución de Dividendos'],
            ['code' => '00', 'name' => 'Casos especiales cuyo sustento no aplica'],
        ]);
    }

    private function seedCatalog(string $type, array $items): void
    {
        foreach ($items as $item) {
            DB::table('sri_catalogs')->updateOrInsert(
                ['catalog_type' => $type, 'code' => $item['code']],
                array_merge($item, [
                    'catalog_type' => $type,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
