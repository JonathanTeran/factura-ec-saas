import { Building2 } from "lucide-react";
import { ThemeToggle } from "./theme-toggle";
import { UserMenu } from "./user-menu";
import { MobileNav } from "./mobile-nav";
import { NewMenu } from "./new-menu";
import type { User } from "@/lib/api/types";

export function Topbar({ user }: { user: User }) {
  return (
    <header className="sticky top-0 z-30 flex h-16 items-center justify-between gap-3 border-b border-border/70 bg-background/80 px-4 backdrop-blur-md lg:px-6">
      <div className="flex min-w-0 items-center gap-2">
        <MobileNav />
        <div className="flex min-w-0 items-center gap-2 rounded-lg border border-border/70 bg-card px-3 py-1.5">
          <Building2 className="size-4 shrink-0 text-muted-foreground" />
          <span className="truncate text-sm font-medium">
            {user.tenant?.name ?? "Mi empresa"}
          </span>
        </div>
      </div>

      <div className="flex items-center gap-1.5">
        <NewMenu />
        <ThemeToggle />
        <UserMenu user={user} />
      </div>
    </header>
  );
}
