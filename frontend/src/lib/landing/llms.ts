import { DOCUMENT_TYPES, FAQS, FEATURES, SITE_DESCRIPTION } from "@/content/landing";
import { formatPrice, type LandingPlan } from "./pricing";

/** Texto para llms.txt (llmstxt.org): resumen factual de Facturón para asistentes de IA. */
export function buildLlmsTxt(input: { appUrl: string; plans: LandingPlan[]; contactEmail: string; whatsapp: string }): string {
  const lines: string[] = [
    "# Facturón",
    "",
    `> ${SITE_DESCRIPTION}`,
    "",
    "Facturón es un software de facturación electrónica para Ecuador, desarrollado por AmePhia Systems Inc. Genera comprobantes según la ficha técnica del SRI, los firma con el certificado .p12 del contribuyente (XAdES-BES) y los envía a los web services del SRI, que autoriza cada comprobante. Facturón no está afiliado al SRI.",
    "",
    "## Para quién",
    "",
    "- Profesionales, emprendedores y PyMEs de Ecuador que emiten comprobantes electrónicos.",
    "- Contadores que gestionan varias empresas (RUC).",
    "- Árbitros de fútbol que facturan a la FEF (módulo especializado).",
    "",
    "## Comprobantes",
    "",
    ...DOCUMENT_TYPES.map((d) => `- ${d.code} ${d.name}: ${d.use}`),
    "",
    "## Funcionalidades",
    "",
    ...FEATURES.map((f) => `- ${f.title}: ${f.text}`),
  ];

  if (input.plans.length > 0) {
    lines.push("", "## Precios", "", "Sin comisión por documento. Pago por transferencia bancaria; sin período de prueba.", "");
    for (const p of input.plans) {
      lines.push(`- ${p.name}: ${formatPrice(p.price_monthly, p.currency)}/mes o ${formatPrice(p.price_yearly, p.currency)}/año. ${p.features_list.join(", ")}.`);
    }
  }

  lines.push(
    "",
    "## Requisitos",
    "",
    "- RUC activo.",
    "- Firma electrónica vigente (.p12 del Banco Central, Security Data, ANF u otra entidad acreditada).",
    "- Habilitación de comprobantes electrónicos en SRI en línea.",
    "",
    "## Preguntas frecuentes",
    "",
    ...FAQS.map((f) => `- ${f.q} ${f.a}`),
    "",
    "## Enlaces",
    "",
    `- Sitio: ${input.appUrl}/`,
    `- Crear cuenta: ${input.appUrl}/register`,
    `- Ingresar: ${input.appUrl}/login`,
    `- API para desarrolladores (docs + OpenAPI): ${input.appUrl}/docs/api`,
    `- Términos: ${input.appUrl}/terms`,
    `- Privacidad: ${input.appUrl}/privacy`,
    `- Contacto: ${input.contactEmail} · WhatsApp ${input.whatsapp}`,
    "",
  );

  return lines.join("\n");
}
