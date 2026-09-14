"use client";

import { useEffect, useRef } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { AlertCircle, CheckCircle2, Clock, Loader2 } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { useCapturePayPalOrder } from "@/lib/api/queries/subscription";
import { ClientApiError } from "@/lib/api/client";
import { formatDate, formatMoney } from "@/lib/format";

function errMessage(err: unknown): string {
  if (err instanceof ClientApiError) {
    const p = err.payload as { message?: string } | null;
    return p?.message ?? err.message;
  }
  return err instanceof Error ? err.message : "No pudimos confirmar el pago.";
}

export function PayPalReturn({ orderId }: { orderId: string | null }) {
  const router = useRouter();
  const { mutate, data, error, isError, isSuccess } = useCapturePayPalOrder();
  const started = useRef(false);

  useEffect(() => {
    if (!orderId || started.current) return;
    // Una sola confirmación por visita (el backend igual es idempotente).
    started.current = true;
    mutate(orderId, { onSuccess: () => router.refresh() });
  }, [orderId, mutate, router]);

  if (!orderId) {
    return (
      <StateCard
        icon={<AlertCircle className="size-6 text-destructive" />}
        title="No encontramos el pago"
        body="Este enlace no trae la referencia de PayPal. Si ya pagaste, tu plan se activará automáticamente en unos minutos."
      >
        <Button asChild>
          <Link href="/settings/subscription">Ir a mi suscripción</Link>
        </Button>
      </StateCard>
    );
  }

  if (isError) {
    return (
      <StateCard
        icon={<AlertCircle className="size-6 text-destructive" />}
        title="No se completó el pago"
        body={errMessage(error)}
      >
        <Button variant="outline" onClick={() => mutate(orderId, { onSuccess: () => router.refresh() })}>
          Volver a intentar
        </Button>
        <Button asChild>
          <Link href="/settings/subscription">Elegir otro método</Link>
        </Button>
      </StateCard>
    );
  }

  if (isSuccess && data) {
    const payment = data.data.payment;
    const subscription = payment.subscription;

    if (data.data.status === "processing") {
      return (
        <StateCard
          icon={<Clock className="size-6 text-amber-600" />}
          title="Pago en revisión"
          body={data.message}
        >
          <Button asChild>
            <Link href="/settings/subscription">Ver mi suscripción</Link>
          </Button>
        </StateCard>
      );
    }

    return (
      <StateCard
        icon={<CheckCircle2 className="size-6 text-emerald-600" />}
        title="¡Pago recibido!"
        body={
          subscription?.ends_at
            ? `Tu plan ${subscription.plan?.name ?? ""} está activo hasta el ${formatDate(subscription.ends_at)}. Te enviamos el detalle por correo.`
            : "Tu plan ya está activo. Te enviamos el detalle por correo."
        }
        detail={payment.total_amount != null ? `Total pagado: ${formatMoney(payment.total_amount)}` : undefined}
      >
        <Button asChild>
          <Link href="/dashboard">Ir al panel</Link>
        </Button>
        <Button variant="outline" asChild>
          <Link href="/settings/subscription">Ver mi suscripción</Link>
        </Button>
      </StateCard>
    );
  }

  return (
    <StateCard
      icon={<Loader2 className="size-6 animate-spin text-primary" />}
      title="Confirmando tu pago con PayPal"
      body="Esto toma unos segundos. No cierres esta página."
    />
  );
}

function StateCard({
  icon,
  title,
  body,
  detail,
  children,
}: {
  icon: React.ReactNode;
  title: string;
  body: string;
  detail?: string;
  children?: React.ReactNode;
}) {
  return (
    <Card className="mx-auto max-w-lg">
      <CardContent className="flex flex-col items-center gap-3 py-10 text-center" role="status" aria-live="polite">
        {icon}
        <h2 className="font-display text-xl font-bold tracking-tight">{title}</h2>
        <p className="max-w-md text-sm text-muted-foreground">{body}</p>
        {detail && <p className="text-sm font-medium">{detail}</p>}
        {children && <div className="mt-3 flex flex-wrap justify-center gap-2">{children}</div>}
      </CardContent>
    </Card>
  );
}
