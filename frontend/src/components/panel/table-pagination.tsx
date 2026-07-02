"use client";

import { ChevronLeft, ChevronRight } from "lucide-react";
import { Button } from "@/components/ui/button";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";

export type PaginationMeta = {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
};

const PER_PAGE_OPTIONS = [10, 20, 50, 100];

/**
 * Pie de paginación estándar para todas las tablas del panel:
 * contador de resultados, selector de tamaño de página y navegación.
 */
export function TablePagination({
  meta,
  page,
  onPageChange,
  perPage,
  onPerPageChange,
  isFetching,
}: {
  meta: PaginationMeta | undefined;
  page: number;
  onPageChange: (page: number) => void;
  perPage: number;
  onPerPageChange: (perPage: number) => void;
  isFetching?: boolean;
}) {
  if (!meta || meta.total === 0) return null;

  const from = (meta.current_page - 1) * meta.per_page + 1;
  const to = Math.min(meta.current_page * meta.per_page, meta.total);

  return (
    <div className="flex flex-col gap-3 border-t border-border pt-3 sm:flex-row sm:items-center sm:justify-between">
      <div className="flex items-center gap-3 text-sm text-muted-foreground">
        <span className="tabular-nums">
          {from}–{to} de {meta.total}
        </span>
        <Select
          value={String(perPage)}
          onValueChange={(v) => {
            onPerPageChange(Number(v));
            onPageChange(1);
          }}
        >
          <SelectTrigger size="sm" className="h-8 w-[110px]">
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            {PER_PAGE_OPTIONS.map((n) => (
              <SelectItem key={n} value={String(n)}>
                {n} por pág.
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </div>

      {meta.last_page > 1 && (
        <div className="flex items-center gap-2">
          <span className="text-sm text-muted-foreground tabular-nums">
            Página {meta.current_page} de {meta.last_page}
          </span>
          <Button
            variant="outline"
            size="sm"
            disabled={page <= 1 || isFetching}
            onClick={() => onPageChange(Math.max(1, page - 1))}
          >
            <ChevronLeft className="size-4" />
            Anterior
          </Button>
          <Button
            variant="outline"
            size="sm"
            disabled={page >= meta.last_page || isFetching}
            onClick={() => onPageChange(page + 1)}
          >
            Siguiente
            <ChevronRight className="size-4" />
          </Button>
        </div>
      )}
    </div>
  );
}
