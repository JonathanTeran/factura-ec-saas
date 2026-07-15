"use client";

import { useMemo, useState } from "react";
import Link from "next/link";
import {
  CalendarCheck,
  CalendarClock,
  Loader2,
  Pencil,
  Plus,
  Receipt,
  UserRound,
} from "lucide-react";
import { toast } from "sonner";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Skeleton } from "@/components/ui/skeleton";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { Tabs, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Field } from "@/components/panel/form";
import { DeleteConfirmButton } from "@/components/forms/delete-confirm-button";
import { useDebouncedValue } from "@/hooks/use-debounced-value";
import { formatDate, formatMoney } from "@/lib/format";
import { cn } from "@/lib/utils";
import { useCustomers } from "@/lib/api/queries/customers";
import type { Customer } from "@/lib/api/types";
import {
  useCreateRefereeMatch,
  useDeleteRefereeMatch,
  useInvoiceRefereeMatches,
  useRefereeChampionships,
  useRefereeClubs,
  useCreateCatalogRequest,
  useRefereeMatches,
  useRefereeProfile,
  useUpdateRefereeMatch,
  useUpdateRefereeProfile,
  type RefereeClub,
  type RefereeMatch,
  type RefereeMatchStatus,
  type RefereeRole,
} from "@/lib/api/queries/referee";

const ROLE_LABELS: Record<RefereeRole, string> = {
  arbitro: "Árbitro central",
  asistente_1: "Asistente 1",
  asistente_2: "Asistente 2",
  cuarto: "Cuarto árbitro",
  var: "VAR",
  comisario: "Comisario",
  delegado: "Delegado",
};

const STATUS_META: Record<
  RefereeMatchStatus,
  { label: string; className: string }
> = {
  pending: {
    label: "Pendiente",
    className: "border-transparent bg-warning/10 text-warning dark:bg-warning/15",
  },
  queued: {
    label: "En proceso SRI",
    className: "border-transparent bg-primary/10 text-primary dark:bg-primary/15",
  },
  invoiced: {
    label: "Facturado",
    className: "border-transparent bg-success/10 text-success dark:bg-success/15",
  },
  blocked_window: {
    label: "Fuera de ventana",
    className: "border-transparent bg-muted text-muted-foreground",
  },
};

type TabValue = "pending" | "invoiced" | "all";

function feeNumber(fee: number | string): number {
  const n = typeof fee === "string" ? Number(fee) : fee;
  return Number.isNaN(n) ? 0 : n;
}

/** Evita el desfase de un día al formatear fechas "YYYY-MM-DD" (UTC-5). */
function formatMatchDate(value: string): string {
  return formatDate(value.includes("T") ? value : `${value}T00:00:00`);
}

function todayLocalISO(): string {
  const d = new Date();
  const mm = String(d.getMonth() + 1).padStart(2, "0");
  const dd = String(d.getDate()).padStart(2, "0");
  return `${d.getFullYear()}-${mm}-${dd}`;
}

function normalizeName(name: string): string {
  return name
    .normalize("NFD")
    .replace(/[̀-ͯ]/g, "")
    .toUpperCase();
}

function isSelectable(m: RefereeMatch): boolean {
  return (
    m.status === "pending" || (m.status === "blocked_window" && m.window.open)
  );
}

