"use client";

import Link from "next/link";
import {
  Plus,
  ChevronDown,
  FileText,
  FileMinus,
  FilePlus2,
  FileCheck2,
  Truck,
  FileBox,
  ClipboardList,
  UserPlus,
  Package,
  Settings2,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";

const DOCUMENTS = [
  { label: "Factura", href: "/documents/new", icon: FileText },
  { label: "Nota de crédito", href: "/documents/new?type=04", icon: FileMinus },
  { label: "Nota de débito", href: "/documents/new?type=05", icon: FilePlus2 },
  { label: "Comprobante de retención", href: "/retentions/new", icon: FileCheck2 },
  { label: "Guía de remisión", href: "/guides/new", icon: Truck },
  { label: "Liquidación de compra", href: "/documents/new?type=03", icon: FileBox },
  { label: "Cotización", href: "/quotes/new", icon: ClipboardList },
];

const ENTITIES = [
  { label: "Cliente", href: "/customers/new", icon: UserPlus },
  { label: "Producto", href: "/products/new", icon: Package },
];

export function NewMenu() {
  return (
    <div className="flex items-center">
      {/* Acción rápida principal */}
      <Button
        asChild
        className="hidden rounded-r-none sm:inline-flex"
      >
        <Link href="/documents/new">
          <Plus className="size-4" />
          Nueva factura
        </Link>
      </Button>

      {/* Menú completo */}
      <DropdownMenu>
        <DropdownMenuTrigger asChild>
          <Button
            className="rounded-lg px-2.5 sm:rounded-l-none sm:border-l sm:border-primary-foreground/20"
            aria-label="Crear nuevo"
          >
            <Plus className="size-4 sm:hidden" />
            <ChevronDown className="hidden size-4 sm:block" />
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" className="w-64">
          <DropdownMenuLabel className="text-xs uppercase tracking-wide text-muted-foreground">
            Documentos
          </DropdownMenuLabel>
          {DOCUMENTS.map(({ label, href, icon: Icon }) => (
            <DropdownMenuItem key={href} asChild>
              <Link href={href} className="gap-2.5">
                <Icon className="size-4 text-primary" />
                {label}
              </Link>
            </DropdownMenuItem>
          ))}
          <DropdownMenuSeparator />
          {ENTITIES.map(({ label, href, icon: Icon }) => (
            <DropdownMenuItem key={href} asChild>
              <Link href={href} className="gap-2.5">
                <Icon className="size-4 text-muted-foreground" />
                {label}
              </Link>
            </DropdownMenuItem>
          ))}
          <DropdownMenuSeparator />
          <DropdownMenuItem asChild>
            <Link href="/settings/establishments" className="gap-2.5">
              <Settings2 className="size-4 text-muted-foreground" />
              Configurar emisión
            </Link>
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    </div>
  );
}
