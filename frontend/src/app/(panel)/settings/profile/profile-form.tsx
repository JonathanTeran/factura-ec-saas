"use client";

import { useState } from "react";
import { Loader2 } from "lucide-react";
import { toast } from "sonner";
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  useProfile,
  useUpdateProfile,
  useUpdatePassword,
} from "@/lib/api/queries/profile";
import { ClientApiError } from "@/lib/api/client";
import type { User } from "@/lib/api/types";

function errMessage(err: unknown): string {
  if (err instanceof ClientApiError) {
    const p = err.payload as
      | { message?: string; errors?: Record<string, string[]> }
      | null;
    const first = p?.errors ? Object.values(p.errors).flat()[0] : null;
    return first ?? p?.message ?? err.message;
  }
  return err instanceof Error ? err.message : "Error inesperado";
}

export function ProfileForm() {
  const profileQ = useProfile();

  if (profileQ.isLoading) {
    return (
      <div className="flex justify-center py-24">
        <Loader2 className="size-6 animate-spin text-muted-foreground" />
      </div>
    );
  }

  if (profileQ.error || !profileQ.data) {
    return (
      <div className="text-sm text-destructive">
        Error cargando perfil.
      </div>
    );
  }

  return <ProfileInner key={profileQ.data.id} user={profileQ.data} />;
}

function ProfileInner({ user }: { user: User }) {
  const update = useUpdateProfile();
  const updatePass = useUpdatePassword();

  const [name, setName] = useState(user.name);
  const [phone, setPhone] = useState((user as User & { phone?: string }).phone ?? "");

  const [currentPass, setCurrentPass] = useState("");
  const [newPass, setNewPass] = useState("");
  const [confirmPass, setConfirmPass] = useState("");

  return (
    <div className="space-y-6 max-w-2xl">
      <Card>
        <CardHeader>
          <CardTitle className="text-base">Datos personales</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="email">Correo</Label>
            <Input id="email" value={user.email} disabled />
          </div>
          <div className="space-y-2">
            <Label htmlFor="name">Nombre</Label>
            <Input
              id="name"
              value={name}
              onChange={(e) => setName(e.target.value)}
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="phone">Teléfono</Label>
            <Input
              id="phone"
              value={phone}
              onChange={(e) => setPhone(e.target.value)}
            />
          </div>
          <div className="flex justify-end">
            <Button
              disabled={update.isPending}
              onClick={() =>
                update.mutate(
                  { name, phone: phone || undefined },
                  {
                    onSuccess: () => toast.success("Perfil actualizado"),
                    onError: (e) => toast.error(errMessage(e)),
                  },
                )
              }
            >
              {update.isPending && <Loader2 className="size-4 animate-spin" />}
              Guardar
            </Button>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle className="text-base">Cambiar contraseña</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="current">Contraseña actual</Label>
            <Input
              id="current"
              type="password"
              value={currentPass}
              onChange={(e) => setCurrentPass(e.target.value)}
              autoComplete="current-password"
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="new">Nueva contraseña</Label>
            <Input
              id="new"
              type="password"
              value={newPass}
              onChange={(e) => setNewPass(e.target.value)}
              autoComplete="new-password"
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="confirm">Confirmar nueva contraseña</Label>
            <Input
              id="confirm"
              type="password"
              value={confirmPass}
              onChange={(e) => setConfirmPass(e.target.value)}
              autoComplete="new-password"
            />
          </div>
          <div className="flex justify-end">
            <Button
              disabled={
                updatePass.isPending ||
                !currentPass ||
                !newPass ||
                newPass !== confirmPass
              }
              onClick={() =>
                updatePass.mutate(
                  {
                    current_password: currentPass,
                    password: newPass,
                    password_confirmation: confirmPass,
                  },
                  {
                    onSuccess: () => {
                      toast.success("Contraseña actualizada");
                      setCurrentPass("");
                      setNewPass("");
                      setConfirmPass("");
                    },
                    onError: (e) => toast.error(errMessage(e)),
                  },
                )
              }
            >
              {updatePass.isPending && (
                <Loader2 className="size-4 animate-spin" />
              )}
              Cambiar contraseña
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
