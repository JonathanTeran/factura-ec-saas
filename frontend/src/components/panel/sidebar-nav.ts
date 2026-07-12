import {
  LayoutDashboard,
  FileCheck2,
  FileMinus,
  FilePlus2,
  FileBox,
  FileText,
  Truck,
  Users,
  Package,
  Tags,
  ClipboardList,
  PackageOpen,
  BarChart3,
  Settings,
  HelpCircle,
  RefreshCw,
  Coins,
  type LucideIcon,
} from "lucide-react";

export type NavItem = {
  label: string;
  href: string;
  icon: LucideIcon;
};

export type NavGroup = {
  label: string;
  items: NavItem[];
};

/**
 * Solo destinos de navegación. Las acciones de creación (nueva factura, notas)
 * viven en el botón primario del shell, no en el menú.
 *
 * Producto = facturador simple: Inventario, Contabilidad y POS se mantienen en
 * el código pero se ocultan del menú (decisión 12-jul-2026).
 */
export const navGroups: NavGroup[] = [
  {
    label: "Principal",
    items: [{ label: "Dashboard", href: "/", icon: LayoutDashboard }],
  },
  {
    label: "Ventas",
    items: [
      { label: "Facturas", href: "/documents", icon: FileText },
      { label: "Notas de crédito", href: "/credit-notes", icon: FileMinus },
      { label: "Notas de débito", href: "/debit-notes", icon: FilePlus2 },
      { label: "Guías de remisión", href: "/guides", icon: Truck },
      { label: "Cotizaciones", href: "/quotes", icon: ClipboardList },
      { label: "Recurrentes", href: "/recurring-invoices", icon: RefreshCw },
    ],
  },
  {
    label: "Catálogo",
    items: [
      { label: "Clientes", href: "/customers", icon: Users },
      { label: "Productos", href: "/products", icon: Package },
      { label: "Categorías", href: "/categories", icon: Tags },
    ],
  },
  {
    label: "Compras",
    items: [
      { label: "Liquidaciones", href: "/liquidations", icon: FileBox },
      { label: "Retenciones", href: "/retentions", icon: FileCheck2 },
      { label: "Doc. recibidos", href: "/received-documents", icon: PackageOpen },
    ],
  },
  {
    label: "Análisis",
    items: [
      { label: "Reportes", href: "/reports", icon: BarChart3 },
      { label: "Gastos personales", href: "/personal-expenses", icon: Coins },
    ],
  },
  {
    label: "Sistema",
    items: [
      { label: "Soporte", href: "/support", icon: HelpCircle },
      { label: "Configuración", href: "/settings", icon: Settings },
    ],
  },
];
