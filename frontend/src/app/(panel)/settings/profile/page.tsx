import Link from "next/link";
import { ChevronLeft } from "lucide-react";
import { Button } from "@/components/ui/button";
import { PageHeader } from "@/components/panel/page-header";
import { ProfileForm } from "./profile-form";

export const metadata = { title: "Mi perfil" };

export default function ProfilePage() {
  return (
    <div>
      <PageHeader
        title="Mi perfil"
        description="Datos personales y contraseña"
        actions={
          <Button variant="outline" asChild>
            <Link href="/settings">
              <ChevronLeft className="size-4" />
              Volver
            </Link>
          </Button>
        }
      />
      <div className="p-4 lg:p-6">
        <ProfileForm />
      </div>
    </div>
  );
}
