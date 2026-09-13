import type { ApiEndpoint, HttpMethod } from "@/content/api-docs";
import { CodeBlock } from "./code-block";

const METHOD_STYLES: Record<HttpMethod, string> = {
  GET: "bg-emerald-50 text-emerald-700 ring-emerald-200",
  POST: "bg-[#EEF2FF] text-[#2446C4] ring-[#C7D2FE]",
  PATCH: "bg-amber-50 text-amber-700 ring-amber-200",
};

export function MethodBadge({ method }: { method: HttpMethod }) {
  return (
    <span className={`inline-flex shrink-0 items-center rounded-md px-2 py-0.5 font-mono text-[11px] font-bold tracking-wide ring-1 ring-inset ${METHOD_STYLES[method]}`}>
      {method}
    </span>
  );
}

export function EndpointCard({ endpoint }: { endpoint: ApiEndpoint }) {
  return (
    <article id={endpoint.id} className="scroll-mt-28 rounded-2xl border border-slate-200 bg-white p-5 shadow-[0_1px_2px_rgba(15,23,42,0.04)] sm:p-6">
      <header className="flex flex-wrap items-center gap-x-3 gap-y-2">
        <MethodBadge method={endpoint.method} />
        <code className="font-mono text-[15px] font-semibold text-navy">{endpoint.path}</code>
        {endpoint.scope ? (
          <span className="ml-auto inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 font-mono text-[11px] text-slate-600">
            {endpoint.scope}
          </span>
        ) : (
          <span className="ml-auto inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] text-slate-500">cualquier llave</span>
        )}
      </header>
      <h4 className="mt-3 text-[17px] font-semibold tracking-tight text-navy">{endpoint.summary}</h4>
      {endpoint.description && <p className="mt-1.5 text-[14.5px] leading-relaxed text-slate-600">{endpoint.description}</p>}

      {endpoint.params && endpoint.params.length > 0 && (
        <div className="mt-4 overflow-x-auto">
          <table className="w-full min-w-[560px] border-collapse text-left text-[13px]">
            <thead>
              <tr className="border-b border-slate-200 text-[11px] uppercase tracking-wider text-slate-500">
                <th className="py-2 pr-3 font-semibold">Parámetro</th>
                <th className="py-2 pr-3 font-semibold">En</th>
                <th className="py-2 pr-3 font-semibold">Tipo</th>
                <th className="py-2 font-semibold">Descripción</th>
              </tr>
            </thead>
            <tbody>
              {endpoint.params.map((p) => (
                <tr key={p.name} className="border-b border-slate-100 align-top last:border-0">
                  <td className="py-2 pr-3">
                    <code className="font-mono text-[12.5px] text-navy">{p.name}</code>
                    {p.required && <span className="ml-1.5 rounded bg-red-50 px-1 py-0.5 text-[10px] font-semibold text-red-600">obligatorio</span>}
                  </td>
                  <td className="py-2 pr-3 text-slate-500">{p.in}</td>
                  <td className="py-2 pr-3 font-mono text-[12px] text-slate-600">{p.type}</td>
                  <td className="py-2 text-slate-600">{p.description}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {(endpoint.request || endpoint.response) && (
        <div className={`mt-4 grid gap-3 ${endpoint.request && endpoint.response ? "xl:grid-cols-2" : ""}`}>
          {endpoint.request && <CodeBlock label="cuerpo de la petición" code={endpoint.request} />}
          {endpoint.response && <CodeBlock label="respuesta" code={endpoint.response} />}
        </div>
      )}
    </article>
  );
}
