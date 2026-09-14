/**
 * Navegación completa del navegador (sale de la app, p. ej. a PayPal).
 * Aislada en un módulo para poder simularla en los tests.
 */
export function redirectTo(url: string): void {
  window.location.assign(url);
}
