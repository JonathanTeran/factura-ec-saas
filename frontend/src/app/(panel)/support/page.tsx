"use client";

import { useState } from "react";
import Link from "next/link";
import { Loader2, Plus } from "lucide-react";
import { toast } from "sonner";
import {
  Card,
  CardContent,
} from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Label } from "@/components/ui/label";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { PageHeader } from "@/components/panel/page-header";
import {
  useCreateTicket,
  useSupportTickets,
} from "@/lib/api/queries/support";
import { ClientApiError } from "@/lib/api/client";
import { TablePagination } from "@/components/panel/table-pagination";
import { formatDate } from "@/lib/format";

const PRIORITY_LABELS: Record<string, string> = {
  low: "Baja",
  medium: "Media",
  high: "Alta",
  urgent: "Urgente",
};

const STATUS_LABELS: Record<string, string> = {
  open: "Abierto",
  in_progress: "En proceso",
  waiting_customer: "Esperando respuesta",
  resolved: "Resuelto",
  closed: "Cerrado",
};

const CATEGORY_LABELS: Record<string, string> = {
  general: "General",
  billing: "Facturación",
  technical: "Técnico",
  sri: "SRI",
  other: "Otro",
};

function priorityVariant(p: string): "default" | "secondary" | "destructive" {
  if (p === "urgent" || p === "high") return "destructive";
  return "secondary";
}

function statusVariant(s: string): "default" | "secondary" | "destructive" {
  if (s === "open" || s === "in_progress") return "default";
  if (s === "resolved" || s === "closed") return "secondary";
  return "secondary";
}

function errMessage(err: unknown): string {
  if (err instanceof ClientApiError) {
    const p = err.payload as { message?: string } | null;
    return p?.message ?? err.message;
  }
  return err instanceof Error ? err.message : "Error inesperado";
}

export default function SupportPage() {
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(20);
  const { data, isLoading, isFetching, error } = useSupportTickets({
    page,
    per_page: perPage,
  });
  const create = useCreateTicket();
  const [open, setOpen] = useState(false);
  const [form, setForm] = useState({
    subject: "",
    category: "general",
    priority: "medium",
    message: "",
  });

  const items = data?.data ?? [];
  const meta = data?.meta;

  return (
    <div>
      <PageHeader
        title="Soporte"
        description="Tickets con el equipo de soporte"
        actions={
          <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
              <Button>
                <Plus className="size-4" />
                Nuevo ticket
              </Button>
            </DialogTrigger>
            <DialogContent>
              <DialogHeader>
                <DialogTitle>Crear ticket de soporte</DialogTitle>
                <DialogDescription>
                  Te responderemos lo antes posible.
                </DialogDescription>
              </DialogHeader>
              <div className="grid gap-3">
                <div className="space-y-2">
                  <Label>Asunto</Label>
                  <Input
                    value={form.subject}
                    onChange={(e) =>
                      setForm((f) => ({ ...f, subject: e.target.value }))
                    }
                  />
                </div>
                <div className="grid gap-3 sm:grid-cols-2">
                  <div className="space-y-2">
                    <Label>Categoría</Label>
                    <Select
                      value={form.category}
                      onValueChange={(v) =>
                        setForm((f) => ({ ...f, category: v }))
                      }
                    >
                      <SelectTrigger>
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="general">General</SelectItem>
                        <SelectItem value="billing">Facturación</SelectItem>
                        <SelectItem value="technical">Técnico</SelectItem>
                        <SelectItem value="sri">SRI</SelectItem>
                        <SelectItem value="other">Otro</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                  <div className="space-y-2">
                    <Label>Prioridad</Label>
                    <Select
                      value={form.priority}
                      onValueChange={(v) =>
                        setForm((f) => ({ ...f, priority: v }))
                      }
                    >
                      <SelectTrigger>
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="low">Baja</SelectItem>
                        <SelectItem value="medium">Media</SelectItem>
                        <SelectItem value="high">Alta</SelectItem>
                        <SelectItem value="urgent">Urgente</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                </div>
                <div className="space-y-2">
                  <Label>Descripción</Label>
                  <textarea
                    rows={4}
                    value={form.message}
                    onChange={(e) =>
                      setForm((f) => ({ ...f, message: e.target.value }))
                    }
                    className="w-full rounded-md border bg-transparent px-3 py-2 text-sm"
                  />
                </div>
              </div>
              <DialogFooter>
                <Button variant="outline" onClick={() => setOpen(false)}>
                  Cancelar
                </Button>
                <Button
                  disabled={
                    create.isPending || !form.subject || !form.message
                  }
                  onClick={() =>
                    create.mutate(form, {
                      onSuccess: () => {
                        toast.success("Ticket creado");
                        setOpen(false);
                        setForm({
                          subject: "",
                          category: "general",
                          priority: "medium",
                          message: "",
                        });
                      },
                      onError: (e) => toast.error(errMessage(e)),
                    })
                  }
                >
                  {create.isPending && (
                    <Loader2 className="size-4 animate-spin" />
                  )}
                  Crear
                </Button>
              </DialogFooter>
            </DialogContent>
          </Dialog>
        }
      />
      <div className="p-4 lg:p-6">
        <Card>
          <CardContent className="p-4">
            {isLoading ? (
              <Loader2 className="size-5 animate-spin mx-auto my-12 text-muted-foreground" />
            ) : error ? (
              <div className="text-sm text-destructive py-6 text-center">
                Error: {(error as Error).message}
              </div>
            ) : items.length === 0 ? (
              <p className="text-sm text-muted-foreground py-12 text-center">
                Sin tickets. Crea uno si tienes una consulta.
              </p>
            ) : (
              <div className="space-y-4">
                <div className="relative">
                  {isFetching && (
                    <div className="absolute right-2 top-2 z-10">
                      <Loader2 className="size-4 animate-spin text-muted-foreground" />
                    </div>
                  )}
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Fecha</TableHead>
                    <TableHead>Asunto</TableHead>
                    <TableHead>Categoría</TableHead>
                    <TableHead>Prioridad</TableHead>
                    <TableHead>Estado</TableHead>
                    <TableHead>Usuario</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {items.map((t) => (
                    <TableRow key={t.id}>
                      <TableCell>{formatDate(t.created_at)}</TableCell>
                      <TableCell className="font-medium">
                        <Link
                          href={`/support/${t.id}`}
                          className="hover:underline"
                        >
                          {t.subject}
                        </Link>
                      </TableCell>
                      <TableCell className="text-xs text-muted-foreground">
                        {CATEGORY_LABELS[t.category] ?? t.category}
                      </TableCell>
                      <TableCell>
                        <Badge variant={priorityVariant(t.priority)}>
                          {PRIORITY_LABELS[t.priority] ?? t.priority}
                        </Badge>
                      </TableCell>
                      <TableCell>
                        <Badge variant={statusVariant(t.status)}>
                          {STATUS_LABELS[t.status] ?? t.status}
                        </Badge>
                      </TableCell>
                      <TableCell className="text-sm text-muted-foreground">
                        {t.user?.name ?? "—"}
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
                </div>
                <TablePagination
                  meta={meta}
                  page={page}
                  onPageChange={setPage}
                  perPage={perPage}
                  onPerPageChange={setPerPage}
                  isFetching={isFetching}
                />
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
