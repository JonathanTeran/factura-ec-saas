import { act, fireEvent, render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";

vi.mock("@/app/(auth)/actions", () => ({
  registerAction: vi.fn(async (_prev: unknown, formData: FormData) => ({
    ok: false,
    message: "La contraseña debe tener una mayúscula",
    fieldErrors: { password: ["La contraseña debe tener una mayúscula"] },
    values: {
      name: String(formData.get("name") ?? ""),
      company_name: String(formData.get("company_name") ?? ""),
      email: String(formData.get("email") ?? ""),
      terms: String(formData.get("terms") ?? ""),
    },
  })),
}));

import { RegisterForm } from "@/app/(auth)/register/register-form";

describe("RegisterForm", () => {
  it("conserva nombre, empresa, correo y términos cuando la acción falla", async () => {
    render(<RegisterForm />);

    const name = screen.getByLabelText("Tu nombre") as HTMLInputElement;
    const company = screen.getByLabelText("Nombre de empresa") as HTMLInputElement;
    const email = screen.getByLabelText("Correo electrónico") as HTMLInputElement;
    const terms = screen.getByRole("checkbox") as HTMLInputElement;
    const password = screen.getByLabelText("Contraseña") as HTMLInputElement;

    name.value = "Ana Pérez";
    company.value = "Andina S.A.";
    email.value = "ana@andina.ec";
    terms.checked = true;
    password.value = "malaclave";

    await act(async () => {
      fireEvent.submit(screen.getByRole("button", { name: "Crear cuenta" }).closest("form")!);
    });

    expect(await screen.findByText("La contraseña debe tener una mayúscula", { selector: "p.text-xs" })).toBeInTheDocument();
    expect(name.value).toBe("Ana Pérez");
    expect(company.value).toBe("Andina S.A.");
    expect(email.value).toBe("ana@andina.ec");
    expect(terms.checked).toBe(true);
    expect(password.value).toBe("");
  });
});