export function RefereeView() {
  const profileQuery = useRefereeProfile();
  const matchesQuery = useRefereeMatches();

  const [tab, setTab] = useState<TabValue>("pending");
  const [selected, setSelected] = useState<number[]>([]);
  const [profileDialogOpen, setProfileDialogOpen] = useState(false);
  const [registerOpen, setRegisterOpen] = useState(false);
  const [invoiceOpen, setInvoiceOpen] = useState(false);
  const [editing, setEditing] = useState<RefereeMatch | null>(null);

  const profile = profileQuery.data;
  const matches = useMemo(
    () => matchesQuery.data?.matches ?? [],
    [matchesQuery.data],
  );
  const windowInfo = matchesQuery.data?.window;

  const visibleMatches = useMemo(() => {
    if (tab === "pending") {
      return matches.filter(
        (m) => m.status === "pending" || m.status === "blocked_window",
      );
    }
    if (tab === "invoiced") {
      return matches.filter(
        (m) => m.status === "queued" || m.status === "invoiced",
      );
    }
    return matches;
  }, [matches, tab]);

  const selectedMatches = useMemo(
    () => matches.filter((m) => selected.includes(m.id) && isSelectable(m)),
    [matches, selected],
  );
  const selectedTotal = selectedMatches.reduce(
    (sum, m) => sum + feeNumber(m.fee),
    0,
  );

  const toggleOne = (id: number) =>
    setSelected((prev) =>
      prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id],
    );

  const selectableInView = visibleMatches.filter(isSelectable);
  const allInViewSelected =
    selectableInView.length > 0 &&
    selectableInView.every((m) => selected.includes(m.id));

  const toggleAllInView = () => {
    if (allInViewSelected) {
      setSelected((prev) =>
        prev.filter((id) => !selectableInView.some((m) => m.id === id)),
      );
    } else {
      setSelected((prev) => [
        ...prev,
        ...selectableInView.filter((m) => !prev.includes(m.id)).map((m) => m.id),
      ]);
    }
  };

  const isLoading = profileQuery.isLoading || matchesQuery.isLoading;

  if (isLoading) {
    return (
      <div className="space-y-4">
        <Skeleton className="h-20 w-full rounded-xl" />
        <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
          {Array.from({ length: 4 }).map((_, i) => (
            <Skeleton key={i} className="h-24 rounded-xl" />
          ))}
        </div>
        <Skeleton className="h-64 w-full rounded-xl" />
      </div>
    );
  }

  if (profileQuery.error || matchesQuery.error) {
    const err = (profileQuery.error ?? matchesQuery.error) as Error;
    return (
      <div className="py-10 text-center text-sm text-destructive">
        Error: {err.message}
      </div>
    );
  }

  const needsSetup = !profile?.referee_name;

  return (
    <div className="space-y-4">
      {/* Perfil de árbitro */}
      {needsSetup ? (
        <Card className="border-primary/30 bg-primary/5">
          <CardContent className="p-5">
            <div className="mb-4 flex items-start gap-3">
              <span className="grid size-10 shrink-0 place-items-center rounded-full bg-primary/10 text-primary">
                <UserRound className="size-5" />
              </span>
              <div>
                <h2 className="text-base font-semibold">
                  Configura tu nombre de árbitro
                </h2>
                <p className="mt-0.5 text-sm text-muted-foreground">
                  Ingresa tu nombre oficial, tal como aparece en las
                  designaciones de la FEF. Con él, el sistema detecta
                  automáticamente los partidos que pitas.
                </p>
              </div>
            </div>
            <ProfileForm
              initialName={profile?.referee_name ?? ""}
              initialFee={profile?.referee_default_fee ?? 0}
            />
          </CardContent>
        </Card>
      ) : (
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div className="flex min-w-0 items-center gap-2.5">
            <span className="grid size-9 shrink-0 place-items-center rounded-full bg-primary/10 text-primary">
              <UserRound className="size-4" />
            </span>
            <div className="min-w-0">
              <p className="truncate text-sm font-medium">
                {profile.referee_name}
              </p>
              <p className="text-xs text-muted-foreground">
                Valor por partido: {formatMoney(profile.referee_default_fee)}
              </p>
            </div>
            <Button
              variant="ghost"
              size="sm"
              onClick={() => setProfileDialogOpen(true)}
            >
              <Pencil className="size-3.5" />
              Editar
            </Button>
          </div>
          <Button variant="outline" onClick={() => setRegisterOpen(true)}>
            <Plus className="size-4" />
            Registrar partido
          </Button>
        </div>
      )}

      {needsSetup && (
        <div className="flex justify-end">
          <Button variant="outline" onClick={() => setRegisterOpen(true)}>
            <Plus className="size-4" />
            Registrar partido
          </Button>
        </div>
      )}

      {/* Stats */}
      {profile && (
        <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
          <StatCard label="Pendientes" value={profile.counts.pending} />
          <StatCard label="En proceso" value={profile.counts.queued} />
          <StatCard label="Facturados" value={profile.counts.invoiced} />
          <StatCard
            label="Fuera de ventana"
            value={profile.counts.blocked_window}
          />
        </div>
      )}

      {/* Ventana FEF */}
      {windowInfo &&
        (windowInfo.open_today ? (
          <div className="flex items-start gap-2.5 rounded-lg border border-success/30 bg-success/5 px-4 py-3 text-sm text-success">
            <CalendarCheck className="mt-0.5 size-4 shrink-0" />
            <p>
              La FEF recibe facturas del {windowInfo.start_day} al{" "}
              {windowInfo.end_day} de cada mes. Hoy puedes facturar los
              partidos de meses anteriores.
            </p>
          </div>
        ) : (
          <div className="flex items-start gap-2.5 rounded-lg border border-warning/30 bg-warning/5 px-4 py-3 text-sm text-warning">
            <CalendarClock className="mt-0.5 size-4 shrink-0" />
            <p>
              Ventana cerrada: la FEF recibe del {windowInfo.start_day} al{" "}
              {windowInfo.end_day} de cada mes. Tus pendientes se habilitarán
              el próximo periodo.
            </p>
          </div>
        ))}

      {/* Tabs + tabla */}
      <Tabs value={tab} onValueChange={(v) => setTab(v as TabValue)}>
        <TabsList>
          <TabsTrigger value="pending">Pendientes</TabsTrigger>
          <TabsTrigger value="invoiced">Facturados</TabsTrigger>
          <TabsTrigger value="all">Todos</TabsTrigger>
        </TabsList>
      </Tabs>

      <Card>
        <CardContent className="p-4">
          {visibleMatches.length === 0 ? (
            <div className="py-12 text-center text-sm text-muted-foreground">
              No tienes partidos aún. El sistema los detecta automáticamente
              cuando la FEF publica los resultados, o regístralos manualmente.
            </div>
          ) : (
            <div className="overflow-x-auto">
              <Table>
                <TableHeader>
                  <TableRow className="hover:bg-transparent">
                    <TableHead className="w-[36px]">
                      <input
                        type="checkbox"
                        className="size-4 accent-primary"
                        aria-label="Seleccionar todos"
                        checked={allInViewSelected}
                        disabled={selectableInView.length === 0}
                        onChange={toggleAllInView}
                      />
                    </TableHead>
                    <TableHead>Fecha</TableHead>
                    <TableHead>Partido</TableHead>
                    <TableHead>Campeonato</TableHead>
                    <TableHead>Rol</TableHead>
                    <TableHead className="text-right">Valor</TableHead>
                    <TableHead>Estado</TableHead>
                    <TableHead className="w-[90px]"></TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {visibleMatches.map((m) => (
                    <MatchRow
                      key={m.id}
                      match={m}
                      checked={selected.includes(m.id)}
                      onToggle={() => toggleOne(m.id)}
                      onEdit={() => setEditing(m)}
                    />
                  ))}
                </TableBody>
              </Table>
            </div>
          )}
        </CardContent>
      </Card>

      {/* Barra de selección */}
      {selectedMatches.length > 0 && (
        <div className="sticky bottom-4 z-10 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-border bg-card px-4 py-3 shadow-lg">
          <p className="text-sm">
            <span className="font-semibold">{selectedMatches.length}</span>{" "}
            {selectedMatches.length === 1
              ? "partido seleccionado"
              : "partidos seleccionados"}{" "}
            · Total{" "}
            <span className="font-semibold">{formatMoney(selectedTotal)}</span>
          </p>
          <Button onClick={() => setInvoiceOpen(true)}>
            <Receipt className="size-4" />
            Facturar seleccionados
          </Button>
        </div>
      )}

      {/* Diálogos */}
      {profile && (
        <Dialog open={profileDialogOpen} onOpenChange={setProfileDialogOpen}>
          <DialogContent className="sm:max-w-md">
            <DialogHeader>
              <DialogTitle>Perfil de árbitro</DialogTitle>
              <DialogDescription>
                Tu nombre oficial, tal como aparece en las designaciones de la
                FEF, permite detectar automáticamente tus partidos.
              </DialogDescription>
            </DialogHeader>
            <ProfileForm
              initialName={profile.referee_name ?? ""}
              initialFee={profile.referee_default_fee}
              onSaved={() => setProfileDialogOpen(false)}
            />
          </DialogContent>
        </Dialog>
      )}

      <RegisterMatchDialog
        key={registerOpen ? "reg-open" : "reg-closed"}
        open={registerOpen}
        onOpenChange={setRegisterOpen}
        defaultFee={profile?.referee_default_fee ?? 0}
      />

      <InvoiceDialog
        key={invoiceOpen ? "inv-open" : "inv-closed"}
        open={invoiceOpen}
        onOpenChange={setInvoiceOpen}
        matches={selectedMatches}
        onDone={() => setSelected([])}
      />

      <EditMatchDialog
        key={editing?.id ?? "edit-none"}
        match={editing}
        onClose={() => setEditing(null)}
      />
    </div>
  );
}

