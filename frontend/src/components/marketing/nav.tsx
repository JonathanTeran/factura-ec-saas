import { getSession } from "@/lib/auth/session";
import { NavClient } from "./nav-client";

/** Lee la cookie de sesión en el servidor y delega la UI al cliente. */
export async function MarketingNav() {
  const session = await getSession();
  return <NavClient isAuthenticated={session !== null} />;
}
