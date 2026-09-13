"use client";

import { useState } from "react";
import { Building2, Download, Hash, Loader2, Plus, RefreshCw } from "lucide-react";
import { toast } from "sonner";
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Badge } from "@/components/ui/badge";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { EntityCombobox } from "@/components/forms/entity-combobox";
import { DeleteConfirmButton } from "@/components/forms/delete-confirm-button";
import {
  useCompanies,
  useCompanyBranches,
  useCreateBranch,
  useCreateEmissionPoint,
  useDeleteBranch,
  useDeleteEmissionPoint,
  useEmissionPointSequentials,
  useUpdateEmissionPointSequentials,
  type BranchInput,
  type EmissionPointInput,
} from "@/lib/api/queries/companies";
import { ClientApiError } from "@/lib/api/client";
import {
  useImportSriEstablishments,
  useSriEstablishments,
} from "@/lib/api/queries/sri";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";

function errMessage(err: unknown): string {
  if (err instanceof ClientApiError) {
    const p = err.payload as
      | { message?: string; errors?: Record<string, string[]> }
      | null;
    const first = p?.errors ? Object.values(p.errors).flat()[0] : null;
    return first ?? p?.message ?? err.message;
  }
  return err instanceof Error ? err.message : "Error inesperado";
}

function SriEstablishmentsDialog({ companyId }: { companyId: number }) {
  const [open, setOpen] = useState(false);
  const sriQ = useSriEstablishments(companyId, open);
  const importSri = useImportSriEstablishments(companyId);
  const data = sriQ.data;
  const pending = data?.pending_import ?? 0;

  const onImport = () => {
    importSri.mutate(undefined, {
      onSuccess: (res) => {
        const n = res.data.imported.length;
        if (n > 0) {
          toast.success(`Se importaron ${n} establecimiento(s) desde el SRI.`);
        } else {
          toast.info("No hay establecimientos nuevos para importar.");
        }
      },
      onError: (e) => toast.error(errMessage(e)),
    });
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button variant="outline" size="sm">
          <Building2 className="size-4" />
          Ver en el SRI
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-2xl">
        <DialogHeader>
          <DialogTitle>Establecimientos registrados en el SRI</DialogTitle>
          <DialogDescription>
            {data
              ? `RUC ${data.company.ruc} · ${data.company.business_name}. Los que ya tienes configurados aparecen marcados.`
              : "Consultamos el catastro público del SRI con el RUC de la empresa."}
          </DialogDescription>
        </DialogHeader>

        {sriQ.isLoading ? (
          <div className="flex justify-center py-10">
            <Loader2 className="size-5 animate-spin text-muted-foreground" />
          </div>
        ) : sriQ.error ? (
          <div className="space-y-3 py-6 text-center text-sm">
            <p className="text-destructive">{errMessage(sriQ.error)}</p>
            <Button variant="outline" size="sm" onClick={() => sriQ.refetch()}>
              <RefreshCw className="size-4" /> Reintentar
            </Button>
          </div>
        ) : (
          <div className="overflow-x-auto rounded-lg border">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead className="w-16">Código</TableHead>
                  <TableHead>Nombre / dirección</TableHead>
                  <TableHead>Estado SRI</TableHead>
                  <TableHead>En Facturón</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {(data?.establishments ?? []).map((e) => (
                  <TableRow key={e.code}>
                    <TableCell className="font-mono text-xs">{e.code}</TableCell>
                    <TableCell>
                      <div className="font-medium">
                        {e.trade_name || (e.is_main ? "Matriz" : `Establecimiento ${e.code}`)}
                        {e.is_main && (
                          <Badge variant="secondary" className="ml-2">Matriz</Badge>
                        )}
                      </div>
                      {e.address && (
                        <div className="text-xs text-muted-foreground">{e.address}</div>
                      )}
                    </TableCell>
                    <TableCell>
                      <Badge variant={e.is_open ? "default" : "destructive"}>
                        {e.is_open ? "Abierto" : "Cerrado"}
                      </Badge>
                    </TableCell>
                    <TableCell>
                      {e.configured ? (
                        <span className="text-sm text-success">
                          Configurado{e.branch_is_active === false ? " (inactivo)" : ""}
                        </span>
                      ) : e.is_open ? (
                        <span className="text-sm text-muted-foreground">Falta importar</span>
                      ) : (
                        <span className="text-sm text-muted-foreground">—</span>
                      )}
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </div>
        )}

        <DialogFooter className="sm:justify-between">
          <p className="text-xs text-muted-foreground">
            {data
              ? pending > 0
                ? `${pending} establecimiento(s) abiertos aún no están en Facturón.`
                : "Todos los establecimientos abiertos ya están configurados."
              : ""}
          </p>
          <div className="flex gap-2">
            <Button variant="outline" onClick={() => setOpen(false)}>
              Cerrar
            </Button>
            <Button onClick={onImport} disabled={importSri.isPending || !data || pending === 0}>
              {importSri.isPending ? (
                <Loader2 className="size-4 animate-spin" />
              ) : (
                <Download className="size-4" />
              )}
              Importar faltantes
            </Button>
          </div>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

export function EstablishmentsManager() {
  const companiesQ = useCompanies();
  const [companyId, setCompanyId] = useState<number | null>(null);

  const companies = companiesQ.data ?? [];
  const effectiveCompanyId =
    companyId ?? (companies.length > 0 ? companies[0].id : null);
  const branchesQ = useCompanyBranches(effectiveCompanyId);

  return (
    <div className="space-y-6">
      <Card>
        <CardHeader>
          <CardTitle className="text-base">Empresa emisora</CardTitle>
        </CardHeader>
        <CardContent>
          <EntityCombobox
            value={effectiveCompanyId}
            onChange={(v) =>
              setCompanyId(typeof v === "number" ? v : null)
            }
            options={companies.map((c) => ({
              value: c.id,
              label: c.legal_name,
              description: `RUC ${c.ruc} · ${
                c.sri_environment === "2" ? "Producción" : "Pruebas"
              }`,
            }))}
            isLoading={companiesQ.isLoading}
            placeholder="Selecciona empresa..."
          />
          {companies.length === 0 && !companiesQ.isLoading && (
            <p className="text-sm text-muted-foreground mt-3">
              Aún no tienes empresas configuradas. Termina el onboarding primero.
            </p>
          )}
        </CardContent>
      </Card>

      {effectiveCompanyId && (
        <Card>
          <CardHeader className="flex flex-row items-center justify-between">
            <CardTitle className="text-base">Establecimientos</CardTitle>
            <div className="flex items-center gap-2">
              <SriEstablishmentsDialog companyId={effectiveCompanyId} />
              <NewBranchDialog companyId={effectiveCompanyId} />
            </div>
          </CardHeader>
          <CardContent className="space-y-4">
            {branchesQ.isLoading ? (
              <div className="flex justify-center py-8">
                <Loader2 className="size-5 animate-spin text-muted-foreground" />
              </div>
            ) : (branchesQ.data ?? []).length === 0 ? (
              <p className="text-sm text-muted-foreground py-4 text-center">
                Sin establecimientos. Crea uno para emitir documentos.
              </p>
            ) : (
              (branchesQ.data ?? []).map((branch) => (
                <BranchCard
                  key={branch.id}
                  branch={branch}
                  companyId={effectiveCompanyId}
                />
              ))
            )}
          </CardContent>
        </Card>
      )}
    </div>
  );
}

function BranchCard({
  branch,
  companyId,
}: {
  branch: import("@/lib/api/types").Branch;
  companyId: number;
}) {
  const del = useDeleteBranch(companyId);
  const eps = branch.emission_points ?? [];

  return (
    <div className="rounded-lg border bg-card p-4 space-y-3">
      <div className="flex items-start justify-between">
        <div>
          <div className="flex items-center gap-2">
            <span className="font-mono text-xs px-1.5 py-0.5 rounded bg-muted">
              {branch.code}
            </span>
            <h3 className="font-semibold">{branch.name}</h3>
            {branch.is_main && <Badge variant="default">Principal</Badge>}
            {branch.is_active === false && (
              <Badge variant="secondary">Inactivo</Badge>
            )}
          </div>
          {branch.address && (
            <p className="text-sm text-muted-foreground mt-1">
              {branch.address}
            </p>
          )}
        </div>
        <div className="flex items-center gap-2">
          <NewEmissionPointDialog branchId={branch.id} />
          <DeleteConfirmButton
            onConfirm={async () => {
              await del.mutateAsync(branch.id);
            }}
            isPending={del.isPending}
            title={`Eliminar establecimiento ${branch.code}?`}
            description="También se eliminarán sus puntos de emisión. Si tienen secuenciales en uso, la operación puede fallar."
            successMessage="Establecimiento eliminado"
            iconOnly
          />
        </div>
      </div>

      <div className="border-t pt-3">
        <div className="text-xs font-semibold uppercase text-muted-foreground mb-2">
          Puntos de emisión
        </div>
        {eps.length === 0 ? (
          <p className="text-sm text-muted-foreground">
            Sin puntos. Crea uno para empezar a numerar documentos.
          </p>
        ) : (
          <ul className="space-y-1">
            {eps.map((ep) => (
              <EmissionPointRow key={ep.id} ep={ep} />
            ))}
          </ul>
        )}
      </div>
    </div>
  );
}

function EmissionPointRow({
  ep,
}: {
  ep: import("@/lib/api/types").EmissionPoint;
}) {
  const del = useDeleteEmissionPoint(ep.branch_id);
  return (
    <li className="flex items-center justify-between rounded-md px-2 py-1.5 hover:bg-muted/50">
      <div className="flex flex-wrap items-center gap-3 text-sm">
        <span className="font-mono text-xs px-1.5 py-0.5 rounded bg-muted">
          {ep.code}
        </span>
        <span>{ep.description ?? "Sin descripción"}</span>
        {ep.is_active === false && <Badge variant="secondary">Inactivo</Badge>}
        {ep.next_invoice_number && (
          <span className="text-xs text-muted-foreground">
            Siguiente factura:{" "}
            <span className="font-mono text-foreground">{ep.next_invoice_number}</span>
          </span>
        )}
      </div>
      <div className="flex items-center gap-1">
        <SequentialsDialog ep={ep} />
      <DeleteConfirmButton
        onConfirm={async () => {
          await del.mutateAsync(ep.id);
        }}
        isPending={del.isPending}
        title={`Eliminar punto ${ep.code}?`}
        description="Si tiene secuenciales emitidos, la operación puede fallar."
        successMessage="Punto eliminado"
        iconOnly
      />
      </div>
    </li>
  );
}

function SequentialsDialog({
  ep,
}: {
  ep: import("@/lib/api/types").EmissionPoint;
}) {
  const [open, setOpen] = useState(false);
  const [draft, setDraft] = useState<Record<string, string>>({});
  const seqQ = useEmissionPointSequentials(ep.branch_id, ep.id, open);
  const update = useUpdateEmissionPointSequentials(ep.branch_id, ep.id);
  const rows = seqQ.data?.sequentials ?? [];
  const series = seqQ.data?.emission_point.series ?? ep.series ?? "";

  const valueFor = (docType: string, current: number) =>
    draft[docType] ?? String(current);

  const changed = rows.filter(
    (r) => draft[r.document_type] !== undefined && Number(draft[r.document_type]) !== r.current_number,
  );

  const onSave = () => {
    update.mutate(
      {
        sequentials: changed.map((r) => ({
          document_type: r.document_type,
          last_number: Math.max(0, Math.floor(Number(draft[r.document_type]) || 0)),
        })),
      },
      {
        onSuccess: () => {
          toast.success("Secuenciales actualizados");
          setDraft({});
          setOpen(false);
        },
        onError: (e) => toast.error(errMessage(e)),
      },
    );
  };

  return (
    <Dialog
      open={open}
      onOpenChange={(v) => {
        setOpen(v);
        if (!v) setDraft({});
      }}
    >
      <DialogTrigger asChild>
        <Button variant="ghost" size="sm" title="Ver y ajustar secuenciales">
          <Hash className="size-4" /> Secuenciales
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-2xl">
        <DialogHeader>
          <DialogTitle>Secuenciales del punto {series || ep.code}</DialogTitle>
          <DialogDescription>
            Cada tipo de comprobante lleva su propia numeración. Escribe el{" "}
            <strong>último número usado</strong> (por ejemplo, el de tu sistema anterior) y el
            siguiente comprobante saldrá con +1. Nunca se puede retroceder por debajo de lo ya emitido.
          </DialogDescription>
        </DialogHeader>

        {seqQ.isLoading ? (
          <div className="flex justify-center py-10">
            <Loader2 className="size-5 animate-spin text-muted-foreground" />
          </div>
        ) : seqQ.error ? (
          <p className="py-6 text-center text-sm text-destructive">{errMessage(seqQ.error)}</p>
        ) : (
          <div className="overflow-x-auto rounded-lg border">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Comprobante</TableHead>
                  <TableHead className="w-36">Último usado</TableHead>
                  <TableHead>Siguiente</TableHead>
                  <TableHead className="text-right">Emitidos</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {rows.map((r) => {
                  const val = valueFor(r.document_type, r.current_number);
                  const n = Math.max(0, Math.floor(Number(val) || 0));
                  const belowIssued = n < r.last_issued;
                  return (
                    <TableRow key={r.document_type}>
                      <TableCell className="font-medium">{r.document_type_label}</TableCell>
                      <TableCell>
                        <Input
                          inputMode="numeric"
                          className="h-9 font-mono"
                          value={val}
                          onChange={(e) =>
                            setDraft((d) => ({ ...d, [r.document_type]: e.target.value.replace(/\D+/g, "") }))
                          }
                          aria-invalid={belowIssued || undefined}
                        />
                        {belowIssued && (
                          <p className="mt-1 text-xs text-destructive">
                            Ya emitiste hasta {r.last_issued}.
                          </p>
                        )}
                      </TableCell>
                      <TableCell className="font-mono text-sm">
                        {series}-{String(n + 1).padStart(9, "0")}
                      </TableCell>
                      <TableCell className="text-right tabular-nums text-muted-foreground">
                        {r.documents_count}
                      </TableCell>
                    </TableRow>
                  );
                })}
              </TableBody>
            </Table>
          </div>
        )}

        <DialogFooter>
          <Button variant="outline" onClick={() => setOpen(false)}>
            Cerrar
          </Button>
          <Button
            onClick={onSave}
            disabled={
              update.isPending ||
              changed.length === 0 ||
              rows.some((r) => Number(valueFor(r.document_type, r.current_number)) < r.last_issued)
            }
          >
            {update.isPending && <Loader2 className="size-4 animate-spin" />}
            Guardar cambios
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function NewBranchDialog({ companyId }: { companyId: number }) {
  const [open, setOpen] = useState(false);
  const [form, setForm] = useState<BranchInput>({
    company_id: companyId,
    code: "",
    name: "",
    address: "",
    is_main: false,
    is_active: true,
  });
  const create = useCreateBranch();

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button size="sm">
          <Plus className="size-4" /> Nuevo establecimiento
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Nuevo establecimiento</DialogTitle>
          <DialogDescription>
            El código de 3 dígitos es el que asigna el SRI (ej: 001, 002).
          </DialogDescription>
        </DialogHeader>
        <div className="grid gap-3">
          <div className="space-y-2">
            <Label htmlFor="branch-code">Código (3 dígitos)</Label>
            <Input
              id="branch-code"
              value={form.code}
              maxLength={3}
              onChange={(e) =>
                setForm((f) => ({ ...f, code: e.target.value }))
              }
              placeholder="001"
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="branch-name">Nombre</Label>
            <Input
              id="branch-name"
              value={form.name}
              onChange={(e) =>
                setForm((f) => ({ ...f, name: e.target.value }))
              }
              placeholder="Matriz"
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="branch-address">Dirección</Label>
            <Input
              id="branch-address"
              value={form.address}
              onChange={(e) =>
                setForm((f) => ({ ...f, address: e.target.value }))
              }
            />
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => setOpen(false)}>
            Cancelar
          </Button>
          <Button
            disabled={create.isPending || !form.code || !form.name || !form.address}
            onClick={() =>
              create.mutate(
                { ...form, company_id: companyId },
                {
                  onSuccess: () => {
                    toast.success("Establecimiento creado");
                    setOpen(false);
                    setForm({
                      company_id: companyId,
                      code: "",
                      name: "",
                      address: "",
                      is_main: false,
                      is_active: true,
                    });
                  },
                  onError: (e) => toast.error(errMessage(e)),
                },
              )
            }
          >
            {create.isPending && <Loader2 className="size-4 animate-spin" />}
            Crear
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function NewEmissionPointDialog({ branchId }: { branchId: number }) {
  const [open, setOpen] = useState(false);
  const [form, setForm] = useState<EmissionPointInput>({
    branch_id: branchId,
    code: "",
    description: "",
    is_active: true,
  });
  const create = useCreateEmissionPoint();

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button variant="outline" size="sm">
          <Plus className="size-4" /> Punto
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Nuevo punto de emisión</DialogTitle>
          <DialogDescription>
            El código de 3 dígitos es la serie SRI (ej: 001, 002).
          </DialogDescription>
        </DialogHeader>
        <div className="grid gap-3">
          <div className="space-y-2">
            <Label htmlFor="ep-code">Código (3 dígitos)</Label>
            <Input
              id="ep-code"
              value={form.code}
              maxLength={3}
              onChange={(e) =>
                setForm((f) => ({ ...f, code: e.target.value }))
              }
              placeholder="001"
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="ep-desc">Descripción (opcional)</Label>
            <Input
              id="ep-desc"
              value={form.description ?? ""}
              onChange={(e) =>
                setForm((f) => ({ ...f, description: e.target.value }))
              }
              placeholder="Punto principal"
            />
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => setOpen(false)}>
            Cancelar
          </Button>
          <Button
            disabled={create.isPending || !form.code}
            onClick={() =>
              create.mutate(
                { ...form, branch_id: branchId },
                {
                  onSuccess: () => {
                    toast.success("Punto de emisión creado");
                    setOpen(false);
                    setForm({
                      branch_id: branchId,
                      code: "",
                      description: "",
                      is_active: true,
                    });
                  },
                  onError: (e) => toast.error(errMessage(e)),
                },
              )
            }
          >
            {create.isPending && <Loader2 className="size-4 animate-spin" />}
            Crear
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
