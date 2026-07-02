import { requireUser } from "@/lib/server/auth";

export default async function OnboardingLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  await requireUser();
  return <div className="min-h-screen bg-background">{children}</div>;
}
