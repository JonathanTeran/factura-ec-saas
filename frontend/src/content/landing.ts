/**
 * Única fuente del copy y los datos estáticos de la landing. La UI y el
 * JSON-LD leen de aquí. Sin JSX: debe poder importarse en Node (tests, llms.txt).
 */
export type IconName =
  | "ShieldCheck"
  | "Zap"
  | "Mail"
  | "Store"
  | "Package"
  | "BookOpen"
  | "Users"
  | "Building2"
  | "Code2"
  | "Smartphone"
  | "Repeat"
  | "Sparkles"
  | "FileText"
  | "FileMinus"
  | "FilePlus2"
  | "Truck"
  | "Receipt"
  | "FileCheck2";

export type FaqItem = { q: string; a: string };
export type DocumentType = { code: string; name: string; use: string; icon: IconName };
export type Feature = { title: string; text: string; icon: IconName; span?: 2 };
export type ShowcaseRow = {
  title: string;
  text: string;
  bullets: string[];
  image: { src: string; alt: string; width: number; height: number; kind: "browser" | "phone" };
};

export const NAV_LINKS = [
  { href: "#funcionalidades", label: "Funcionalidades" },
  { href: "#como-funciona", label: "Cómo funciona" },
  { href: "#precios", label: "Precios" },
  { href: "#faq", label: "FAQ" },
] as const;

export const SITE_DESCRIPTION =
  "Factura electrónicamente en Ecuador desde $2.99 al mes, sin comisión por documento. Facturas, retenciones y guías autorizadas por el SRI en segundos.";

export const HERO = {
  eyebrow: "Facturación electrónica · Ecuador",
  title: "Facturación electrónica que el SRI autoriza en segundos",
  subtitle:
    "Facturas, retenciones, guías y más, firmadas con tu certificado y enviadas a tus clientes automáticamente.",
} as const;

export const TRUST_CHIPS = [
  "Ficha técnica del SRI",
  "Firma XAdES-BES",
  "Certificados BCE, Security Data y ANF",
  "Ambiente de pruebas y producción",
  "Todas las tarifas de IVA del catálogo SRI (15 %, 8 %, 5 %, 0 %, exento, no objeto)",
] as const;

export const DOCUMENT_TYPES: DocumentType[] = [
  { code: "01", name: "Factura", use: "Ventas de bienes y servicios a consumidores y empresas.", icon: "FileText" },
  { code: "03", name: "Liquidación de compra", use: "Compras a personas que no emiten comprobantes.", icon: "Receipt" },
  { code: "04", name: "Nota de crédito", use: "Devoluciones, descuentos y anulaciones parciales.", icon: "FileMinus" },
  { code: "05", name: "Nota de débito", use: "Cargos e intereses posteriores a la factura.", icon: "FilePlus2" },
  { code: "06", name: "Guía de remisión", use: "Traslado de mercadería con respaldo tributario.", icon: "Truck" },
  { code: "07", name: "Comprobante de retención", use: "Retenciones de IVA y renta a tus proveedores.", icon: "FileCheck2" },
];

export const SHOWCASE: ShowcaseRow[] = [
  {
    title: "Todo tu negocio en un panel",
    text: "Ventas del mes, documentos pendientes y rechazados, cobros y el estado de cada comprobante ante el SRI, sin recargar la página.",
    bullets: ["Estado SRI de cada documento en vivo", "Cobros y pendientes de un vistazo", "Varias empresas desde una sola cuenta"],
    image: { src: "/marketing/panel-dashboard.png", alt: "Panel web de Facturón con el resumen de ventas y documentos", width: 1920, height: 1200, kind: "browser" },
  },
  {
    title: "Una factura en 30 segundos",
    text: "Busca al cliente por RUC o cédula y completa sus datos desde el SRI, agrega productos con IVA calculado y emite. Nosotros firmamos, enviamos y entregamos.",
    bullets: ["Cliente por RUC o cédula con datos del SRI", "IVA y totales calculados solos", "Firma y envío automáticos"],
    image: { src: "/marketing/panel-invoice.png", alt: "Formulario de nueva factura en Facturón", width: 1920, height: 1200, kind: "browser" },
  },
  {
    title: "Vende desde el celular o en caja",
    text: "La app para Android e iOS emite y consulta comprobantes desde donde estés. El punto de venta abre sesiones de caja e imprime en impresora térmica.",
    bullets: ["App Android e iOS", "Sesiones de caja y cierres", "Impresión térmica del recibo"],
    image: { src: "/marketing/app-home.png", alt: "Inicio de la app móvil de Facturón con el resumen del mes", width: 1080, height: 2400, kind: "phone" },
  },
];

