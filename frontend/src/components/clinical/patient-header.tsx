import { Save } from "lucide-react"

export function PatientHeader() {
  return (
    <header className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
      <div>
        <h1 className="text-3xl font-bold text-balance text-slate-800">Carlos Ruiz Navarro</h1>
        <p className="mt-1 text-sm text-slate-500">ID: PAC-001 | Fecha de alta: 10 May 2026</p>
      </div>
      <div className="flex items-center gap-3">
        <button
          type="button"
          className="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-50"
        >
          Cancelar
        </button>
        <button
          type="button"
          className="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-blue-700"
        >
          <Save className="h-4 w-4" aria-hidden="true" />
          Guardar Cambios
        </button>
      </div>
    </header>
  )
}
