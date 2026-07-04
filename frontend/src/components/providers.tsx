"use client";

import {
  QueryCache,
  QueryClient,
  QueryClientProvider,
} from "@tanstack/react-query";
import { ReactQueryDevtools } from "@tanstack/react-query-devtools";
import { ThemeProvider } from "next-themes";
import { useState, type PropsWithChildren } from "react";
import { toast } from "sonner";
import { Toaster } from "@/components/ui/sonner";
import { ClientApiError } from "@/lib/api/client";

/** Detecta el 403 de funcionalidad no disponible por plan. */
function isFeatureBlocked(error: unknown): { feature?: string } | null {
  if (error instanceof ClientApiError && error.status === 403) {
    const p = error.payload as { error?: string; feature?: string } | null;
    if (p?.error === "feature_not_available") return { feature: p.feature };
  }
  return null;
}

export function Providers({ children }: PropsWithChildren) {
  const [client] = useState(
    () =>
      new QueryClient({
        // Cuando el plan no incluye una funcionalidad, el backend responde 403.
        // Mostramos un aviso claro con acción para mejorar el plan.
        queryCache: new QueryCache({
          onError: (error) => {
            if (isFeatureBlocked(error)) {
              toast.error("Esta función no está en tu plan actual", {
                description:
                  "Mejora tu plan para acceder a esta funcionalidad.",
                action: {
                  label: "Ver planes",
                  onClick: () => {
                    window.location.href = "/settings/subscription";
                  },
                },
              });
            }
          },
        }),
        defaultOptions: {
          queries: {
            staleTime: 30_000,
            refetchOnWindowFocus: false,
            // No reintentar errores del cliente (4xx): el resultado no cambia.
            retry: (count, error) => {
              if (error instanceof ClientApiError && error.status < 500) {
                return false;
              }
              return count < 1;
            },
          },
        },
      }),
  );

  return (
    <ThemeProvider attribute="class" defaultTheme="system" enableSystem>
      <QueryClientProvider client={client}>
        {children}
        <Toaster richColors position="top-right" />
        {process.env.NODE_ENV === "development" && (
          <ReactQueryDevtools initialIsOpen={false} />
        )}
      </QueryClientProvider>
    </ThemeProvider>
  );
}
