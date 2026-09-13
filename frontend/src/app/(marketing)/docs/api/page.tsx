import type { Metadata } from "next";
import Link from "next/link";
import { ArrowRight, BookOpen, FileJson, KeyRound, ShieldCheck, Timer, Zap } from "lucide-react";
import {
  API_BASE_URL,
  API_SECTIONS,
  CODE_EXAMPLES,
  DOC_FAQS,
  ERROR_CODES,
  OPENAPI_PATH,
  RATE_LIMITS,
  SCOPES,
} from "@/content/api-docs";
import { APP_URL, CONTACT_EMAIL } from "@/lib/landing/config";
import { CodeBlock } from "@/components/marketing/docs/code-block";
import { DocsNav } from "@/components/marketing/docs/docs-nav";
import { EndpointCard } from "@/components/marketing/docs/endpoint-card";
import { SiteFooter } from "@/components/marketing/footer";
import { MarketingNav } from "@/components/marketing/nav";

const TITLE = "Documentación de la API de Facturón";
const DESCRIPTION =
  "API REST para emitir facturas electrónicas autorizadas por el SRI y consultar lo facturado desde tu ERP, tienda en línea o sistema. Llaves por empresa, alcances, idempotencia y ejemplos en curl, JavaScript y PHP.";

export const metadata: Metadata = {
  title: { absolute: `${TITLE} — facturación electrónica del Ecuador` },
  description: DESCRIPTION,
  alternates: { canonical: "/docs/api" },
  openGraph: { title: TITLE, description: DESCRIPTION, url: "/docs/api", type: "article" },
  twitter: { card: "summary_large_image", title: TITLE, description: DESCRIPTION },
};

// La nav lee la cookie de sesión: se renderiza por petición.
export const dynamic = "force-dynamic";

const STEPS = [
  { n: 1, title: "Crea una llave", text: "En el panel: Configuración → API e integraciones. Elige alcances y caducidad. La llave se muestra una sola vez." },
  { n: 2, title: "Descubre tus ids", text: "GET /companies devuelve company_id y emission_point_id; GET /customers/lookup encuentra al cliente por cédula o RUC (o créalo con POST /customers)." },
  { n: 3, title: "Emite", text: "POST /documents con los importes calculados y la cabecera Idempotency-Key. Responde 201 con el documento en processing." },
  { n: 4, title: "Confirma la autorización", text: "GET /documents/{id}/status cada 5–10 s hasta authorized (o rejected con los mensajes del SRI)." },
  { n: 5, title: "Entrega el comprobante", text: "GET /documents/{id}/ride (PDF) y /xml, o POST /documents/{id}/email para reenviarlo al cliente." },
];

function SectionHeading({ id, eyebrow, title, children }: { id: string; eyebrow: string; title: string; children?: React.ReactNode }) {
  return (
    <div id={id} className="scroll-mt-24">
      <p className="text-[11px] font-semibold uppercase tracking-[0.18em] text-[#2B54E4]">{eyebrow}</p>
      <h2 className="mt-1.5 font-display text-[1.75rem] font-extrabold leading-tight tracking-tight text-navy sm:text-[2rem]">{title}</h2>
      {children && <div className="mt-2 max-w-2xl text-[15px] leading-relaxed text-slate-600">{children}</div>}
    </div>
  );
}

