import type { Metadata } from "next";
import { Geist, Geist_Mono } from "next/font/google";
import "./globals.css";
import { Providers } from "@/components/providers";

const geistSans = Geist({
  variable: "--font-geist-sans",
  subsets: ["latin"],
});

const geistMono = Geist_Mono({
  variable: "--font-geist-mono",
  subsets: ["latin"],
});

export const metadata: Metadata = {
  title: {
    default: "AmePhia Facturación",
    template: "%s · AmePhia Facturación",
  },
  description: "Facturación electrónica SRI para Ecuador",
};

export default function RootLayout({
  children,
}: Readonly<{ children: React.ReactNode }>) {
  return (
    <html
      lang="es"
      suppressHydrationWarning
      className={`${geistSans.variable} ${geistMono.variable} h-full antialiased`}
    >
      {/* suppressHydrationWarning: extensiones (ColorZilla, Grammarly…) inyectan
          atributos en <body> antes de que React hidrate; solo silencia atributos
          de este elemento, no de los hijos. */}
      <body
        suppressHydrationWarning
        className="min-h-full bg-background text-foreground"
      >
        <Providers>{children}</Providers>
      </body>
    </html>
  );
}
