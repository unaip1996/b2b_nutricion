import { Lock } from "lucide-react"

export function LoginCard() {
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
        <form className="mt-8 space-y-5">
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
              className="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 outline-none transition focus:border-blue-600 focus:bg-white focus:ring-2 focus:ring-blue-600/20"
              placeholder="••••••••••••"
            />
          </div>

          <button
            type="submit"
            className="w-full rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-600/40 focus:ring-offset-2"
          >
            Verificar Licencia B2B
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
