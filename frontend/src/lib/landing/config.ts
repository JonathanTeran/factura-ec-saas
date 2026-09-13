/**
 * Configuración de contacto y tiendas de la landing. Las NEXT_PUBLIC_* se
 * incrustan en build; si faltan, valen los defaults acordados.
 */
export function digitsOnly(value: string): string {
  return value.replace(/\D+/g, "");
}

export const APP_URL = (process.env.NEXT_PUBLIC_APP_URL ?? "http://localhost:3000").replace(/\/+$/, "");
export const WHATSAPP_DIGITS = digitsOnly(process.env.NEXT_PUBLIC_WHATSAPP ?? "13347324056");
export const CONTACT_EMAIL = process.env.NEXT_PUBLIC_CONTACT_EMAIL ?? "info@amephia.com";
export const STORE_PLAY_URL = process.env.NEXT_PUBLIC_STORE_PLAY_URL ?? "";
export const STORE_APPSTORE_URL = process.env.NEXT_PUBLIC_STORE_APPSTORE_URL ?? "";
export const WHATSAPP_DEFAULT_TEXT = "Hola, quiero información sobre Facturón";

export function whatsappUrl(text: string = WHATSAPP_DEFAULT_TEXT, digits: string = WHATSAPP_DIGITS): string {
  return `https://wa.me/${digits}?text=${encodeURIComponent(text)}`;
}

/** "+1 334 732 4056" / "+593 99 123 4567"; otros países: "+<dígitos>". */
export function formatWhatsapp(digits: string = WHATSAPP_DIGITS): string {
  if (digits.length === 11 && digits.startsWith("1")) {
    return `+1 ${digits.slice(1, 4)} ${digits.slice(4, 7)} ${digits.slice(7)}`;
  }
  if (digits.length === 12 && digits.startsWith("593")) {
    return `+593 ${digits.slice(3, 5)} ${digits.slice(5, 8)} ${digits.slice(8)}`;
  }
  return `+${digits}`;
}
