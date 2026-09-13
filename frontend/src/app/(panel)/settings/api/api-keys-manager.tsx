"use client";

import { useState } from "react";
import Link from "next/link";
import { BookOpen, KeyRound, Loader2, Lock, Plus, RefreshCw, Trash2 } from "lucide-react";
import { toast } from "sonner";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { formatDate } from "@/lib/format";
import {
  errorMessage,
  isFeatureLocked,
  useApiKeys,
  useDeleteApiKey,
  useRotateApiKey,
  useUpdateApiKey,
  type ApiKey,
  type CreatedApiKey,
} from "@/lib/api/queries/api-keys";
import { CreateKeyDialog } from "./create-key-dialog";
import { RevealKeyDialog } from "./reveal-key-dialog";

function UpsellCard() {
  return (
    <Card className="mx-auto max-w-2xl">
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <Lock className="size-4 text-primary" />
          La API está disponible desde el plan Negocio
        </CardTitle>
      </CardHeader>
      <CardContent className="space-y-4">
        <p className="text-sm text-muted-foreground">
          Con la API de Facturón tu ERP, tienda en línea o punto de venta puede emitir comprobantes autorizados por el SRI y consultar lo facturado sin tocar el panel.
        </p>
        <ul className="grid gap-2 text-sm sm:grid-cols-2">
          {[
            "Emitir facturas, notas de crédito y retenciones",
            "Consultar la autorización, el RIDE y el XML",
            "Sincronizar clientes y productos",
            "Llaves por sistema con alcances y límites",
          ].map((item) => (
            <li key={item} className="rounded-lg border border-border bg-card px-3 py-2">
              {item}
            </li>
          ))}
        </ul>
        <div className="flex flex-wrap gap-2">
          <Button asChild>
            <Link href="/settings/subscription">Ver planes</Link>
          </Button>
          <Button variant="outline" asChild>
            <Link href="/docs/api" target="_blank" rel="noopener noreferrer">
              <BookOpen className="size-4" />
              Ver documentación
            </Link>
          </Button>
        </div>
      </CardContent>
    </Card>
  );
}

function StatusBadge({ apiKey }: { apiKey: ApiKey }) {
  if (!apiKey.is_active) return <Badge variant="secondary">Desactivada</Badge>;
  if (apiKey.is_expired) return <Badge variant="destructive">Caducada</Badge>;
  return <Badge className="bg-emerald-600 text-white hover:bg-emerald-600">Activa</Badge>;
}

