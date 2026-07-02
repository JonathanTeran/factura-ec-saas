import type { NextConfig } from "next";
import path from "node:path";
import { fileURLToPath } from "node:url";

// Fija la raíz del workspace a esta carpeta. Sin esto, Next detecta mal la
// raíz por lockfiles en el home (~/package-lock.json) y Turbopack no resuelve
// módulos como tailwindcss.
const projectRoot = path.dirname(fileURLToPath(import.meta.url));

const nextConfig: NextConfig = {
  // Imagen Docker mínima para producción: Next empaqueta solo lo necesario.
  output: "standalone",
  turbopack: {
    root: projectRoot,
  },
};

export default nextConfig;
