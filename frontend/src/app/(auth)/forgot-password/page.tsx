import Link from "next/link";
import { ForgotForm } from "./forgot-form";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";

export const metadata = { title: "Recuperar contraseña" };

export default function ForgotPasswordPage() {
  return (
    <Card>
      <CardHeader className="space-y-1">
        <CardTitle className="text-2xl">Recuperar contraseña</CardTitle>
        <CardDescription>
          Ingresa tu correo y te enviaremos un enlace de recuperación
        </CardDescription>
      </CardHeader>
      <CardContent className="space-y-4">
        <ForgotForm />
        <div className="text-center text-sm">
          <Link
            href="/login"
            className="text-muted-foreground hover:text-foreground hover:underline"
          >
            Volver al inicio de sesión
          </Link>
        </div>
      </CardContent>
    </Card>
  );
}