export const FEATURES: Feature[] = [
  { title: "Firmamos por ti", text: "Sube tu certificado .p12 una vez y firmamos cada comprobante con XAdES-BES, como exige el SRI. No instalas nada.", icon: "ShieldCheck", span: 2 },
  { title: "Autorización con reintentos", text: "Si el SRI no responde, el documento espera en cola y se reintenta solo. Ves el estado en tiempo real.", icon: "Zap", span: 2 },
  { title: "RIDE + XML por correo", text: "Tu cliente recibe el PDF y el XML al instante, con tu logo.", icon: "Mail" },
  { title: "Punto de venta", text: "Sesiones de caja, factura al instante e impresora térmica.", icon: "Store" },
  { title: "Inventario y compras", text: "Stock con alertas de mínimos, compras y documentos recibidos.", icon: "Package" },
  { title: "Contabilidad, ATS e IVA", text: "Plan de cuentas, asientos automáticos, ATS mensual y resumen para la declaración de IVA.", icon: "BookOpen" },
  { title: "Portal de clientes", text: "Tus clientes descargan sus comprobantes sin pedírtelos.", icon: "Users" },
  { title: "Multi-empresa", text: "Varios RUC en una cuenta; cambias de empresa con un clic.", icon: "Building2" },
  { title: "API REST", text: "Crea documentos y consulta datos desde tu propio sistema.", icon: "Code2" },
  { title: "App móvil", text: "Android e iOS para emitir y consultar desde cualquier lugar.", icon: "Smartphone" },
  { title: "Proformas y recurrentes", text: "Cotiza, convierte en factura y programa cobros periódicos.", icon: "Repeat" },
  { title: "Categorización con IA", text: "Clasifica productos y gastos automáticamente.", icon: "Sparkles" },
];

export const FEATURE_LIST_FOR_SEO = FEATURES.map((f) => f.title);

export const STEPS = [
  { title: "Crea tu cuenta con tu RUC", text: "Registro en 2 minutos. Configura tu establecimiento y punto de emisión." },
  { title: "Sube tu certificado .p12", text: "El del BCE, Security Data, ANF u otra entidad acreditada. Lo usamos para firmar por ti." },
  { title: "Emite", text: "Se firma, se envía al SRI y llega a tu cliente en PDF y XML. Automático." },
] as const;

export const ARBITROS = {
  eyebrow: "Facturón para Árbitros",
  title: "Una factura por partido, sin perder ninguno.",
  text: "Módulo especializado para árbitros de fútbol que facturan a la FEF.",
  bullets: [
    "Partidos pendientes por facturar en tiempo real",
    "Facturación en lote: una factura por partido con el concepto que exige la FEF",
    "Control de la ventana de recepción (del 1 al 20 de cada mes)",
    "Si anulas una factura, el partido vuelve a pendiente automáticamente",
  ],
  cta: "Escríbenos por WhatsApp",
  whatsappText: "Hola, soy árbitro y quiero información de Facturón",
} as const;

export const REASONS = [
  { title: "Hecho para el SRI desde cero", text: "XML según la ficha técnica, firma XAdES-BES y comunicación directa con los web services del SRI.", icon: "ShieldCheck" as IconName },
  { title: "Todo en uno", text: "Facturación, punto de venta, inventario, contabilidad básica y portal de clientes, sin integrar cinco sistemas.", icon: "Building2" as IconName },
  { title: "Sin trabajo manual", text: "Creas el documento; nosotros lo firmamos, lo enviamos al SRI y se lo mandamos a tu cliente.", icon: "Zap" as IconName },
] as const;