function StatCard({ label, value }: { label: string; value: number }) {
  return (
    <div className="rounded-xl border border-border bg-card p-4">
      <p className="text-2xl font-semibold tabular-nums">{value}</p>
      <p className="mt-0.5 text-xs text-muted-foreground">{label}</p>
    </div>
  );
}

function MatchRow({
  match: m,
  checked,
  onToggle,
  onEdit,
}: {
  match: RefereeMatch;
  checked: boolean;
  onToggle: () => void;
  onEdit: () => void;
}) {
  const del = useDeleteRefereeMatch();
  const selectable = isSelectable(m);
  const status = STATUS_META[m.status];
  const editable = m.status === "pending" || m.status === "blocked_window";

  return (
    <TableRow className="hover:bg-muted/50">
      <TableCell>
        <input
          type="checkbox"
          className="size-4 accent-primary disabled:opacity-30"
          aria-label={`Seleccionar ${m.home_club} vs ${m.away_club}`}
          checked={checked && selectable}
          disabled={!selectable}
          onChange={onToggle}
        />
      </TableCell>
      <TableCell className="whitespace-nowrap text-sm">
        {formatMatchDate(m.match_date)}
      </TableCell>
      <TableCell className="text-sm">
        <span className="font-semibold">{m.home_club}</span>
        {m.home_club_city && (
          <span className="text-xs text-muted-foreground"> ({m.home_club_city})</span>
        )}{" "}
        <span className="text-muted-foreground">vs</span>{" "}
        <span className="font-semibold">{m.away_club}</span>
        {m.away_club_city && (
          <span className="text-xs text-muted-foreground"> ({m.away_club_city})</span>
        )}
      </TableCell>
      <TableCell className="max-w-[180px]">
        <span className="block truncate text-sm text-muted-foreground">
          {m.championship ?? "—"}
        </span>
      </TableCell>
      <TableCell>
        <Badge variant="outline">{ROLE_LABELS[m.role] ?? m.role}</Badge>
      </TableCell>
      <TableCell className="whitespace-nowrap text-right text-sm font-medium tabular-nums">
        {formatMoney(feeNumber(m.fee))}
      </TableCell>
      <TableCell>
        <Badge
          className={status.className}
          title={
            m.status === "blocked_window"
              ? (m.window.reason ?? undefined)
              : undefined
          }
        >
          {status.label}
        </Badge>
      </TableCell>
      <TableCell>
        {editable ? (
          <div className="flex items-center justify-end gap-0.5">
            <Button
              variant="ghost"
              size="icon"
              aria-label="Editar"
              onClick={onEdit}
            >
              <Pencil className="size-4" />
            </Button>
            <DeleteConfirmButton
              onConfirm={() => del.mutateAsync(m.id)}
              isPending={del.isPending}
              title={`¿Eliminar el partido ${m.home_club} vs ${m.away_club}?`}
              description="Esta acción no se puede deshacer."
              successMessage="Partido eliminado"
              iconOnly
            />
          </div>
        ) : m.document ? (
          <Link
            href={`/documents/${m.document.id}`}
            className="whitespace-nowrap font-mono text-xs text-primary hover:underline"
          >
            {m.document.number}
          </Link>
        ) : null}
      </TableCell>
    </TableRow>
  );
}

