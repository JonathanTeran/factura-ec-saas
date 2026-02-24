<?php

/**
 * Plan de Cuentas - NIIF Completas
 * Basado en la estructura de la Superintendencia de Compañías del Ecuador
 * ~250 cuentas para empresas obligadas a llevar NIIF completas
 */

return [
    // ===================== 1. ACTIVO =====================
    ['code' => '1', 'name' => 'ACTIVO', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 1, 'is_parent' => true, 'allows_movement' => false, 'tax_form_code' => '311'],
    ['code' => '1.01', 'name' => 'ACTIVO CORRIENTE', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 2, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '1', 'tax_form_code' => '312'],

    // Efectivo y equivalentes
    ['code' => '1.01.01', 'name' => 'Efectivo y equivalentes de efectivo', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '1.01', 'tax_form_code' => '313'],
    ['code' => '1.01.01.01', 'name' => 'Caja', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.01.01'],
    ['code' => '1.01.01.02', 'name' => 'Caja chica', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.01.01'],
    ['code' => '1.01.01.03', 'name' => 'Bancos', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.01.01'],
    ['code' => '1.01.01.04', 'name' => 'Inversiones temporales', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.01.01'],

    // Activos financieros
    ['code' => '1.01.02', 'name' => 'Activos financieros', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '1.01', 'tax_form_code' => '314'],
    ['code' => '1.01.02.01', 'name' => 'Activos financieros a valor razonable', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.01.02'],
    ['code' => '1.01.02.02', 'name' => 'Activos financieros mantenidos hasta el vencimiento', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.01.02'],
    ['code' => '1.01.02.03', 'name' => 'Documentos y cuentas por cobrar clientes no relacionados', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.01.02'],
    ['code' => '1.01.02.04', 'name' => 'Documentos y cuentas por cobrar clientes relacionados', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.01.02'],
    ['code' => '1.01.02.05', 'name' => 'Cuentas por cobrar clientes', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.01.02'],
    ['code' => '1.01.02.06', 'name' => 'Otras cuentas por cobrar', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.01.02'],
    ['code' => '1.01.02.09', 'name' => '(-) Provision cuentas incobrables', 'account_type' => 'activo', 'account_nature' => 'credit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.01.02'],

    // Inventarios
    ['code' => '1.01.03', 'name' => 'Inventarios', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '1.01', 'tax_form_code' => '315'],
    ['code' => '1.01.03.01', 'name' => 'Inventario de materia prima', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.01.03'],
    ['code' => '1.01.03.02', 'name' => 'Inventario de productos en proceso', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.01.03'],
    ['code' => '1.01.03.03', 'name' => 'Inventario de productos terminados', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.01.03'],
    ['code' => '1.01.03.04', 'name' => 'Mercaderias en transito', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.01.03'],
    ['code' => '1.01.03.05', 'name' => 'Inventario de suministros y materiales', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.01.03'],
    ['code' => '1.01.03.09', 'name' => '(-) Provision por valor neto de realizacion', 'account_type' => 'activo', 'account_nature' => 'credit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.01.03'],

    // Servicios y otros pagos anticipados
    ['code' => '1.01.04', 'name' => 'Servicios y otros pagos anticipados', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '1.01', 'tax_form_code' => '316'],
    ['code' => '1.01.04.01', 'name' => 'Seguros pagados por anticipado', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.01.04'],
    ['code' => '1.01.04.02', 'name' => 'Arriendos pagados por anticipado', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.01.04'],
    ['code' => '1.01.04.03', 'name' => 'Anticipos a proveedores', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.01.04'],
    ['code' => '1.01.04.04', 'name' => 'Otros anticipos entregados', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.01.04'],

    // Activos por impuestos corrientes
    ['code' => '1.01.05', 'name' => 'Activos por impuestos corrientes', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '1.01', 'tax_form_code' => '317'],
    ['code' => '1.01.05.01', 'name' => 'Credito tributario a favor IVA', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.01.05'],
    ['code' => '1.01.05.02', 'name' => 'Credito tributario a favor IR', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.01.05'],
    ['code' => '1.01.05.03', 'name' => 'Anticipo de impuesto a la renta', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.01.05'],
    ['code' => '1.01.05.04', 'name' => 'IVA en compras', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.01.05'],
    ['code' => '1.01.05.05', 'name' => 'Retenciones IVA que le han sido efectuadas', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.01.05'],
    ['code' => '1.01.05.06', 'name' => 'Retenciones IR que le han sido efectuadas', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.01.05'],

    // ACTIVO NO CORRIENTE
    ['code' => '1.02', 'name' => 'ACTIVO NO CORRIENTE', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 2, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '1', 'tax_form_code' => '330'],

    // Propiedades, planta y equipo
    ['code' => '1.02.01', 'name' => 'Propiedades, planta y equipo', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '1.02', 'tax_form_code' => '331'],
    ['code' => '1.02.01.01', 'name' => 'Terrenos', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.02.01'],
    ['code' => '1.02.01.02', 'name' => 'Edificios', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.02.01'],
    ['code' => '1.02.01.03', 'name' => 'Construcciones en curso', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.02.01'],
    ['code' => '1.02.01.04', 'name' => 'Instalaciones', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.02.01'],
    ['code' => '1.02.01.05', 'name' => 'Muebles y enseres', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.02.01'],
    ['code' => '1.02.01.06', 'name' => 'Maquinaria y equipo', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.02.01'],
    ['code' => '1.02.01.07', 'name' => 'Naves, aeronaves, barcazas y similares', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.02.01'],
    ['code' => '1.02.01.08', 'name' => 'Equipo de computacion', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.02.01'],
    ['code' => '1.02.01.09', 'name' => 'Vehiculos, equipos de transporte', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.02.01'],
    ['code' => '1.02.01.10', 'name' => 'Otros propiedades, planta y equipo', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.02.01'],
    ['code' => '1.02.01.11', 'name' => 'Repuestos y herramientas', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.02.01'],
    ['code' => '1.02.01.12', 'name' => '(-) Depreciacion acumulada', 'account_type' => 'activo', 'account_nature' => 'credit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.02.01'],
    ['code' => '1.02.01.13', 'name' => '(-) Deterioro acumulado', 'account_type' => 'activo', 'account_nature' => 'credit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.02.01'],

    // Propiedades de inversion
    ['code' => '1.02.02', 'name' => 'Propiedades de inversion', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '1.02', 'tax_form_code' => '332'],
    ['code' => '1.02.02.01', 'name' => 'Terrenos (inversion)', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.02.02'],
    ['code' => '1.02.02.02', 'name' => 'Edificios (inversion)', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.02.02'],

    // Activos intangibles
    ['code' => '1.02.04', 'name' => 'Activos intangibles', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '1.02', 'tax_form_code' => '334'],
    ['code' => '1.02.04.01', 'name' => 'Plusvalias', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.02.04'],
    ['code' => '1.02.04.02', 'name' => 'Marcas, patentes, licencias', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.02.04'],
    ['code' => '1.02.04.03', 'name' => 'Software', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.02.04'],
    ['code' => '1.02.04.04', 'name' => '(-) Amortizacion acumulada intangibles', 'account_type' => 'activo', 'account_nature' => 'credit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.02.04'],

    // Activos por impuestos diferidos
    ['code' => '1.02.05', 'name' => 'Activos por impuestos diferidos', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.02', 'tax_form_code' => '335'],

    // Otros activos no corrientes
    ['code' => '1.02.06', 'name' => 'Otros activos no corrientes', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '1.02', 'tax_form_code' => '336'],
    ['code' => '1.02.06.01', 'name' => 'Inversiones en subsidiarias', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.02.06'],
    ['code' => '1.02.06.02', 'name' => 'Inversiones en asociadas', 'account_type' => 'activo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '1.02.06'],

    // ===================== 2. PASIVO =====================
    ['code' => '2', 'name' => 'PASIVO', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 1, 'is_parent' => true, 'allows_movement' => false, 'tax_form_code' => '411'],
    ['code' => '2.01', 'name' => 'PASIVO CORRIENTE', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 2, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '2', 'tax_form_code' => '412'],

    // Cuentas y documentos por pagar
    ['code' => '2.01.01', 'name' => 'Cuentas y documentos por pagar', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 3, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '2.01', 'tax_form_code' => '413'],
    ['code' => '2.01.01.01', 'name' => 'Proveedores locales', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '2.01.01'],
    ['code' => '2.01.01.02', 'name' => 'Proveedores del exterior', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '2.01.01'],

    // Obligaciones con instituciones financieras
    ['code' => '2.01.02', 'name' => 'Obligaciones con instituciones financieras', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 3, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '2.01', 'tax_form_code' => '414'],
    ['code' => '2.01.02.01', 'name' => 'Prestamos bancarios corto plazo', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '2.01.02'],
    ['code' => '2.01.02.02', 'name' => 'Sobregiros bancarios', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '2.01.02'],

    // Provisiones
    ['code' => '2.01.03', 'name' => 'Cuentas por pagar diversas', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 3, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '2.01'],
    ['code' => '2.01.03.01', 'name' => 'Cuentas por pagar proveedores', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '2.01.03'],

    // Obligaciones con empleados
    ['code' => '2.01.04', 'name' => 'Obligaciones con el IESS', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 3, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '2.01', 'tax_form_code' => '415'],
    ['code' => '2.01.04.01', 'name' => 'Aportes al IESS por pagar', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '2.01.04'],
    ['code' => '2.01.04.02', 'name' => 'Fondos de reserva por pagar', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '2.01.04'],
    ['code' => '2.01.04.03', 'name' => 'Prestamos quirografarios IESS', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '2.01.04'],

    // Beneficios a empleados por pagar
    ['code' => '2.01.05', 'name' => 'Beneficios a empleados por pagar', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 3, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '2.01', 'tax_form_code' => '416'],
    ['code' => '2.01.05.01', 'name' => 'Sueldos y salarios por pagar', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '2.01.05'],
    ['code' => '2.01.05.02', 'name' => 'Decimo tercer sueldo por pagar', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '2.01.05'],
    ['code' => '2.01.05.03', 'name' => 'Decimo cuarto sueldo por pagar', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '2.01.05'],
    ['code' => '2.01.05.04', 'name' => 'Vacaciones por pagar', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '2.01.05'],
    ['code' => '2.01.05.05', 'name' => 'Participacion trabajadores por pagar', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '2.01.05'],
    ['code' => '2.01.05.06', 'name' => 'Liquidaciones por pagar', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '2.01.05'],

    // Provisiones
    ['code' => '2.01.06', 'name' => 'Provisiones', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 3, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '2.01'],
    ['code' => '2.01.06.01', 'name' => 'Provision por garantias', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '2.01.06'],
    ['code' => '2.01.06.02', 'name' => 'Provision por desahucio', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '2.01.06'],
    ['code' => '2.01.06.03', 'name' => 'Provision por jubilacion patronal', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '2.01.06'],

    // Otras obligaciones corrientes
    ['code' => '2.01.07', 'name' => 'Otras obligaciones corrientes', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 3, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '2.01', 'tax_form_code' => '417'],
    ['code' => '2.01.07.01', 'name' => 'IVA en ventas por pagar', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '2.01.07'],
    ['code' => '2.01.07.02', 'name' => 'Impuesto a la renta por pagar', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '2.01.07'],
    ['code' => '2.01.07.03', 'name' => 'Retenciones IR por pagar', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '2.01.07'],
    ['code' => '2.01.07.04', 'name' => 'Retenciones IVA por pagar', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '2.01.07'],
    ['code' => '2.01.07.05', 'name' => 'IVA por pagar', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '2.01.07'],

    // Anticipos de clientes
    ['code' => '2.01.08', 'name' => 'Anticipos de clientes', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '2.01'],

    // PASIVO NO CORRIENTE
    ['code' => '2.02', 'name' => 'PASIVO NO CORRIENTE', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 2, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '2', 'tax_form_code' => '430'],
    ['code' => '2.02.01', 'name' => 'Obligaciones con instituciones financieras LP', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '2.02', 'tax_form_code' => '431'],
    ['code' => '2.02.02', 'name' => 'Cuentas por pagar diversas largo plazo', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '2.02'],
    ['code' => '2.02.03', 'name' => 'Pasivos por impuestos diferidos', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '2.02', 'tax_form_code' => '432'],
    ['code' => '2.02.04', 'name' => 'Provision por jubilacion patronal LP', 'account_type' => 'pasivo', 'account_nature' => 'credit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '2.02'],

    // ===================== 3. PATRIMONIO =====================
    ['code' => '3', 'name' => 'PATRIMONIO NETO', 'account_type' => 'patrimonio', 'account_nature' => 'credit', 'level' => 1, 'is_parent' => true, 'allows_movement' => false, 'tax_form_code' => '501'],

    // Capital
    ['code' => '3.01', 'name' => 'Capital', 'account_type' => 'patrimonio', 'account_nature' => 'credit', 'level' => 2, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '3', 'tax_form_code' => '502'],
    ['code' => '3.01.01', 'name' => 'Capital suscrito o asignado', 'account_type' => 'patrimonio', 'account_nature' => 'credit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '3.01'],
    ['code' => '3.01.02', 'name' => '(-) Capital suscrito no pagado', 'account_type' => 'patrimonio', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '3.01'],

    // Aportes futuras capitalizaciones
    ['code' => '3.02', 'name' => 'Aportes de socios para futura capitalizacion', 'account_type' => 'patrimonio', 'account_nature' => 'credit', 'level' => 2, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '3', 'tax_form_code' => '503'],

    // Prima por emision de acciones
    ['code' => '3.03', 'name' => 'Prima por emision de acciones', 'account_type' => 'patrimonio', 'account_nature' => 'credit', 'level' => 2, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '3'],

    // Reservas
    ['code' => '3.04', 'name' => 'Reservas', 'account_type' => 'patrimonio', 'account_nature' => 'credit', 'level' => 2, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '3', 'tax_form_code' => '504'],
    ['code' => '3.04.01', 'name' => 'Reserva legal', 'account_type' => 'patrimonio', 'account_nature' => 'credit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '3.04'],
    ['code' => '3.04.02', 'name' => 'Reservas facultativas y estatutarias', 'account_type' => 'patrimonio', 'account_nature' => 'credit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '3.04'],
    ['code' => '3.04.03', 'name' => 'Reserva de capital', 'account_type' => 'patrimonio', 'account_nature' => 'credit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '3.04'],

    // Otros resultados integrales
    ['code' => '3.05', 'name' => 'Otros resultados integrales', 'account_type' => 'patrimonio', 'account_nature' => 'credit', 'level' => 2, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '3', 'tax_form_code' => '505'],
    ['code' => '3.05.01', 'name' => 'Superavit por revaluacion de PPE', 'account_type' => 'patrimonio', 'account_nature' => 'credit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '3.05'],
    ['code' => '3.05.02', 'name' => 'Superavit por revaluacion de intangibles', 'account_type' => 'patrimonio', 'account_nature' => 'credit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '3.05'],

    // Resultados acumulados
    ['code' => '3.06', 'name' => 'Resultados acumulados', 'account_type' => 'patrimonio', 'account_nature' => 'credit', 'level' => 2, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '3', 'tax_form_code' => '506'],
    ['code' => '3.06.01', 'name' => 'Ganancias acumuladas', 'account_type' => 'patrimonio', 'account_nature' => 'credit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '3.06'],
    ['code' => '3.06.02', 'name' => '(-) Perdidas acumuladas', 'account_type' => 'patrimonio', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '3.06'],

    // Resultados del ejercicio
    ['code' => '3.07', 'name' => 'Resultados del ejercicio', 'account_type' => 'patrimonio', 'account_nature' => 'credit', 'level' => 2, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '3', 'tax_form_code' => '507'],
    ['code' => '3.07.01', 'name' => 'Ganancia neta del periodo', 'account_type' => 'patrimonio', 'account_nature' => 'credit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '3.07'],
    ['code' => '3.07.02', 'name' => '(-) Perdida neta del periodo', 'account_type' => 'patrimonio', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '3.07'],

    // ===================== 4. INGRESOS =====================
    ['code' => '4', 'name' => 'INGRESOS', 'account_type' => 'ingreso', 'account_nature' => 'credit', 'level' => 1, 'is_parent' => true, 'allows_movement' => false, 'tax_form_code' => '601'],

    // Ingresos de actividades ordinarias
    ['code' => '4.01', 'name' => 'Ingresos de actividades ordinarias', 'account_type' => 'ingreso', 'account_nature' => 'credit', 'level' => 2, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '4', 'tax_form_code' => '602'],
    ['code' => '4.01.01', 'name' => 'Venta de bienes', 'account_type' => 'ingreso', 'account_nature' => 'credit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '4.01'],
    ['code' => '4.01.02', 'name' => 'Prestacion de servicios', 'account_type' => 'ingreso', 'account_nature' => 'credit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '4.01'],
    ['code' => '4.01.03', 'name' => 'Contratos de construccion', 'account_type' => 'ingreso', 'account_nature' => 'credit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '4.01'],
    ['code' => '4.01.04', 'name' => 'Subvenciones del gobierno', 'account_type' => 'ingreso', 'account_nature' => 'credit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '4.01'],
    ['code' => '4.01.05', 'name' => 'Regalias', 'account_type' => 'ingreso', 'account_nature' => 'credit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '4.01'],
    ['code' => '4.01.06', 'name' => '(-) Descuento en ventas', 'account_type' => 'ingreso', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '4.01'],
    ['code' => '4.01.07', 'name' => '(-) Devoluciones en ventas', 'account_type' => 'ingreso', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '4.01'],

    // Otros ingresos
    ['code' => '4.02', 'name' => 'Otros ingresos', 'account_type' => 'ingreso', 'account_nature' => 'credit', 'level' => 2, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '4', 'tax_form_code' => '603'],
    ['code' => '4.02.01', 'name' => 'Dividendos', 'account_type' => 'ingreso', 'account_nature' => 'credit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '4.02'],
    ['code' => '4.02.02', 'name' => 'Intereses financieros', 'account_type' => 'ingreso', 'account_nature' => 'credit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '4.02'],
    ['code' => '4.02.03', 'name' => 'Ganancia en inversiones', 'account_type' => 'ingreso', 'account_nature' => 'credit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '4.02'],
    ['code' => '4.02.04', 'name' => 'Otros ingresos diversos', 'account_type' => 'ingreso', 'account_nature' => 'credit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '4.02'],

    // ===================== 5. COSTOS =====================
    ['code' => '5', 'name' => 'COSTOS', 'account_type' => 'costo', 'account_nature' => 'debit', 'level' => 1, 'is_parent' => true, 'allows_movement' => false, 'tax_form_code' => '701'],

    ['code' => '5.01', 'name' => 'Costo de ventas y produccion', 'account_type' => 'costo', 'account_nature' => 'debit', 'level' => 2, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '5', 'tax_form_code' => '702'],
    ['code' => '5.01.01', 'name' => 'Materiales utilizados o productos vendidos', 'account_type' => 'costo', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '5.01'],
    ['code' => '5.01.01.01', 'name' => 'Inventario inicial de bienes no producidos', 'account_type' => 'costo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '5.01.01'],
    ['code' => '5.01.01.02', 'name' => 'Compras netas locales de bienes', 'account_type' => 'costo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '5.01.01'],
    ['code' => '5.01.01.03', 'name' => 'Importaciones de bienes', 'account_type' => 'costo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '5.01.01'],
    ['code' => '5.01.01.04', 'name' => '(-) Inventario final de bienes no producidos', 'account_type' => 'costo', 'account_nature' => 'credit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '5.01.01'],

    ['code' => '5.01.02', 'name' => 'Mano de obra directa', 'account_type' => 'costo', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '5.01'],
    ['code' => '5.01.02.01', 'name' => 'Sueldos y beneficios sociales', 'account_type' => 'costo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '5.01.02'],
    ['code' => '5.01.02.02', 'name' => 'Gasto planes de beneficios a empleados', 'account_type' => 'costo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '5.01.02'],

    ['code' => '5.01.03', 'name' => 'Mano de obra indirecta', 'account_type' => 'costo', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '5.01'],

    ['code' => '5.01.04', 'name' => 'Otros costos indirectos de fabricacion', 'account_type' => 'costo', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '5.01'],
    ['code' => '5.01.04.01', 'name' => 'Depreciacion PP&E', 'account_type' => 'costo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '5.01.04'],
    ['code' => '5.01.04.02', 'name' => 'Deterioro de PPE', 'account_type' => 'costo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '5.01.04'],
    ['code' => '5.01.04.03', 'name' => 'Servicios publicos produccion', 'account_type' => 'costo', 'account_nature' => 'debit', 'level' => 4, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '5.01.04'],

    // ===================== 6. GASTOS =====================
    ['code' => '6', 'name' => 'GASTOS', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 1, 'is_parent' => true, 'allows_movement' => false, 'tax_form_code' => '801'],

    // Gastos de venta
    ['code' => '6.01', 'name' => 'Gastos de venta', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 2, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '6', 'tax_form_code' => '802'],
    ['code' => '6.01.01', 'name' => 'Sueldos, salarios y demas remuneraciones ventas', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6.01'],
    ['code' => '6.01.02', 'name' => 'Aportes a la seguridad social ventas', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6.01'],
    ['code' => '6.01.03', 'name' => 'Beneficios sociales e indemnizaciones ventas', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6.01'],
    ['code' => '6.01.04', 'name' => 'Comisiones en ventas', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6.01'],
    ['code' => '6.01.05', 'name' => 'Publicidad y propaganda', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6.01'],
    ['code' => '6.01.06', 'name' => 'Transporte y fletes en ventas', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6.01'],
    ['code' => '6.01.07', 'name' => 'Provision cuentas incobrables ventas', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6.01'],

    // Gastos administrativos
    ['code' => '6.02', 'name' => 'Gastos administrativos', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 2, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '6', 'tax_form_code' => '803'],
    ['code' => '6.02.01', 'name' => 'Sueldos, salarios y demas remuneraciones admin', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6.02'],
    ['code' => '6.02.02', 'name' => 'Aportes a la seguridad social admin', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6.02'],
    ['code' => '6.02.03', 'name' => 'Beneficios sociales e indemnizaciones admin', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6.02'],
    ['code' => '6.02.04', 'name' => 'Honorarios profesionales', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6.02'],
    ['code' => '6.02.05', 'name' => 'Arriendos', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6.02'],
    ['code' => '6.02.06', 'name' => 'Mantenimiento y reparaciones', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6.02'],
    ['code' => '6.02.07', 'name' => 'Combustibles', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6.02'],
    ['code' => '6.02.08', 'name' => 'Seguros y reaseguros', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6.02'],
    ['code' => '6.02.09', 'name' => 'Suministros de oficina', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6.02'],
    ['code' => '6.02.10', 'name' => 'Servicios publicos', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6.02'],
    ['code' => '6.02.11', 'name' => 'Impuestos, contribuciones y otros', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6.02'],
    ['code' => '6.02.12', 'name' => 'Depreciaciones admin', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6.02'],
    ['code' => '6.02.13', 'name' => 'Amortizaciones admin', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6.02'],
    ['code' => '6.02.14', 'name' => 'Gastos de viaje', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6.02'],
    ['code' => '6.02.15', 'name' => 'Agua, luz, telecomunicaciones', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6.02'],
    ['code' => '6.02.16', 'name' => 'Notarios y registradores de propiedad', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6.02'],
    ['code' => '6.02.99', 'name' => 'Otros gastos administrativos', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6.02'],

    // Gastos financieros
    ['code' => '6.03', 'name' => 'Gastos financieros', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 2, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '6', 'tax_form_code' => '804'],
    ['code' => '6.03.01', 'name' => 'Intereses bancarios', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6.03'],
    ['code' => '6.03.02', 'name' => 'Comisiones bancarias', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6.03'],
    ['code' => '6.03.03', 'name' => 'Diferencia en cambio', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6.03'],
    ['code' => '6.03.04', 'name' => 'Otros gastos financieros', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6.03'],

    // Otros gastos
    ['code' => '6.04', 'name' => 'Otros gastos', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 2, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '6', 'tax_form_code' => '805'],
    ['code' => '6.04.01', 'name' => 'Perdida en venta de activos', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6.04'],
    ['code' => '6.04.02', 'name' => 'Gastos no deducibles', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6.04'],
    ['code' => '6.04.03', 'name' => 'Otros gastos diversos', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6.04'],

    // Impuesto a la renta
    ['code' => '6.05', 'name' => 'Impuesto a la renta', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 2, 'is_parent' => true, 'allows_movement' => false, 'parent_code' => '6'],
    ['code' => '6.05.01', 'name' => 'Impuesto a la renta corriente', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6.05'],
    ['code' => '6.05.02', 'name' => 'Impuesto a la renta diferido', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 3, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6.05'],

    // Participacion trabajadores
    ['code' => '6.06', 'name' => 'Participacion trabajadores', 'account_type' => 'gasto', 'account_nature' => 'debit', 'level' => 2, 'is_parent' => false, 'allows_movement' => true, 'parent_code' => '6'],
];
