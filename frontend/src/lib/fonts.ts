import { Bricolage_Grotesque } from "next/font/google";

/** Fuente display de la marca (titulares de landing y auth). Self-hosted por next/font. */
export const bricolage = Bricolage_Grotesque({
  subsets: ["latin"],
  weight: ["700", "800"],
  variable: "--font-bricolage",
  display: "swap",
});