function ProfileForm({
  initialName,
  initialFee,
  onSaved,
}: {
  initialName: string;
  initialFee: number;
  onSaved?: () => void;
}) {
  const update = useUpdateRefereeProfile();
  const [name, setName] = useState(initialName);
  const [fee, setFee] = useState(initialFee > 0 ? String(initialFee) : "");

  const feeValue = Number(fee);
  const canSave = name.trim().length > 0 && !update.isPending;

  const onSubmit = async () => {
    try {
      await update.mutateAsync({
        referee_name: name.trim(),
        referee_default_fee:
          fee !== "" && !Number.isNaN(feeValue) ? feeValue : undefined,
      });
      toast.success("Perfil de árbitro guardado");
      onSaved?.();
    } catch (e) {
      toast.error(
        e instanceof Error ? e.message : "No se pudo guardar el perfil.",
      );
    }
  };

  return (
    <div className="grid gap-4 sm:grid-cols-[1fr_180px_auto] sm:items-end">
      <Field label="Nombre oficial" required htmlFor="referee-name">
        <Input
          id="referee-name"
          value={name}
          onChange={(e) => setName(e.target.value)}
          placeholder="Ej: TERAN JONATHAN"
        />
      </Field>
      <Field label="Valor por partido (USD)" htmlFor="referee-fee">
        <Input
          id="referee-fee"
          type="number"
          min="0"
          step="0.01"
          value={fee}
          onChange={(e) => setFee(e.target.value)}
          placeholder="0.00"
        />
      </Field>
      <Button disabled={!canSave} onClick={onSubmit}>
        {update.isPending && <Loader2 className="size-4 animate-spin" />}
        Guardar
      </Button>
    </div>
  );
}

