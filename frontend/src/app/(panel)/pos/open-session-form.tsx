"use client";

import { useState } from "react";
import { Loader2, Power } from "lucide-react";
import { toast } from "sonner";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import { EntityCombobox } from "@/components/forms/entity-combobox";
import {
  useCompanies,
  useCompanyBranches,
  useEmissionPoints,
} from "@/lib/api/queries/companies";
import { useOpenPosSession } from "@/lib/api/queries/pos";
import { ClientApiError } from "@/lib/api/client";

function errMessage(err: unknown): string {
  if (err instanceof ClientApiError) {
    const p = err.payload as { message?: string } | null;
    return p?.message ?? err.message;
  }
  return err instanceof Error ? err.message : "Error inesperado";
}

export function OpenSessionForm() {
  const companiesQ = useCompanies();
  const [companyId, setCompanyId] = useState<number | null>(null);
  const [branchId, setBranchId] = useState<number | null>(null);
  const [epId, setEpId] = useState<number | null>(null);
  const [openingAmount, setOpeningAmount] = useState("0");

  const branchesQ = useCompanyBranches(companyId);
  const epsQ = useEmissionPoints(companyId);
  const open = useOpenPosSession();

  const branchEps = epsQ.data?.filter((ep) => ep.branch_id === branchId) ?? [];

  const onSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!companyId || !branchId || !epId) {
      toast.error("Selecciona empresa, sucursal y punto de emisión.");
      return;
    }
    open.mutate(
      {
        company_id: companyId,
        branch_id: branchId,
        emission_point_id: epId,
        opening_amount: Number(openingAmount) || 0,
      },
      {
        onSuccess: () => toast.success("Caja abierta"),
        onError: (e) => toast.error(errMessage(e)),
      },
    );
  };

  return (
    <Card className="w-full max-w-md">
      <CardHeader>
        <CardTitle>Abrir caja</CardTitle>
        <CardDescription>
          Inicia una sesión POS para empezar a vender.
        </CardDescription>
      </CardHeader>
      <CardContent>
        <form onSubmit={onSubmit} className="space-y-4">
          <div className="space-y-2">
            <Label>Empresa</Label>
            <EntityCombobox
              value={companyId}
              onChange={(v) => {
                setCompanyId(typeof v === "number" ? v : null);
                setBranchId(null);
                setEpId(null);
              }}
              options={
                companiesQ.data?.map((c) => ({
                  value: c.id,
                  label: c.legal_name,
                  description: `RUC ${c.ruc}`,
                })) ?? []
              }
              isLoading={companiesQ.isLoading}
              placeholder="Selecciona empresa..."
            />
          </div>
          <div className="space-y-2">
            <Label>Sucursal</Label>
            <EntityCombobox
              value={branchId}
              onChange={(v) => {
                setBranchId(typeof v === "number" ? v : null);
                setEpId(null);
              }}
              options={
                branchesQ.data?.map((b) => ({
                  value: b.id,
                  label: `${b.code} · ${b.name}`,
                  description: b.address ?? undefined,
                })) ?? []
              }
              isLoading={branchesQ.isLoading}
              placeholder={
                companyId ? "Selecciona sucursal..." : "Primero la empresa"
              }
            />
          </div>
          <div className="space-y-2">
            <Label>Punto de emisión</Label>
            <EntityCombobox
              value={epId}
              onChange={(v) => setEpId(typeof v === "number" ? v : null)}
              options={branchEps.map((ep) => ({
                value: ep.id,
                label: ep.code,
                description: ep.description ?? undefined,
              }))}
              placeholder={
                branchId ? "Selecciona punto..." : "Primero la sucursal"
              }
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="opening">Efectivo inicial</Label>
            <Input
              id="opening"
              type="number"
              min="0"
              step="0.01"
              value={openingAmount}
              onChange={(e) => setOpeningAmount(e.target.value)}
            />
          </div>
          <Button
            type="submit"
            className="w-full"
            disabled={open.isPending || !companyId || !branchId || !epId}
          >
            {open.isPending ? (
              <Loader2 className="size-4 animate-spin" />
            ) : (
              <Power className="size-4" />
            )}
            Abrir caja
          </Button>
        </form>
      </CardContent>
    </Card>
  );
}
