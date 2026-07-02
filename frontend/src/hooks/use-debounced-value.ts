"use client";

import { useEffect, useState } from "react";

/**
 * Devuelve el valor con retraso: evita disparar una petición por tecla
 * en las búsquedas de tablas con muchos registros.
 */
export function useDebouncedValue<T>(value: T, delayMs = 350): T {
  const [debounced, setDebounced] = useState(value);

  useEffect(() => {
    const t = setTimeout(() => setDebounced(value), delayMs);
    return () => clearTimeout(t);
  }, [value, delayMs]);

  return debounced;
}
