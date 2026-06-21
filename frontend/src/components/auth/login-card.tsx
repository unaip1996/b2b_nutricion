"use client"
import { useState } from "react"
import { Lock } from "lucide-react"
import { useRouter } from "next/navigation";

export function LoginCard() {
  const router = useRouter();
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [isLoading, setIsLoading] = useState(false);

  const handleLogin = async (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault(); // Evita que la página se recargue

    setIsLoading(true);
    setError("");

    try {
      const response = await fetch("http://localhost:8000/api/login_check", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ username: email, password: password }),
      });

      if (!response.ok) {
        throw new Error("Credenciales inválidas o no autorizadas.");
      }

      const data = await response.json();
      document.cookie = `auth_token=${data.token}; path=/; max-age=86400`;
      
      router.push("/dashboard");
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : "Error de conexión con el servidor.");
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="w-full max-w-md rounded-2xl border border-slate-200 bg-white shadow-sm">
      <div className="px-8 pt-10 pb-8">
        {/* Logo */}
        <div className="text-center">
          <span className="text-xl font-semibold tracking-tight text-slate-900">
            NutriSupport
            <span className="text-blue-600">.AI</span>
          </span>
        </div>

        {/* Title + subtitle */}
        <div className="mt-8 space-y-2 text-center">
          <h1 className="text-balance text-2xl font-semibold tracking-tight text-slate-900">
            Acceso al Panel Profesional
          </h1>
          <p className="text-pretty text-sm leading-relaxed text-slate-500">
            Introduzca sus credenciales autorizadas de facultativo.
          </p>
        </div>

        {/* Form */}
        <form className="mt-8 space-y-5" onSubmit={handleLogin}>
          {error && (
            <div className="rounded-xl bg-red-50 p-3 text-sm text-red-600 border border-red-100">
              {error}
            </div>
          )}

          <div className="space-y-2">
            <label
              htmlFor="email"
              className="block text-xs font-medium uppercase tracking-wide text-slate-600"
            >
              Correo Electrónico Corporativo
            </label>
            <input
              id="email"
              name="email"
              type="email"
              autoComplete="email"
              required
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              className="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 outline-none transition focus:border-blue-600 focus:bg-white focus:ring-2 focus:ring-blue-600/20"
              placeholder="nombre@institucion.es"
            />
          </div>

          <div className="space-y-2">
            <label
              htmlFor="password"
              className="block text-xs font-medium uppercase tracking-wide text-slate-600"
            >
              Contraseña de Seguridad
            </label>
            <input
              id="password"
              name="password"
              type="password"
              autoComplete="current-password"
              required
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              className="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 outline-none transition focus:border-blue-600 focus:bg-white focus:ring-2 focus:ring-blue-600/20"
              placeholder="••••••••••••"
            />
          </div>

          <button
            type="submit"
            disabled={isLoading}
            className="w-full rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-600/40 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {isLoading ? "Validando..." : "Acceder"}
          </button>
        </form>
      </div>

      {/* Footer */}
      <div className="flex items-center justify-center gap-2 border-t border-slate-200 px-8 py-4">
        <Lock className="size-3.5 text-slate-400" aria-hidden="true" />
        <p className="text-xs text-slate-400">
          Entorno segurizado con cifrado extremo a extremo
        </p>
      </div>
    </div>
  )
}
