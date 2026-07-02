"use client";

import { useState } from "react";
import { Loader2, Send } from "lucide-react";
import { toast } from "sonner";
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import {
  useReplyTicket,
  useSupportTicket,
  useTicketAction,
} from "@/lib/api/queries/support";
import { ClientApiError } from "@/lib/api/client";
import { formatDate } from "@/lib/format";

const STATUS_LABELS: Record<string, string> = {
  open: "Abierto",
  in_progress: "En proceso",
  waiting_customer: "Esperando respuesta",
  resolved: "Resuelto",
  closed: "Cerrado",
};

const PRIORITY_LABELS: Record<string, string> = {
  low: "Baja",
  medium: "Media",
  high: "Alta",
  urgent: "Urgente",
};

const CATEGORY_LABELS: Record<string, string> = {
  general: "General",
  billing: "Facturación",
  technical: "Técnico",
  sri: "SRI",
  other: "Otro",
};

function errMessage(err: unknown): string {
  if (err instanceof ClientApiError) {
    const p = err.payload as { message?: string } | null;
    return p?.message ?? err.message;
  }
  return err instanceof Error ? err.message : "Error inesperado";
}

export function TicketDetail({ id }: { id: number }) {
  const { data, isLoading, error } = useSupportTicket(id);
  const reply = useReplyTicket(id);
  const action = useTicketAction();
  const [message, setMessage] = useState("");

  if (isLoading) {
    return (
      <div className="flex justify-center py-24">
        <Loader2 className="size-6 animate-spin text-muted-foreground" />
      </div>
    );
  }

  if (error || !data) {
    return (
      <div className="text-sm text-destructive">
        Error: {(error as Error)?.message ?? "Ticket no encontrado"}
      </div>
    );
  }

  const t = data;
  const isOpen = t.status === "open" || t.status === "in_progress";

  return (
    <div className="space-y-6">
      <Card>
        <CardHeader className="flex flex-row items-start justify-between">
          <div>
            <CardTitle className="text-xl">{t.subject}</CardTitle>
            <div className="flex gap-2 mt-2 flex-wrap">
              <Badge variant="outline">
                {CATEGORY_LABELS[t.category] ?? t.category}
              </Badge>
              <Badge
                variant={t.priority === "urgent" ? "destructive" : "secondary"}
              >
                {PRIORITY_LABELS[t.priority] ?? t.priority}
              </Badge>
              <Badge variant={isOpen ? "default" : "secondary"}>
                {STATUS_LABELS[t.status] ?? t.status}
              </Badge>
            </div>
            <p className="text-xs text-muted-foreground mt-2">
              {t.user?.name ?? "—"} · {formatDate(t.created_at)}
            </p>
          </div>
          {isOpen ? (
            <Button
              variant="outline"
              size="sm"
              disabled={action.isPending}
              onClick={() =>
                action.mutate(
                  { id: t.id, action: "close" },
                  {
                    onSuccess: () => toast.success("Ticket cerrado"),
                    onError: (e) => toast.error(errMessage(e)),
                  },
                )
              }
            >
              Cerrar ticket
            </Button>
          ) : (
            <Button
              variant="outline"
              size="sm"
              disabled={action.isPending}
              onClick={() =>
                action.mutate(
                  { id: t.id, action: "reopen" },
                  {
                    onSuccess: () => toast.success("Ticket reabierto"),
                    onError: (e) => toast.error(errMessage(e)),
                  },
                )
              }
            >
              Reabrir
            </Button>
          )}
        </CardHeader>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle className="text-base">Conversación</CardTitle>
        </CardHeader>
        <CardContent className="space-y-3">
          {(t.messages ?? []).map((m) => (
            <div
              key={m.id}
              className={`rounded-md p-3 ${
                m.is_admin_reply
                  ? "bg-primary/10 border-l-4 border-primary"
                  : "bg-muted/50"
              }`}
            >
              <div className="flex items-center justify-between mb-1 text-xs text-muted-foreground">
                <span className="font-medium">
                  {m.is_admin_reply ? "Soporte" : m.user?.name ?? "Usuario"}
                </span>
                <span>{formatDate(m.created_at)}</span>
              </div>
              <p className="text-sm whitespace-pre-wrap">{m.message}</p>
            </div>
          ))}
        </CardContent>
      </Card>

      {isOpen && (
        <Card>
          <CardHeader>
            <CardTitle className="text-base">Responder</CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            <textarea
              rows={4}
              value={message}
              onChange={(e) => setMessage(e.target.value)}
              className="w-full rounded-md border bg-transparent px-3 py-2 text-sm"
              placeholder="Escribe tu mensaje..."
            />
            <div className="flex justify-end">
              <Button
                disabled={!message || reply.isPending}
                onClick={() =>
                  reply.mutate(message, {
                    onSuccess: () => {
                      toast.success("Respuesta enviada");
                      setMessage("");
                    },
                    onError: (e) => toast.error(errMessage(e)),
                  })
                }
              >
                {reply.isPending ? (
                  <Loader2 className="size-4 animate-spin" />
                ) : (
                  <Send className="size-4" />
                )}
                Enviar
              </Button>
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  );
}