export const FAQS: FaqItem[] = [
  {
    q: "¿Tienen API para integrar mi sistema?",
    a: "Sí. Los planes Negocio, Profesional y Enterprise incluyen una API REST con llaves por empresa para emitir comprobantes y consultar autorizaciones, RIDE y XML desde tu ERP, tienda en línea o punto de venta, con idempotencia para reintentar sin duplicar facturas. La documentación y la especificación OpenAPI están en facturon.ec/docs/api.",
  },
  {
    q: "¿Qué necesito para facturar electrónicamente en Ecuador?",
    a: "Tu RUC activo, una firma electrónica vigente (archivo .p12 del Banco Central, Security Data, ANF u otra entidad acreditada) y estar habilitado para emitir comprobantes electrónicos en SRI en línea. Facturón te guía paso a paso en el proceso.",
  },
  {
    q: "¿Los comprobantes emitidos con Facturón son válidos ante el SRI?",
    a: "Sí. Generamos el XML según la ficha técnica del SRI, lo firmamos con XAdES-BES y lo enviamos a los web services del SRI, que devuelve la autorización de cada comprobante. Facturón no es el SRI ni está afiliado a él.",
  },
  {
    q: "¿Cuánto cuesta?",
    a: "Los planes empiezan en $2.99 al mes y ninguno cobra comisión por documento. El plan anual tiene descuento. Puedes ver todos los planes y lo que incluye cada uno en la tabla de precios.",
  },
  {
    q: "¿Cómo funciona el registro y el pago?",
    a: "Te registras en 2 minutos, eliges el plan y pagas por transferencia bancaria. Cuando confirmamos el pago, tu cuenta se activa con todas las funciones del plan. Hoy no ofrecemos período de prueba.",
  },
  {
    q: "¿Puedo migrar desde otro sistema de facturación?",
    a: "Sí. Importas tus clientes y productos desde CSV o Excel, y tus secuenciales continúan donde los dejaste en el sistema anterior, así no rompes la numeración ante el SRI.",
  },
  {
    q: "¿Qué pasa si el SRI está caído o no responde?",
    a: "Tu documento queda en cola y se reintenta automáticamente hasta que el servicio se restablece. Puedes ver el estado de cada comprobante en tiempo real desde el panel.",
  },
  {
    q: "¿Puedo facturar desde el celular?",
    a: "Sí. La app de Facturón está disponible para Android en Google Play y la versión para iOS está en revisión en la App Store. La web también funciona en el navegador del celular.",
  },
  {
    q: "¿Genera el ATS y la declaración de IVA?",
    a: "Sí. Desde reportes generas el ATS mensual en XML, listo para subir al portal del SRI, y un resumen de ventas e IVA para preparar la declaración.",
  },
  {
    q: "¿Sirve para mi contador?",
    a: "Sí. Puedes dar acceso a tu contador, y tiene plan de cuentas, asientos automáticos y reportes de ventas, compras y retenciones para trabajar sin pedirte archivos.",
  },
  {
    q: "¿Tiene API para conectar mi propio sistema?",
    a: "Sí. Facturón tiene una API REST documentada para crear documentos y consultar clientes, productos y reportes. Está disponible según el plan que elijas.",
  },
  {
    q: "¿Puedo manejar varias empresas (RUC) con una sola cuenta?",
    a: "Sí, en los planes que lo incluyen. Administras varios RUC desde la misma cuenta y cambias de empresa con un clic, sin pagar otra suscripción por cada una.",
  },
  {
    q: "¿Venden certificados de firma electrónica?",
    a: "No. Usas el certificado .p12 que ya tienes o el que compres en el Banco Central, Security Data, ANF u otra entidad acreditada. Lo subes una vez y firmamos con él.",
  },
  {
    q: "¿Qué es Facturón para Árbitros?",
    a: "Es un módulo para árbitros de fútbol que facturan a la FEF: muestra los partidos pendientes por facturar, emite una factura por partido con el concepto exigido, controla la ventana de recepción y devuelve el partido a pendiente si anulas la factura.",
  },
];

export const FINAL_CTA = {
  title: "Empieza a facturar hoy.",
  text: "Crea tu cuenta en 2 minutos. Sin contratos. Desde $2.99 al mes.",
} as const;