export default function ApiDocsPage() {
  const jsonLd = {
    "@context": "https://schema.org",
    "@type": "TechArticle",
    headline: TITLE,
    description: DESCRIPTION,
    inLanguage: "es-EC",
    url: `${APP_URL}/docs/api`,
    author: { "@type": "Organization", name: "AmePhia Systems", url: "https://amephia.com" },
    publisher: { "@type": "Organization", name: "Facturón", url: APP_URL },
    about: { "@type": "SoftwareApplication", name: "Facturón", applicationCategory: "BusinessApplication" },
  };

  return (
    <>
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(jsonLd) }} />
      <MarketingNav />
      <main id="contenido">
        {/* Cabecera navy: coherente con el hero de la landing y con la nav transparente. */}
        <header className="relative overflow-hidden bg-[#0B1220] pb-16 pt-32 text-white">
          <div aria-hidden className="pointer-events-none absolute inset-0 bg-[radial-gradient(60%_50%_at_80%_0%,rgba(43,84,228,0.35),transparent_60%)]" />
          <div aria-hidden className="absolute inset-x-0 top-0 h-[3px] bg-[linear-gradient(90deg,#FFCE00_0%,#FFCE00_33%,#0653C6_33%,#0653C6_66%,#EF3340_66%,#EF3340_100%)]" />
          <div className="relative mx-auto max-w-6xl px-5 sm:px-8">
            <p className="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-3 py-1 text-[12px] font-medium text-slate-200">
              <BookOpen className="size-3.5 text-[#8FA6FF]" /> Documentación para desarrolladores · v1
            </p>
            <h1 className="mt-5 max-w-3xl font-display text-[2.4rem] font-extrabold leading-[1.05] tracking-tight sm:text-5xl">
              Emite comprobantes del SRI desde tu propio sistema
            </h1>
            <p className="mt-4 max-w-2xl text-[17px] leading-relaxed text-slate-300">
              Una API REST con llaves por empresa para crear facturas, notas de crédito, retenciones y más, seguir su autorización y descargar el RIDE y el XML. Integra tu ERP, tienda en línea o punto de venta en una tarde.
            </p>
            <div className="mt-7 flex flex-wrap items-center gap-3">
              <Link
                href="/settings/api"
                className="inline-flex items-center gap-2 rounded-full bg-[#2B54E4] px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-[#2B54E4]/25 transition-colors hover:bg-[#2446C4]"
              >
                <KeyRound className="size-4" /> Crear mi llave de API
              </Link>
              <a
                href={OPENAPI_PATH}
                className="inline-flex items-center gap-2 rounded-full border border-white/20 px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-white/10"
              >
                <FileJson className="size-4" /> OpenAPI 3.1 (YAML)
              </a>
            </div>
            <dl className="mt-10 grid max-w-3xl grid-cols-2 gap-4 text-sm sm:grid-cols-4">
              {[
                { icon: Zap, k: "Base URL", v: "facturon.ec/api/v1/ext" },
                { icon: ShieldCheck, k: "Autenticación", v: "Bearer fec_…" },
                { icon: Timer, k: "Emisión", v: "asíncrona, segundos" },
                { icon: KeyRound, k: "Planes", v: "Negocio y superiores" },
              ].map(({ icon: Icon, k, v }) => (
                <div key={k} className="rounded-xl border border-white/10 bg-white/5 p-3">
                  <dt className="flex items-center gap-1.5 text-[11px] uppercase tracking-wider text-slate-400">
                    <Icon className="size-3.5" /> {k}
                  </dt>
                  <dd className="mt-1 font-mono text-[13px] text-white">{v}</dd>
                </div>
              ))}
            </dl>
          </div>
        </header>

        <div className="mx-auto max-w-6xl px-5 py-12 sm:px-8 lg:grid lg:grid-cols-[220px_minmax(0,1fr)] lg:gap-12">
          <aside className="hidden lg:block">
            <div className="sticky top-24">
              <DocsNav />
              <div className="mt-8 rounded-xl border border-slate-200 bg-slate-50 p-4 text-[13px] leading-relaxed text-slate-600">
                ¿Dudas o un caso especial?{" "}
                <a href={`mailto:${CONTACT_EMAIL}`} className="font-semibold text-[#2446C4] hover:underline">
                  {CONTACT_EMAIL}
                </a>
              </div>
            </div>
          </aside>

          <div className="space-y-16">
            {/* Introducción */}
            <section>
              <SectionHeading id="introduccion" eyebrow="Introducción" title="Qué puedes hacer con la API">
                Todo lo que hace el panel de Facturón al emitir, expuesto como recursos JSON: empresas y puntos de emisión, clientes, productos, documentos electrónicos con su ciclo completo ante el SRI y los catálogos oficiales. Las respuestas siguen siempre el mismo envoltorio.
              </SectionHeading>
              <div className="mt-6 grid gap-4 sm:grid-cols-3">
                {[
                  { title: "Emitir", text: "Facturas, liquidaciones, notas de crédito y débito, guías de remisión y retenciones." },
                  { title: "Consultar", text: "Estado de autorización, mensajes del SRI, RIDE en PDF y XML autorizado." },
                  { title: "Sincronizar", text: "Clientes y productos en ambos sentidos, con búsqueda por identificación." },
                ].map((c) => (
                  <div key={c.title} className="rounded-2xl border border-slate-200 bg-white p-5">
                    <h3 className="font-semibold text-navy">{c.title}</h3>
                    <p className="mt-1.5 text-[14px] leading-relaxed text-slate-600">{c.text}</p>
                  </div>
                ))}
              </div>
              <CodeBlock
                className="mt-6"
                label="envoltorio de respuesta"
                code={`{ "success": true, "message": "…", "data": { … } }          // éxito
{ "success": true, "data": [ … ], "meta": { "current_page": 1, "last_page": 3, "per_page": 15, "total": 42 } }  // listados
{ "success": false, "error": "insufficient_scope", "message": "…", "required_scope": "documents:write" }       // error`}
              />
            </section>

            {/* Autenticación */}
            <section>
              <SectionHeading id="autenticacion" eyebrow="Autenticación" title="Llaves de API por empresa">
                Cada llave pertenece a una cuenta de Facturón, se muestra una sola vez al crearla y se guarda cifrada (hash). Envíala en la cabecera <code className="rounded bg-slate-100 px-1 font-mono text-[13px]">Authorization: Bearer</code> o, si tu herramienta lo prefiere, en <code className="rounded bg-slate-100 px-1 font-mono text-[13px]">X-API-Key</code>. Nunca en la URL.
              </SectionHeading>
              <CodeBlock
                className="mt-6"
                label="primera petición"
                code={`curl ${API_BASE_URL}/me \\
  -H "Authorization: Bearer fec_TU_LLAVE"`}
              />
              <ul className="mt-5 grid gap-3 text-[14.5px] text-slate-600 sm:grid-cols-2">
                <li className="rounded-xl border border-slate-200 bg-white p-4"><strong className="text-navy">Rotación.</strong> Genera una credencial nueva sin borrar la configuración; la anterior deja de funcionar al instante.</li>
                <li className="rounded-xl border border-slate-200 bg-white p-4"><strong className="text-navy">Caducidad.</strong> Opcional: 30, 90 o 365 días. Una llave caducada responde <code className="font-mono text-[13px]">401 expired_api_key</code>.</li>
                <li className="rounded-xl border border-slate-200 bg-white p-4"><strong className="text-navy">Hasta 10 llaves activas</strong> por cuenta: una por sistema, con el mínimo de alcances que necesite.</li>
                <li className="rounded-xl border border-slate-200 bg-white p-4"><strong className="text-navy">Solo HTTPS.</strong> Las llaves equivalen a las credenciales del propietario de la cuenta: guárdalas como secretos.</li>
              </ul>
            </section>

            {/* Alcances y límites */}
            <section>
              <SectionHeading id="alcances-limites" eyebrow="Alcances y límites" title="Mínimo privilegio y cuotas por plan">
                Al crear la llave eliges qué puede hacer. Sin el alcance necesario la API responde <code className="rounded bg-slate-100 px-1 font-mono text-[13px]">403 insufficient_scope</code> indicando cuál falta.
              </SectionHeading>
              <div className="mt-6 grid gap-6 lg:grid-cols-[1.4fr_1fr]">
                <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                  <table className="w-full text-left text-[13.5px]">
                    <thead className="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500">
                      <tr><th className="px-4 py-2.5 font-semibold">Alcance</th><th className="px-4 py-2.5 font-semibold">Permite</th></tr>
                    </thead>
                    <tbody>
                      {SCOPES.map((s) => (
                        <tr key={s.scope} className="border-t border-slate-100 align-top">
                          <td className="px-4 py-2.5 font-mono text-[12.5px] text-navy">{s.scope}</td>
                          <td className="px-4 py-2.5 text-slate-600">{s.description}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
                <div className="space-y-4">
                  <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                    <table className="w-full text-left text-[13.5px]">
                      <thead className="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500">
                        <tr><th className="px-4 py-2.5 font-semibold">Plan</th><th className="px-4 py-2.5 font-semibold">Límite</th></tr>
                      </thead>
                      <tbody>
                        {RATE_LIMITS.map((r) => (
                          <tr key={r.plan} className="border-t border-slate-100">
                            <td className="px-4 py-2.5 font-medium text-navy">{r.plan}</td>
                            <td className="px-4 py-2.5 text-slate-600">{r.limit}</td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                  <p className="text-[13.5px] leading-relaxed text-slate-600">
                    Cada respuesta incluye <code className="font-mono text-[12.5px]">X-RateLimit-Limit</code> y <code className="font-mono text-[12.5px]">X-RateLimit-Remaining</code>. Al superarlo: <code className="font-mono text-[12.5px]">429 rate_limit_exceeded</code> con <code className="font-mono text-[12.5px]">Retry-After</code>. Puedes fijar un límite menor por llave. El límite mensual de documentos es el del plan.
                  </p>
                </div>
              </div>
            </section>

            {/* Flujo */}
            <section>
              <SectionHeading id="flujo" eyebrow="Flujo para emitir" title="De tu pedido a un comprobante autorizado en cinco pasos" />
              <ol className="mt-6 grid gap-3 md:grid-cols-2">
                {STEPS.map((s) => (
                  <li key={s.n} className="flex gap-4 rounded-2xl border border-slate-200 bg-white p-5">
                    <span className="flex size-8 shrink-0 items-center justify-center rounded-full bg-[#0B1220] font-display text-sm font-bold text-white">{s.n}</span>
                    <div>
                      <h3 className="font-semibold text-navy">{s.title}</h3>
                      <p className="mt-1 text-[14px] leading-relaxed text-slate-600">{s.text}</p>
                    </div>
                  </li>
                ))}
              </ol>
              <div className="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-[14px] leading-relaxed text-amber-900">
                <strong>Ambiente de pruebas.</strong> Configura la empresa emisora en ambiente de pruebas del SRI para integrar sin emitir comprobantes reales; la API y la llave son las mismas. Al pasar a producción solo cambias el ambiente de la empresa.
              </div>
            </section>

            {/* Idempotencia */}
            <section>
              <SectionHeading id="idempotencia" eyebrow="Idempotencia" title="Reintenta sin duplicar facturas">
                Las redes fallan. Envía en <code className="rounded bg-slate-100 px-1 font-mono text-[13px]">POST /documents</code> la cabecera <code className="rounded bg-slate-100 px-1 font-mono text-[13px]">Idempotency-Key</code> con un identificador único de tu operación (por ejemplo, el número de pedido). Durante 24 horas, cualquier repetición con el mismo cuerpo devuelve exactamente la misma respuesta.
              </SectionHeading>
              <div className="mt-6 grid gap-3 sm:grid-cols-3">
                {[
                  { code: "201 + Idempotent-Replayed: true", text: "Misma llave, mismo cuerpo: se devuelve la respuesta original, no se crea otro documento." },
                  { code: "409 idempotency_key_reused", text: "Misma llave con otro cuerpo: usa una llave nueva para cada operación distinta." },
                  { code: "409 idempotency_in_progress", text: "Dos peticiones simultáneas con la misma llave: espera unos segundos y reintenta." },
                ].map((c) => (
                  <div key={c.code} className="rounded-2xl border border-slate-200 bg-white p-4">
                    <code className="font-mono text-[12.5px] font-semibold text-navy">{c.code}</code>
                    <p className="mt-1.5 text-[13.5px] leading-relaxed text-slate-600">{c.text}</p>
                  </div>
                ))}
              </div>
            </section>

            {/* Referencia */}
            {API_SECTIONS.map((section) => (
              <section key={section.id}>
                <SectionHeading id={section.id} eyebrow="Referencia" title={section.title}>
                  {section.intro}
                </SectionHeading>
                <div className="mt-6 space-y-4">
                  {section.endpoints.map((endpoint) => (
                    <EndpointCard key={endpoint.id} endpoint={endpoint} />
                  ))}
                </div>
              </section>
            ))}

            {/* Errores */}
            <section>
              <SectionHeading id="errores" eyebrow="Errores" title="Códigos estables para tu manejo de errores">
                Además del código HTTP, cada error trae un campo <code className="rounded bg-slate-100 px-1 font-mono text-[13px]">error</code> en snake_case que no cambia entre versiones, y un <code className="rounded bg-slate-100 px-1 font-mono text-[13px]">message</code> legible en español.
              </SectionHeading>
              <div className="mt-6 overflow-x-auto rounded-2xl border border-slate-200 bg-white">
                <table className="w-full min-w-[560px] text-left text-[13.5px]">
                  <thead className="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500">
                    <tr><th className="px-4 py-2.5 font-semibold">HTTP</th><th className="px-4 py-2.5 font-semibold">error</th><th className="px-4 py-2.5 font-semibold">Cuándo</th></tr>
                  </thead>
                  <tbody>
                    {ERROR_CODES.map((e) => (
                      <tr key={e.code} className="border-t border-slate-100 align-top">
                        <td className="px-4 py-2.5 font-mono text-[12.5px] text-slate-600">{e.status}</td>
                        <td className="px-4 py-2.5 font-mono text-[12.5px] font-semibold text-navy">{e.code}</td>
                        <td className="px-4 py-2.5 text-slate-600">{e.when}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </section>

            {/* Ejemplos */}
            <section>
              <SectionHeading id="ejemplos" eyebrow="Ejemplos de código" title="Del cero a la primera factura">
                Los tres ejemplos hacen lo mismo: verificar la llave, emitir con idempotencia, esperar la autorización y descargar el RIDE.
              </SectionHeading>
              <div className="mt-6 space-y-4">
                {CODE_EXAMPLES.map((ex) => (
                  <CodeBlock key={ex.id} label={ex.label} code={ex.code} />
                ))}
              </div>
            </section>

            {/* FAQ */}
            <section>
              <SectionHeading id="preguntas" eyebrow="Preguntas frecuentes" title="Lo que preguntan los integradores" />
              <div className="mt-6 divide-y divide-slate-200 rounded-2xl border border-slate-200 bg-white">
                {DOC_FAQS.map((f) => (
                  <details key={f.q} className="group px-5 py-4">
                    <summary className="flex cursor-pointer list-none items-center justify-between gap-4 text-[15px] font-semibold text-navy">
                      {f.q}
                      <ArrowRight className="size-4 shrink-0 text-slate-400 transition-transform group-open:rotate-90" />
                    </summary>
                    <p className="mt-2 text-[14.5px] leading-relaxed text-slate-600">{f.a}</p>
                  </details>
                ))}
              </div>
              <div className="mt-8 rounded-2xl bg-[#0B1220] p-6 text-white sm:flex sm:items-center sm:justify-between">
                <div>
                  <h3 className="font-display text-xl font-bold">¿Listo para integrar?</h3>
                  <p className="mt-1 text-sm text-slate-300">Crea tu cuenta, elige un plan con API y genera tu primera llave en minutos.</p>
                </div>
                <div className="mt-4 flex gap-3 sm:mt-0">
                  <Link href="/register?plan=negocio" className="inline-flex items-center gap-2 rounded-full bg-[#2B54E4] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#2446C4]">
                    Crear cuenta <ArrowRight className="size-4" />
                  </Link>
                  <Link href="/settings/api" className="inline-flex items-center rounded-full border border-white/20 px-5 py-2.5 text-sm font-semibold hover:bg-white/10">
                    Mis llaves
                  </Link>
                </div>
              </div>
            </section>
          </div>
        </div>
      </main>
      <SiteFooter />
    </>
  );
}
