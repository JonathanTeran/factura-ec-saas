import Link from "next/link";
import {
  Building2,
  ChevronRight,
  CreditCard,
  Receipt,
  Shield,
  ShieldCheck,
  User,
} from "lucide-react";
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { PageHeader } from "@/components/panel/page-header";

export const metadata = { title: "Configuración" };

const SECTIONS = [
  {
    title: "Firma electrónica",
    description: "Certificado .p12 y caducidad",
    href: "/settings/firma",
    icon: ShieldCheck,
  },
  {
    title: "Establecimientos",
    description: "Sucursales y puntos de emisión SRI",
    href: "/settings/establishments",
    icon: Building2,
  },
  {
    title: "Mi perfil",
    description: "Nombre, contraseña, 2FA",
    href: "/settings/profile",
    icon: User,
  },
  {
    title: "Suscripción",
    description: "Plan, pagos por transferencia",
    href: "/settings/subscription",
    icon: CreditCard,
  },
  {
    title: "Plantillas de documento",
    description: "Email automático y RIDE",
    href: "/settings/templates",
    icon: Receipt,
  },
  {
    title: "Seguridad",
    description: "API keys, 2FA, sesiones",
    href: "/settings/security",
    icon: Shield,
  },
];

export default function SettingsPage() {
  return (
    <div>
      <PageHeader
        title="Configuración"
        description="Ajustes de tu cuenta y empresa"
      />
      <div className="p-4 lg:p-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        {SECTIONS.map((s) => {
          const Icon = s.icon;
          return (
            <Link key={s.href} href={s.href}>
              <Card className="hover:bg-muted/50 transition-colors h-full">
                <CardHeader>
                  <div className="flex items-start justify-between">
                    <Icon className="size-5 text-primary" />
                    <ChevronRight className="size-4 text-muted-foreground" />
                  </div>
                </CardHeader>
                <CardContent>
                  <CardTitle className="text-base mb-1">{s.title}</CardTitle>
                  <p className="text-sm text-muted-foreground">
                    {s.description}
                  </p>
                </CardContent>
              </Card>
            </Link>
          );
        })}
      </div>
    </div>
  );
}