export function ApiKeysManager() {
  const { data, isLoading, error, refetch } = useApiKeys();
  const update = useUpdateApiKey();
  const rotate = useRotateApiKey();
  const remove = useDeleteApiKey();
  const [createOpen, setCreateOpen] = useState(false);
  const [revealed, setRevealed] = useState<CreatedApiKey | null>(null);

  if (isLoading) {
    return (
      <div className="flex justify-center py-24">
        <Loader2 className="size-6 animate-spin text-muted-foreground" />
      </div>
    );
  }

  if (error && isFeatureLocked(error)) {
    return <UpsellCard />;
  }

  if (error || !data) {
    return (
      <Card className="mx-auto max-w-xl">
        <CardContent className="space-y-3 pt-6 text-sm">
          <p className="text-destructive">{errorMessage(error, "No se pudieron cargar las llaves.")}</p>
          <Button variant="outline" onClick={() => refetch()}>
            Reintentar
          </Button>
        </CardContent>
      </Card>
    );
  }

  const keys = data.api_keys;
  const activeCount = keys.filter((k) => k.is_active).length;

  async function toggleActive(key: ApiKey) {
    try {
      await update.mutateAsync({ id: key.id, is_active: !key.is_active });
      toast.success(key.is_active ? "Llave desactivada." : "Llave activada.");
    } catch (err) {
      toast.error(errorMessage(err));
    }
  }

  async function doRotate(key: ApiKey) {
    if (!window.confirm(`¿Rotar «${key.name}»? La llave actual dejará de funcionar de inmediato.`)) return;
    try {
      const created = await rotate.mutateAsync(key.id);
      setRevealed(created);
    } catch (err) {
      toast.error(errorMessage(err));
    }
  }

  async function doDelete(key: ApiKey) {
    if (!window.confirm(`¿Eliminar «${key.name}»? Las integraciones que la usen dejarán de funcionar.`)) return;
    try {
      await remove.mutateAsync(key.id);
      toast.success("Llave eliminada.");
    } catch (err) {
      toast.error(errorMessage(err));
    }
  }

  return (
    <div className="space-y-4">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <p className="text-sm text-muted-foreground">
          {activeCount} de {data.limits.max_keys} llaves activas · límite del plan: {data.limits.plan_rate_limit} peticiones/min
        </p>
        <Button onClick={() => setCreateOpen(true)} disabled={activeCount >= data.limits.max_keys}>
          <Plus className="size-4" />
          Nueva llave
        </Button>
      </div>

      {keys.length === 0 ? (
        <Card>
          <CardContent className="flex flex-col items-center gap-3 py-14 text-center">
            <span className="flex size-12 items-center justify-center rounded-full bg-primary/10 text-primary">
              <KeyRound className="size-5" />
            </span>
            <div>
              <p className="font-medium">Aún no tienes llaves de API</p>
              <p className="mt-1 max-w-md text-sm text-muted-foreground">
                Crea una por cada sistema que vayas a conectar. Elegirás qué puede hacer y cuándo caduca.
              </p>
            </div>
            <Button onClick={() => setCreateOpen(true)}>
              <Plus className="size-4" />
              Crear mi primera llave
            </Button>
          </CardContent>
        </Card>
      ) : (
        <Card>
          <CardContent className="p-0">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Nombre</TableHead>
                  <TableHead>Prefijo</TableHead>
                  <TableHead>Alcances</TableHead>
                  <TableHead className="text-right">Límite/min</TableHead>
                  <TableHead>Último uso</TableHead>
                  <TableHead>Caduca</TableHead>
                  <TableHead>Estado</TableHead>
                  <TableHead className="text-right">Acciones</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {keys.map((key) => (
                  <TableRow key={key.id}>
                    <TableCell className="font-medium">{key.name}</TableCell>
                    <TableCell>
                      <code className="rounded bg-muted px-1.5 py-0.5 font-mono text-[12px]">{key.key_prefix}…</code>
                    </TableCell>
                    <TableCell>
                      <div className="flex max-w-[260px] flex-wrap gap-1">
                        {key.scopes.map((s) => (
                          <Badge key={s} variant="outline" className="font-mono text-[10.5px]">
                            {s === "*" ? "acceso total" : s}
                          </Badge>
                        ))}
                      </div>
                    </TableCell>
                    <TableCell className="text-right tabular-nums">{key.effective_rate_limit}</TableCell>
                    <TableCell className="text-sm text-muted-foreground">
                      {key.last_used_at ? (
                        <>
                          {formatDate(key.last_used_at)}
                          {key.last_used_ip && <span className="block font-mono text-[11px]">{key.last_used_ip}</span>}
                        </>
                      ) : (
                        "Nunca"
                      )}
                    </TableCell>
                    <TableCell className="text-sm text-muted-foreground">{key.expires_at ? formatDate(key.expires_at) : "Nunca"}</TableCell>
                    <TableCell>
                      <StatusBadge apiKey={key} />
                    </TableCell>
                    <TableCell>
                      <div className="flex justify-end gap-1">
                        <Button variant="ghost" size="sm" onClick={() => doRotate(key)} title="Rotar (nueva credencial)">
                          <RefreshCw className="size-4" />
                          <span className="sr-only">Rotar</span>
                        </Button>
                        <Button variant="ghost" size="sm" onClick={() => toggleActive(key)}>
                          {key.is_active ? "Desactivar" : "Activar"}
                        </Button>
                        <Button variant="ghost" size="sm" className="text-destructive" onClick={() => doDelete(key)} title="Eliminar">
                          <Trash2 className="size-4" />
                          <span className="sr-only">Eliminar</span>
                        </Button>
                      </div>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </CardContent>
        </Card>
      )}

      <Card>
        <CardHeader>
          <CardTitle className="text-base">Inicio rápido</CardTitle>
        </CardHeader>
        <CardContent className="space-y-3">
          <p className="text-sm text-muted-foreground">
            Base URL <code className="rounded bg-muted px-1.5 py-0.5 font-mono text-[12px]">{data.base_url}</code>. Autentica con{" "}
            <code className="rounded bg-muted px-1.5 py-0.5 font-mono text-[12px]">Authorization: Bearer fec_…</code> y consulta la{" "}
            <Link href="/docs/api" target="_blank" rel="noopener noreferrer" className="font-medium text-primary hover:underline">
              documentación completa
            </Link>
            .
          </p>
          <pre className="overflow-x-auto rounded-md bg-[#0b1220] p-3 font-mono text-[12px] leading-relaxed text-slate-100">{`curl ${data.base_url}/me \\
  -H "Authorization: Bearer fec_TU_LLAVE"`}</pre>
        </CardContent>
      </Card>

      <CreateKeyDialog
        open={createOpen}
        onOpenChange={setCreateOpen}
        scopeLabels={data.scopes}
        planRateLimit={data.limits.plan_rate_limit}
        onCreated={(created) => setRevealed(created)}
      />
      <RevealKeyDialog
        plainKey={revealed?.plain_key ?? null}
        keyName={revealed?.api_key.name ?? ""}
        baseUrl={data.base_url}
        onClose={() => setRevealed(null)}
      />
    </div>
  );
}
