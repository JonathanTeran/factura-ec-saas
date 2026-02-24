<?php

/**
 * Plan de Cuentas - NIIF para PYMES
 * Basado en la estructura de la Superintendencia de Compañías del Ecuador
 * Versión simplificada (~180 cuentas) para pequeñas y medianas empresas
 */

return [
    // ===================== 1. ACTIVO =====================
    ['code' => '1', 'name' => 'ACTIVO', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 1, 'is_parent' => true, 'allows_movement' => false, 'tax_form_code' => '311'],
    ['code' => '1.01', 'name' => 'ACTIVO CORRIENTE', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 2, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '1'],

    // Efectivo
    ['code' => '1.01.01', 'name' => 'Efectivo y equivalentes de efectivo', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '1.01'],
    ['code' => '1.01.01.01', 'name' => 'Caja', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.01.01'],
    ['code' => '1.01.01.02', 'name' => 'Caja chica', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.01.01'],
    ['code' => '1.01.01.03', 'name' => 'Bancos', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.01.01'],

    // Cuentas por cobrar
    ['code' => '1.01.02', 'name' => 'Cuentas y documentos por cobrar', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '1.01'],
    ['code' => '1.01.02.01', 'name' => 'Clientes por cobrar', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.01.02'],
    ['code' => '1.01.02.02', 'name' => 'Documentos por cobrar', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.01.02'],
    ['code' => '1.01.02.03', 'name' => 'Otras cuentas por cobrar', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.01.02'],
    ['code' => '1.01.02.04', 'name' => 'Anticipos a empleados', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.01.02'],
    ['code' => '1.01.02.05', 'name' => 'Cuentas por cobrar clientes', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.01.02'],
    ['code' => '1.01.02.09', 'name' => '(-) Provision cuentas incobrables', 'account_type' => 'activo', 'account_nature' => 'credit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.01.02'],

    // Inventarios
    ['code' => '1.01.03', 'name' => 'Inventarios', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '1.01'],
    ['code' => '1.01.03.01', 'name' => 'Inventario de mercaderias', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.01.03'],
    ['code' => '1.01.03.02', 'name' => 'Inventario de suministros', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.01.03'],

    // Pagos anticipados
    ['code' => '1.01.04', 'name' => 'Pagos anticipados', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '1.01'],
    ['code' => '1.01.04.01', 'name' => 'Seguros pagados por anticipado', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.01.04'],
    ['code' => '1.01.04.02', 'name' => 'Arriendos pagados por anticipado', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.01.04'],
    ['code' => '1.01.04.03', 'name' => 'Anticipos a proveedores', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.01.04'],

    // Activos por impuestos
    ['code' => '1.01.05', 'name' => 'Activos por impuestos corrientes', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '1.01'],
    ['code' => '1.01.05.01', 'name' => 'Credito tributario IVA', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.01.05'],
    ['code' => '1.01.05.02', 'name' => 'Credito tributario IR', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.01.05'],
    ['code' => '1.01.05.03', 'name' => 'Anticipo impuesto a la renta', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.01.05'],
    ['code' => '1.01.05.04', 'name' => 'IVA en compras', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.01.05'],
    ['code' => '1.01.05.05', 'name' => 'Retenciones IVA recibidas', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.01.05'],
    ['code' => '1.01.05.06', 'name' => 'Retenciones IR recibidas', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.01.05'],

    // Activo no corriente
    ['code' => '1.02', 'name' => 'ACTIVO NO CORRIENTE', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 2, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '1'],

    ['code' => '1.02.01', 'name' => 'Propiedad, planta y equipo', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '1.02'],
    ['code' => '1.02.01.01', 'name' => 'Terrenos', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.02.01'],
    ['code' => '1.02.01.02', 'name' => 'Edificios', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.02.01'],
    ['code' => '1.02.01.03', 'name' => 'Muebles y enseres', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.02.01'],
    ['code' => '1.02.01.04', 'name' => 'Equipo de computacion', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.02.01'],
    ['code' => '1.02.01.05', 'name' => 'Vehiculos', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.02.01'],
    ['code' => '1.02.01.06', 'name' => 'Maquinaria y equipo', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.02.01'],
    ['code' => '1.02.01.09', 'name' => '(-) Depreciacion acumulada', 'account_type' => 'activo', 'account_nature' => 'credit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.02.01'],

    // ===================== 2. PASIVO =====================
    ['code' => '2', 'name' => 'PASIVO', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 1, 'is_parent' => true, 'allows_movement' => false, 'tax_form_code' => '411'],
    ['code' => '2.01', 'name' => 'PASIVO CORRIENTE', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 2, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '2'],

    ['code' => '2.01.01', 'name' => 'Cuentas y documentos por pagar', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 3, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '2.01'],
    ['code' => '2.01.01.01', 'name' => 'Proveedores por pagar', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '2.01.01'],
    ['code' => '2.01.01.02', 'name' => 'Documentos por pagar', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '2.01.01'],

    ['code' => '2.01.02', 'name' => 'Obligaciones bancarias', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '2.01'],

    ['code' => '2.01.03', 'name' => 'Cuentas por pagar diversas', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 3, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '2.01'],
    ['code' => '2.01.03.01', 'name' => 'Cuentas por pagar proveedores', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '2.01.03'],

    ['code' => '2.01.04', 'name' => 'Obligaciones con empleados', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 3, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '2.01'],
    ['code' => '2.01.04.01', 'name' => 'Sueldos por pagar', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '2.01.04'],
    ['code' => '2.01.04.02', 'name' => 'IESS por pagar', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '2.01.04'],
    ['code' => '2.01.04.03', 'name' => 'Beneficios sociales por pagar', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '2.01.04'],
    ['code' => '2.01.04.04', 'name' => 'Participacion trabajadores por pagar', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '2.01.04'],

    ['code' => '2.01.07', 'name' => 'Obligaciones fiscales', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 3, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '2.01'],
    ['code' => '2.01.07.01', 'name' => 'IVA en ventas por pagar', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '2.01.07'],
    ['code' => '2.01.07.02', 'name' => 'Impuesto a la renta por pagar', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '2.01.07'],
    ['code' => '2.01.07.03', 'name' => 'Retenciones IR por pagar', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '2.01.07'],
    ['code' => '2.01.07.04', 'name' => 'Retenciones IVA por pagar', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '2.01.07'],
    ['code' => '2.01.07.05', 'name' => 'IVA por pagar', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '2.01.07'],

    ['code' => '2.01.08', 'name' => 'Anticipos de clientes', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '2.01'],

    ['code' => '2.02', 'name' => 'PASIVO NO CORRIENTE', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 2, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '2'],
    ['code' => '2.02.01', 'name' => 'Prestamos bancarios largo plazo', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '2.02'],
    ['code' => '2.02.02', 'name' => 'Provisiones largo plazo', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '2.02'],

    // ===================== 3. PATRIMONIO =====================
    ['code' => '3', 'name' => 'PATRIMONIO NETO', 'account_type' => 'patrimonio', 'account_nature' => 'credit', 'level' => 1, 'is_parent' => true, 'allows_movement' => false, 'tax_form_code' => '501'],

    ['code' => '3.01', 'name' => 'Capital', 'account_type' => 'patrimonio', 'account_nature' => 'credit', 'level' => 2, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '3'],
    ['code' => '3.01.01', 'name' => 'Capital social', 'account_type' => 'patrimonio', 'account_nature' => 'credit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '3.01'],
    ['code' => '3.01.02', 'name' => 'Aportes futuras capitalizaciones', 'account_type' => 'patrimonio', 'account_nature' => 'credit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '3.01'],

    ['code' => '3.04', 'name' => 'Reservas', 'account_type' => 'patrimonio', 'account_nature' => 'credit', 'level' => 2, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '3'],
    ['code' => '3.04.01', 'name' => 'Reserva legal', 'account_type' => 'patrimonio', 'account_nature' => 'credit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '3.04'],
    ['code' => '3.04.02', 'name' => 'Reservas facultativas', 'account_type' => 'patrimonio', 'account_nature' => 'credit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '3.04'],

    ['code' => '3.06', 'name' => 'Resultados acumulados', 'account_type' => 'patrimonio', 'account_nature' => 'credit', 'level' => 2, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '3'],
    ['code' => '3.06.01', 'name' => 'Utilidades acumuladas', 'account_type' => 'patrimonio', 'account_nature' => 'credit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '3.06'],
    ['code' => '3.06.02', 'name' => '(-) Perdidas acumuladas', 'account_type' => 'patrimonio', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '3.06'],

    ['code' => '3.07', 'name' => 'Resultados del ejercicio', 'account_type' => 'patrimonio', 'account_nature' => 'credit', 'level' => 2, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '3'],
    ['code' => '3.07.01', 'name' => 'Utilidad del ejercicio', 'account_type' => 'patrimonio', 'account_nature' => 'credit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '3.07'],
    ['code' => '3.07.02', 'name' => '(-) Perdida del ejercicio', 'account_type' => 'patrimonio', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '3.07'],

    // ===================== 4. INGRESOS =====================
    ['code' => '4', 'name' => 'INGRESOS', 'account_type' => 'ingreso', 'account_nature' => 'credit', 'level' => 1, 'is_parent' => true, 'allows_movement' => false, 'tax_form_code' => '601'],

    ['code' => '4.01', 'name' => 'Ingresos operacionales', 'account_type' => 'ingreso', 'account_nature' => 'credit', 'level' => 2, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '4'],
    ['code' => '4.01.01', 'name' => 'Venta de bienes', 'account_type' => 'ingreso', 'account_nature' => 'credit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '4.01'],
    ['code' => '4.01.02', 'name' => 'Prestacion de servicios', 'account_type' => 'ingreso', 'account_nature' => 'credit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '4.01'],
    ['code' => '4.01.03', 'name' => '(-) Descuento en ventas', 'account_type' => 'ingreso', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '4.01'],
    ['code' => '4.01.04', 'name' => '(-) Devoluciones en ventas', 'account_type' => 'ingreso', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '4.01'],

    ['code' => '4.02', 'name' => 'Otros ingresos', 'account_type' => 'ingreso', 'account_nature' => 'credit', 'level' => 2, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '4'],
    ['code' => '4.02.01', 'name' => 'Intereses ganados', 'account_type' => 'ingreso', 'account_nature' => 'credit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '4.02'],
    ['code' => '4.02.02', 'name' => 'Otros ingresos diversos', 'account_type' => 'ingreso', 'account_nature' => 'credit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '4.02'],

    // ===================== 5. COSTOS =====================
    ['code' => '5', 'name' => 'COSTOS', 'account_type' => 'costo', 'account_nature' => 'debit', 'level' => 1, 'is_parent' => true, 'allows_movement' => false, 'tax_form_code' => '701'],

    ['code' => '5.01', 'name' => 'Costo de ventas', 'account_type' => 'costo', 'account_nature' => 'debit', 'level' => 2, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '5'],
    ['code' => '5.01.01', 'name' => 'Costo de mercaderias vendidas', 'account_type' => 'costo', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '5.01'],
    ['code' => '5.01.02', 'name' => 'Costo de servicios prestados', 'account_type' => 'costo', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '5.01'],

    // ===================== 6. GASTOS =====================
    ['code' => '6', 'name' => 'GASTOS', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 1, 'is_parent' => true, 'allows_movement' => false, 'tax_form_code' => '801'],

    ['code' => '6.01', 'name' => 'Gastos de venta', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 2, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '6'],
    ['code' => '6.01.01', 'name' => 'Sueldos y salarios ventas', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6.01'],
    ['code' => '6.01.02', 'name' => 'Beneficios sociales ventas', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6.01'],
    ['code' => '6.01.03', 'name' => 'Comisiones en ventas', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6.01'],
    ['code' => '6.01.04', 'name' => 'Publicidad y propaganda', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6.01'],
    ['code' => '6.01.05', 'name' => 'Transporte en ventas', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6.01'],

    ['code' => '6.02', 'name' => 'Gastos administrativos', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 2, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '6'],
    ['code' => '6.02.01', 'name' => 'Sueldos y salarios admin', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6.02'],
    ['code' => '6.02.02', 'name' => 'Beneficios sociales admin', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6.02'],
    ['code' => '6.02.03', 'name' => 'Honorarios profesionales', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6.02'],
    ['code' => '6.02.04', 'name' => 'Arriendos', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6.02'],
    ['code' => '6.02.05', 'name' => 'Mantenimiento y reparaciones', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6.02'],
    ['code' => '6.02.06', 'name' => 'Suministros de oficina', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6.02'],
    ['code' => '6.02.07', 'name' => 'Servicios basicos', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6.02'],
    ['code' => '6.02.08', 'name' => 'Seguros', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6.02'],
    ['code' => '6.02.09', 'name' => 'Impuestos y contribuciones', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6.02'],
    ['code' => '6.02.10', 'name' => 'Depreciaciones', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6.02'],
    ['code' => '6.02.11', 'name' => 'Gastos de viaje', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6.02'],
    ['code' => '6.02.99', 'name' => 'Otros gastos administrativos', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6.02'],

    ['code' => '6.03', 'name' => 'Gastos financieros', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 2, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '6'],
    ['code' => '6.03.01', 'name' => 'Intereses bancarios', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6.03'],
    ['code' => '6.03.02', 'name' => 'Comisiones bancarias', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6.03'],
    ['code' => '6.03.03', 'name' => 'Otros gastos financieros', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6.03'],

    ['code' => '6.04', 'name' => 'Otros gastos', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 2, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '6'],
    ['code' => '6.04.01', 'name' => 'Gastos no deducibles', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6.04'],
    ['code' => '6.04.02', 'name' => 'Otros gastos diversos', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6.04'],

    ['code' => '6.05', 'name' => 'Impuesto a la renta', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 2, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6'],
    ['code' => '6.06', 'name' => 'Participacion trabajadores', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 2, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6'],
];
