export type StatusMeta = { label: string; className: string };

const AUTHORIZED = "border-transparent bg-success/10 text-success dark:bg-success/15";
const PENDING = "border-transparent bg-warning/10 text-warning dark:bg-warning/15";
const REJECTED = "border-transparent bg-destructive/10 text-destructive dark:bg-destructive/15";
const NEUTRAL = "border-transparent bg-muted text-muted-foreground";

const MAP: Record<string, StatusMeta> = {
  authorized: { label: "Autorizado", className: AUTHORIZED },
  rejected: { label: "Rechazado", className: REJECTED },
  cancelled: { label: "Anulado", className: REJECTED },
  canceled: { label: "Anulado", className: REJECTED },
  pending: { label: "Pendiente", className: PENDING },
  sent: { label: "Enviado", className: PENDING },
  processing: { label: "Procesando", className: PENDING },
  received: { label: "Recibido", className: PENDING },
  draft: { label: "Borrador", className: NEUTRAL },
};

const DOC_TYPES: Record<string, string> = {
  "01": "Factura",
  "03": "Liquidación",
  "04": "Nota de crédito",
  "05": "Nota de débito",
  "06": "Guía de remisión",
  "07": "Retención",
  factura: "Factura",
  nota_credito: "Nota de crédito",
  nota_debito: "Nota de débito",
  guia_remision: "Guía de remisión",
  retencion: "Retención",
};

export function documentTypeLabel(type: string | null | undefined): string {
  if (!type) return "—";
  return DOC_TYPES[type] ?? type;
}

export function documentStatusMeta(status: string | null | undefined): StatusMeta {
  if (!status) return { label: "—", className: NEUTRAL };
  return (
    MAP[status.toLowerCase()] ?? {
      label: status.charAt(0).toUpperCase() + status.slice(1),
      className: NEUTRAL,
    }
  );
}
