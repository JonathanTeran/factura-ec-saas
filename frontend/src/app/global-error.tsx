"use client";

/**
 * Último recurso: falla el propio layout raíz. Debe devolver <html> y <body>
 * y no puede depender de CSS global, por eso los estilos van en línea.
 */
export default function GlobalError({ error, reset }: { error: Error & { digest?: string }; reset: () => void }) {
  return (
    <html lang="es">
      <body
        style={{
          margin: 0,
          minHeight: "100vh",
          display: "flex",
          alignItems: "center",
          justifyContent: "center",
          background: "#0B1220 radial-gradient(70% 60% at 85% -10%, rgba(43,84,228,.38), transparent 60%)",
          color: "#E2E8F0",
          fontFamily: 'ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif',
          textAlign: "center",
          padding: 24,
        }}
      >
        <div style={{ maxWidth: 520 }}>
          <p style={{ fontSize: 22, fontWeight: 700, color: "#fff", margin: 0 }}>Facturón</p>
          <h1 style={{ fontSize: 28, fontWeight: 800, color: "#fff", margin: "24px 0 0", letterSpacing: "-0.02em" }}>Algo salió mal de nuestro lado</h1>
          <p style={{ fontSize: 16, lineHeight: 1.6, color: "#B7C2D6", margin: "14px 0 0" }}>
            Ya quedó registrado. Tus comprobantes y datos no se han perdido. Inténtalo de nuevo en un momento.
          </p>
          {error.digest && (
            <p style={{ fontSize: 13, color: "#8B98AF", margin: "12px 0 0" }}>Código: {error.digest}</p>
          )}
          <div style={{ display: "flex", gap: 10, justifyContent: "center", marginTop: 28, flexWrap: "wrap" }}>
            <button
              type="button"
              onClick={reset}
              style={{ height: 44, padding: "0 22px", borderRadius: 999, border: 0, background: "#2B54E4", color: "#fff", fontWeight: 600, fontSize: 15, cursor: "pointer" }}
            >
              Reintentar
            </button>
            {/* Enlace nativo a propósito: en global-error el router puede estar roto. */}
            {/* eslint-disable-next-line @next/next/no-html-link-for-pages */}
            <a
              href="/"
              style={{ height: 44, padding: "0 22px", borderRadius: 999, border: "1px solid rgba(255,255,255,.22)", color: "#fff", fontWeight: 600, fontSize: 15, display: "inline-flex", alignItems: "center", textDecoration: "none" }}
            >
              Ir al inicio
            </a>
          </div>
          <p style={{ fontSize: 13.5, color: "#8B98AF", marginTop: 32 }}>
            ¿Necesitas ayuda? <a href="mailto:info@facturon.ec" style={{ color: "#C7D2FE" }}>info@facturon.ec</a>
          </p>
        </div>
      </body>
    </html>
  );
}
