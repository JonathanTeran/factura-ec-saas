"use client";

import { useId, useState } from "react";
import { useFormStatus } from "react-dom";
import { ArrowRight, Check, Eye, EyeOff, Loader2 } from "lucide-react";
import { PASSWORD_RULES, passwordChecks } from "@/lib/auth/password-rules";
import { cn } from "@/lib/utils";

const INPUT =
  "h-12 w-full rounded-xl border bg-white px-4 text-[15px] text-slate-900 outline-none transition-[border-color,box-shadow] placeholder:text-slate-400 focus:border-brand focus:ring-4 focus:ring-brand/15 disabled:opacity-60";

function inputState(invalid: boolean) {
  return invalid ? "border-red-400 focus:border-red-500 focus:ring-red-500/15" : "border-slate-200 hover:border-slate-300";
}

export function FieldError({ errors }: { errors?: string[] }) {
  if (!errors || errors.length === 0) return null;
  return <p className="text-xs font-medium text-red-600">{errors[0]}</p>;
}

export function FieldLabel({ htmlFor, children, aside }: { htmlFor: string; children: React.ReactNode; aside?: React.ReactNode }) {
  return (
    <div className="flex items-center justify-between gap-3">
      <label htmlFor={htmlFor} className="text-[13px] font-semibold text-slate-700">
        {children}
      </label>
      {aside}
    </div>
  );
}

type TextFieldProps = React.ComponentProps<"input"> & {
  label: string;
  errors?: string[];
  aside?: React.ReactNode;
};

export function TextField({ label, errors, aside, className, id, ...props }: TextFieldProps) {
  const autoId = useId();
  const fieldId = id ?? autoId;
  const invalid = Boolean(errors?.length);
  return (
    <div className={cn("space-y-1.5", className)}>
      <FieldLabel htmlFor={fieldId} aside={aside}>{label}</FieldLabel>
      <input id={fieldId} aria-invalid={invalid || undefined} className={cn(INPUT, inputState(invalid))} {...props} />
      <FieldError errors={errors} />
    </div>
  );
}

type PasswordFieldProps = Omit<React.ComponentProps<"input">, "type"> & {
  label: string;
  errors?: string[];
  aside?: React.ReactNode;
  /** Muestra la checklist de la política de contraseñas mientras se escribe. */
  showRules?: boolean;
};

export function PasswordField({ label, errors, aside, showRules = false, className, id, onChange, ...props }: PasswordFieldProps) {
  const autoId = useId();
  const fieldId = id ?? autoId;
  const [show, setShow] = useState(false);
  const [value, setValue] = useState("");
  const invalid = Boolean(errors?.length);
  return (
    <div className={cn("space-y-1.5", className)}>
      <FieldLabel htmlFor={fieldId} aside={aside}>{label}</FieldLabel>
      <div className="relative">
        <input
          id={fieldId}
          type={show ? "text" : "password"}
          aria-invalid={invalid || undefined}
          className={cn(INPUT, "pr-12", inputState(invalid))}
          onChange={(e) => {
            setValue(e.target.value);
            onChange?.(e);
          }}
          {...props}
        />
        <button
          type="button"
          onClick={() => setShow((v) => !v)}
          aria-label={show ? "Ocultar contraseña" : "Mostrar contraseña"}
          className="absolute inset-y-0 right-0 flex w-12 items-center justify-center text-slate-400 transition-colors hover:text-slate-700"
        >
          {show ? <EyeOff className="size-[18px]" /> : <Eye className="size-[18px]" />}
        </button>
      </div>
      {showRules && <PasswordRules password={value} />}
      <FieldError errors={errors} />
    </div>
  );
}

export function PasswordRules({ password }: { password: string }) {
  const checks = passwordChecks(password);
  return (
    <ul className="grid grid-cols-2 gap-x-3 gap-y-1.5 pt-1" aria-label="Requisitos de la contraseña">
      {PASSWORD_RULES.map((rule) => {
        const ok = checks[rule.id];
        return (
          <li key={rule.id} className={cn("flex items-center gap-1.5 text-[11px] font-medium transition-colors", ok ? "text-emerald-600" : "text-slate-400")}>
            <span className={cn("flex size-4 shrink-0 items-center justify-center rounded-full border transition-colors", ok ? "border-emerald-500 bg-emerald-500 text-white" : "border-slate-300")}>
              {ok && <Check className="size-2.5" aria-hidden="true" />}
            </span>
            {rule.label}
          </li>
        );
      })}
    </ul>
  );
}

export function CheckboxField({ name, label, defaultChecked, errors }: { name: string; label: React.ReactNode; defaultChecked?: boolean; errors?: string[] }) {
  return (
    <div className="space-y-1.5">
      <label className="group flex cursor-pointer items-start gap-3 text-[13px] leading-relaxed text-slate-600">
        <input type="checkbox" name={name} defaultChecked={defaultChecked} className="peer sr-only" />
        <span className="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-md border border-slate-300 bg-white text-white transition-colors peer-checked:border-brand peer-checked:bg-brand peer-focus-visible:ring-4 peer-focus-visible:ring-brand/20 peer-checked:[&>svg]:opacity-100">
          <Check className="size-3.5 opacity-0 transition-opacity" aria-hidden="true" />
        </span>
        <span>{label}</span>
      </label>
      <FieldError errors={errors} />
    </div>
  );
}

export function FormAlert({ tone = "error", children }: { tone?: "error" | "warning" | "success"; children: React.ReactNode }) {
  const styles = {
    error: "border-red-200 bg-red-50 text-red-700",
    warning: "border-amber-200 bg-amber-50 text-amber-800",
    success: "border-emerald-200 bg-emerald-50 text-emerald-700",
  }[tone];
  return (
    <div role={tone === "error" ? "alert" : "status"} className={cn("rounded-xl border px-4 py-3 text-sm", styles)}>
      {children}
    </div>
  );
}

export function SubmitButton({ children, className }: { children: React.ReactNode; className?: string }) {
  const { pending } = useFormStatus();
  return (
    <button
      type="submit"
      disabled={pending}
      className={cn(
        "group inline-flex h-12 w-full items-center justify-center gap-2 rounded-full bg-brand text-[15px] font-semibold text-white shadow-lg shadow-brand/25 transition-colors hover:bg-brand-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand disabled:opacity-60",
        className,
      )}
    >
      {pending ? (
        <Loader2 className="size-4 animate-spin" aria-hidden="true" />
      ) : (
        <>
          {children}
          <ArrowRight className="size-4 transition-transform group-hover:translate-x-0.5" aria-hidden="true" />
        </>
      )}
    </button>
  );
}

export function AuthHeading({ eyebrow, title, subtitle }: { eyebrow: string; title: string; subtitle?: React.ReactNode }) {
  return (
    <div className="space-y-3">
      <p className="text-xs font-semibold uppercase tracking-[0.18em] text-brand">{eyebrow}</p>
      <h1 className="font-display text-[2rem] font-extrabold leading-[1.1] tracking-tight text-navy sm:text-[2.25rem]">{title}</h1>
      {subtitle && <p className="text-[15px] leading-relaxed text-slate-500">{subtitle}</p>}
    </div>
  );
}