function EditMatchDialog({
  match,
  onClose,
}: {
  match: RefereeMatch | null;
  onClose: () => void;
}) {
  const update = useUpdateRefereeMatch();
  // El padre remonta este dialog con key={match.id}, así que el estado se
  // inicializa desde props sin efectos.
  const [fee, setFee] = useState(() => (match ? String(feeNumber(match.fee)) : ""));
  const [role, setRole] = useState<RefereeRole>(() => match?.role ?? "arbitro");
  const [notes, setNotes] = useState(() => match?.notes ?? "");

  const feeValue = Number(fee);
  const canSave =
    !!match && fee !== "" && !Number.isNaN(feeValue) && feeValue > 0;

  const onSubmit = async () => {
    if (!match) return;
    try {
      await update.mutateAsync({
        id: match.id,
        fee: feeValue,
        role,
        notes: notes.trim() || undefined,
      });
      toast.success("Partido actualizado");
      onClose();
    } catch (e) {
      toast.error(
        e instanceof Error ? e.message : "No se pudo actualizar el partido.",
      );
    }
  };

  return (
    <Dialog open={!!match} onOpenChange={(v) => !v && onClose()}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Editar partido</DialogTitle>
          {match && (
            <DialogDescription>
              {match.home_club} vs {match.away_club} ·{" "}
              {formatMatchDate(match.match_date)}
            </DialogDescription>
          )}
        </DialogHeader>
        <div className="space-y-4">
          <Field label="Valor (USD)" required htmlFor="edit-fee">
            <Input
              id="edit-fee"
              type="number"
              min="0"
              step="0.01"
              value={fee}
              onChange={(e) => setFee(e.target.value)}
            />
          </Field>
          <Field label="Rol">
            <Select value={role} onValueChange={(v) => setRole(v as RefereeRole)}>
              <SelectTrigger className="w-full">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {(Object.keys(ROLE_LABELS) as RefereeRole[]).map((r) => (
                  <SelectItem key={r} value={r}>
                    {ROLE_LABELS[r]}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </Field>
          <Field label="Notas" htmlFor="edit-notes">
            <Input
              id="edit-notes"
              value={notes}
              onChange={(e) => setNotes(e.target.value)}
              placeholder="Opcional"
            />
          </Field>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={onClose}>
            Cancelar
          </Button>
          <Button disabled={!canSave || update.isPending} onClick={onSubmit}>
            {update.isPending && <Loader2 className="size-4 animate-spin" />}
            Guardar cambios
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function ClubCombobox({
  label,
  value,
  onChange,
  error,
}: {
  label: string;
  value: RefereeClub | null;
  onChange: (club: RefereeClub | null) => void;
  error?: string;
}) {
  const [text, setText] = useState("");
  const [open, setOpen] = useState(false);
  const debounced = useDebouncedValue(text);
  const clubsQuery = useRefereeClubs(debounced, open);
  const clubs = clubsQuery.data ?? [];

  return (
    <Field label={label} required error={error}>
      <div className="relative">
        <Input
          value={value ? value.name : text}
          placeholder="Buscar club..."
          onFocus={() => setOpen(true)}
          onBlur={() => setOpen(false)}
          onChange={(e) => {
            onChange(null);
            setText(e.target.value);
            setOpen(true);
          }}
        />
        {open && !value && (
          <div className="absolute inset-x-0 top-full z-20 mt-1 max-h-44 overflow-y-auto rounded-lg border border-border bg-popover p-1 shadow-md">
            {clubsQuery.isLoading ? (
              <div className="flex justify-center py-3">
                <Loader2 className="size-4 animate-spin text-muted-foreground" />
              </div>
            ) : clubs.length === 0 ? (
              <p className="px-2 py-2 text-xs text-muted-foreground">
                Sin resultados.
              </p>
            ) : (
              clubs.map((c) => (
                <button
                  key={c.id}
                  type="button"
                  className="block w-full rounded-md px-2 py-1.5 text-left text-sm hover:bg-accent"
                  onMouseDown={(e) => {
                    e.preventDefault();
                    onChange(c);
                    setText("");
                    setOpen(false);
                  }}
                >
                  {c.name}
                  {c.city && (
                    <span className="text-muted-foreground"> ({c.city})</span>
                  )}
                </button>
              ))
            )}
          </div>
        )}
      </div>
    </Field>
  );
}

function RegisterMatchDialog({
  open,
  onOpenChange,
  defaultFee,
}: {
  open: boolean;
  onOpenChange: (v: boolean) => void;
  defaultFee: number;
}) {
  const create = useCreateRefereeMatch();
  const championshipsQuery = useRefereeChampionships();
  const championships = championshipsQuery.data ?? [];

  // El padre remonta este dialog con key al abrir/cerrar: estado inicial
  // limpio en cada apertura, sin efectos.
  const [championshipId, setChampionshipId] = useState("");
  const [home, setHome] = useState<RefereeClub | null>(null);
  const [away, setAway] = useState<RefereeClub | null>(null);
  const [date, setDate] = useState("");
  const [role, setRole] = useState<RefereeRole>("arbitro");
  const [fee, setFee] = useState(() => (defaultFee > 0 ? String(defaultFee) : ""));
  const [notes, setNotes] = useState("");

  const sameClub = !!home && !!away && home.id === away.id;
  const feeValue = Number(fee);
  const canSave =
    !!championshipId &&
    !!home &&
    !!away &&
    !sameClub &&
    !!date &&
    fee !== "" &&
    !Number.isNaN(feeValue) &&
    feeValue > 0;

  const onSubmit = async () => {
    if (!canSave || !home || !away) return;
    try {
      await create.mutateAsync({
        championship_id: Number(championshipId),
        home_club_id: home.id,
        away_club_id: away.id,
        match_date: date,
        role,
        fee: feeValue,
        notes: notes.trim() || undefined,
      });
      toast.success("Partido registrado");
      onOpenChange(false);
    } catch (e) {
      toast.error(
        e instanceof Error ? e.message : "No se pudo registrar el partido.",
      );
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Registrar partido</DialogTitle>
          <DialogDescription>
            Registra manualmente un partido que pitaste para poder facturarlo.
          </DialogDescription>
        </DialogHeader>
        <div className="space-y-4">
          <Field label="Campeonato" required>
            <Select value={championshipId} onValueChange={setChampionshipId}>
              <SelectTrigger className="w-full">
                <SelectValue
                  placeholder={
                    championshipsQuery.isLoading
                      ? "Cargando..."
                      : "Selecciona el campeonato"
                  }
                />
              </SelectTrigger>
              <SelectContent>
                {championships.map((c) => (
                  <SelectItem key={c.id} value={String(c.id)}>
                    {c.name}
                    {c.season ? ` · ${c.season}` : ""}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </Field>
          <div className="grid gap-4 sm:grid-cols-2">
            <ClubCombobox
              label="Club local"
              value={home}
              onChange={setHome}
              error={
                sameClub ? "El local y el visitante deben ser distintos." : undefined
              }
            />
            <ClubCombobox label="Club visitante" value={away} onChange={setAway} />
          </div>
          <div className="grid gap-4 sm:grid-cols-2">
            <Field label="Fecha del partido" required htmlFor="match-date">
              <Input
                id="match-date"
                type="date"
                max={todayLocalISO()}
                value={date}
                onChange={(e) => setDate(e.target.value)}
              />
            </Field>
            <Field label="Rol" required>
              <Select
                value={role}
                onValueChange={(v) => setRole(v as RefereeRole)}
              >
                <SelectTrigger className="w-full">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {(Object.keys(ROLE_LABELS) as RefereeRole[]).map((r) => (
                    <SelectItem key={r} value={r}>
                      {ROLE_LABELS[r]}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </Field>
          </div>
          <div className="grid gap-4 sm:grid-cols-2">
            <Field label="Valor (USD)" required htmlFor="match-fee">
              <Input
                id="match-fee"
                type="number"
                min="0"
                step="0.01"
                value={fee}
                onChange={(e) => setFee(e.target.value)}
              />
            </Field>
            <Field label="Notas" htmlFor="match-notes">
              <Input
                id="match-notes"
                value={notes}
                onChange={(e) => setNotes(e.target.value)}
                placeholder="Opcional"
              />
            </Field>
          </div>
        </div>
        <CatalogRequestLink />
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>
            Cancelar
          </Button>
          <Button disabled={!canSave || create.isPending} onClick={onSubmit}>
            {create.isPending && <Loader2 className="size-4 animate-spin" />}
            Registrar
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

/**
 * Enlace + mini-formulario para pedir un campeonato o club que no está en el
 * catálogo (el super admin lo aprueba y se crea). Ver spec §5.5.
 */
function CatalogRequestLink() {
  const [open, setOpen] = useState(false);
  const [type, setType] = useState<"championship" | "club">("championship");
  const [name, setName] = useState("");
  const [comment, setComment] = useState("");
  const request = useCreateCatalogRequest();

  const reset = () => {
    setType("championship");
    setName("");
    setComment("");
  };

  const onSubmit = async () => {
    try {
      await request.mutateAsync({
        type,
        name: name.trim(),
        comment: comment.trim() || undefined,
      });
      toast.success(
        "Solicitud enviada. Te avisaremos cuando esté disponible en el catálogo.",
      );
      setOpen(false);
      reset();
    } catch (e) {
      toast.error(e instanceof Error ? e.message : "No se pudo enviar la solicitud.");
    }
  };

  return (
    <>
      <button
        type="button"
        onClick={() => setOpen(true)}
        className="text-left text-xs text-muted-foreground underline-offset-4 hover:text-foreground hover:underline"
      >
        ¿No encuentras el campeonato o club? Solicítalo aquí
      </button>

      <Dialog
        open={open}
        onOpenChange={(v) => {
          setOpen(v);
          if (!v) reset();
        }}
      >
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Solicitar al catálogo</DialogTitle>
            <DialogDescription>
              Cuéntanos qué falta. Lo revisamos, lo agregamos al catálogo y
              podrás registrar tu partido.
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-4">
            <Field label="¿Qué falta?" required>
              <Select
                value={type}
                onValueChange={(v) => setType(v as "championship" | "club")}
              >
                <SelectTrigger className="w-full">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="championship">Un campeonato</SelectItem>
                  <SelectItem value="club">Un club</SelectItem>
                </SelectContent>
              </Select>
            </Field>
            <Field label="Nombre completo" required htmlFor="cr-name">
              <Input
                id="cr-name"
                value={name}
                onChange={(e) => setName(e.target.value)}
                placeholder={
                  type === "championship"
                    ? "Ej. Campeonato Interparroquial Quito 2026"
                    : "Ej. CLUB DEPORTIVO EJEMPLO"
                }
              />
            </Field>
            <Field label="Comentario" htmlFor="cr-comment">
              <Input
                id="cr-comment"
                value={comment}
                onChange={(e) => setComment(e.target.value)}
                placeholder="Opcional: categoría, provincia, referencia…"
              />
            </Field>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setOpen(false)}>
              Cancelar
            </Button>
            <Button
              disabled={name.trim().length < 3 || request.isPending}
              onClick={onSubmit}
            >
              {request.isPending && <Loader2 className="size-4 animate-spin" />}
              Enviar solicitud
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </>
  );
}

function InvoiceDialog({
  open,
  onOpenChange,
  matches,
  onDone,
}: {
  open: boolean;
  onOpenChange: (v: boolean) => void;
  matches: RefereeMatch[];
  onDone: () => void;
}) {
  const invoice = useInvoiceRefereeMatches();
  const [search, setSearch] = useState("");
  const debounced = useDebouncedValue(search);
  const [customer, setCustomer] = useState<Customer | null>(null);

  const customersQuery = useCustomers(
    { search: debounced || undefined, per_page: 20 },
  );
  const customers = customersQuery.data?.data ?? [];

  // Receptor efectivo: la elección explícita del usuario o, por defecto, el
  // cliente FEDERACION si existe (derivado en render, sin efectos). El padre
  // remonta el dialog con key al abrir/cerrar, así que no hay estado residual.
  const fedDefault = !search
    ? customers.find((c) => normalizeName(c.name).includes("FEDERACION")) ?? null
    : null;
  const effectiveCustomer = customer ?? fedDefault;

  const total = matches.reduce((sum, m) => sum + feeNumber(m.fee), 0);

  const onConfirm = async () => {
    if (!effectiveCustomer) return;
    try {
      const res = await invoice.mutateAsync({
        ids: matches.map((m) => m.id),
        customer_id: effectiveCustomer.id,
      });
      const s = res.data.summary;
      if (s.queued > 0) {
        toast.success(
          `${s.queued} ${s.queued === 1 ? "factura enviada" : "facturas enviadas"} al SRI`,
        );
      }
      if (s.blocked_window > 0) {
        toast.warning(`${s.blocked_window} quedaron fuera de ventana`);
      }
      if (s.draft > 0) {
        toast.info(`${s.draft} en borrador`);
      }
      if (s.skipped > 0) {
        toast.info(`${s.skipped} omitidos`);
      }
      if (s.error > 0) {
        toast.error(`${s.error} con error`);
      }
      onDone();
      onOpenChange(false);
    } catch (e) {
      toast.error(
        e instanceof Error ? e.message : "No se pudieron emitir las facturas.",
      );
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Facturar partidos</DialogTitle>
          <DialogDescription>
            Se emitirá 1 factura por partido ({matches.length}{" "}
            {matches.length === 1 ? "factura" : "facturas"}). El concepto se
            genera automáticamente con equipos, fecha y campeonato.
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-3">
          <div className="rounded-lg bg-muted/50 px-3 py-2 text-sm">
            {matches.length}{" "}
            {matches.length === 1 ? "partido" : "partidos"} · Total{" "}
            <span className="font-semibold">{formatMoney(total)}</span>
          </div>

          <Field label="Cliente receptor" required>
            <div className="space-y-1.5">
              <Input
                value={search}
                onChange={(e) => {
                  setSearch(e.target.value);
                  setCustomer(null);
                }}
                placeholder="Buscar cliente..."
              />
              <div className="max-h-44 overflow-y-auto rounded-lg border border-border p-1">
                {customersQuery.isLoading ? (
                  <div className="flex justify-center py-3">
                    <Loader2 className="size-4 animate-spin text-muted-foreground" />
                  </div>
                ) : customers.length === 0 ? (
                  <p className="px-2 py-2 text-xs text-muted-foreground">
                    Sin clientes. Crea primero el cliente (p. ej. la FEF).
                  </p>
                ) : (
                  customers.map((c) => (
                    <button
                      key={c.id}
                      type="button"
                      onClick={() => setCustomer(c)}
                      className={cn(
                        "block w-full rounded-md px-2 py-1.5 text-left text-sm hover:bg-accent",
                        effectiveCustomer?.id === c.id &&
                          "bg-primary/10 font-medium text-primary hover:bg-primary/10",
                      )}
                    >
                      <span className="block truncate">{c.name}</span>
                      <span className="block font-mono text-[11px] text-muted-foreground">
                        {c.identification_number}
                      </span>
                    </button>
                  ))
                )}
              </div>
            </div>
          </Field>
        </div>

        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>
            Cancelar
          </Button>
          <Button
            disabled={!effectiveCustomer || matches.length === 0 || invoice.isPending}
            onClick={onConfirm}
          >
            {invoice.isPending && <Loader2 className="size-4 animate-spin" />}
            Confirmar y facturar
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
