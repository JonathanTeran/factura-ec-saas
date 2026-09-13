import { describe, expect, it } from "vitest";
import {
  CONTACT_EMAIL,
  STORE_APPSTORE_URL,
  STORE_PLAY_URL,
  WHATSAPP_DIGITS,
  digitsOnly,
  formatWhatsapp,
  whatsappUrl,
} from "@/lib/landing/config";

describe("config", () => {
  it("tiene los defaults acordados", () => {
    expect(WHATSAPP_DIGITS).toBe("13347324056");
    expect(CONTACT_EMAIL).toBe("info@facturon.ec");
    expect(STORE_PLAY_URL).toBe("");
    expect(STORE_APPSTORE_URL).toBe("");
  });

  it("deja solo dígitos", () => {
    expect(digitsOnly("+1 (334) 732-4056")).toBe("13347324056");
  });

  it("arma la URL de WhatsApp con texto codificado", () => {
    expect(whatsappUrl("Hola, quiero info")).toBe(
      "https://wa.me/13347324056?text=Hola%2C%20quiero%20info",
    );
  });

  it("formatea números de EE. UU. y Ecuador", () => {
    expect(formatWhatsapp("13347324056")).toBe("+1 334 732 4056");
    expect(formatWhatsapp("593991234567")).toBe("+593 99 123 4567");
    expect(formatWhatsapp("4412345678")).toBe("+4412345678");
  });
});
