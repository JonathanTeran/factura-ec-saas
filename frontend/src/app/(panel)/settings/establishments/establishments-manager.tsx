"use client";

import { useState } from "react";
import { Download, Loader2, Plus } from "lucide-react";
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
  type BranchInput,
  type EmissionPointInput,
} from "@/lib/api/queries/companies";
import { ClientApiError } from "@/lib/api/client";
import { useImportSriEstablishments } from "@/lib/api/queries/sri";

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

function ImportSriButton({ companyId }: { companyId: number }) {
  const importSri = useImportSriEstablishments(companyId);

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
    <Button
      variant="outline"
      size="sm"
      onClick={onImport}
      disabled={importSri.isPending}
    >
      {importSri.isPending ? (
        <Loader2 className="size-4 animate-spin" />
      ) : (
        <Download className="size-4" />
      )}
      Importar del SRI
    </Button>
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
              <ImportSriButton companyId={effectiveCompanyId} />
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
      <div className="flex items-center gap-3 text-sm">
        <span className="font-mono text-xs px-1.5 py-0.5 rounded bg-muted">
          {ep.code}
        </span>
        <span>{ep.description ?? "Sin descripción"}</span>
        {ep.is_active === false && <Badge variant="secondary">Inactivo</Badge>}
      </div>
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
    </li>
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
