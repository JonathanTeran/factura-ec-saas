"use client";

import { Loader2 } from "lucide-react";
import { useActivePosSession } from "@/lib/api/queries/pos";
import { OpenSessionForm } from "./open-session-form";
import { ActiveSession } from "./active-session";

export function PosTerminal() {
  const { data: session, isLoading, error } = useActivePosSession();

  if (isLoading) {
    return (
      <div className="flex items-center justify-center h-full">
        <Loader2 className="size-6 animate-spin text-muted-foreground" />
      </div>
    );
  }

  if (error) {
    return (
      <div className="p-6 text-sm text-destructive">
        Error: {(error as Error).message}
      </div>
    );
  }

  if (!session) {
    return (
      <div className="flex items-center justify-center h-full p-6">
        <OpenSessionForm />
      </div>
    );
  }

  return <ActiveSession session={session} />;
}
