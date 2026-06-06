"use client"

import { Plus, Search } from "lucide-react"
import { PatientsTable } from "@/components/patients-table"

export default function Page() {
  return (
    <main className="min-h-screen bg-slate-50 text-slate-900">
      <div className="mx-auto max-w-6xl px-6 py-10">
        <header className="mb-8">
          <h1 className="text-3xl font-bold text-slate-800 text-balance">Directorio Clínico de Pacientes</h1>
          <p className="mt-1 text-slate-500">Gestión de historiales biométricos y perfiles clínicos.</p>
        </header>

        <div className="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div className="relative w-full sm:max-w-sm">
            <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
            <input
              type="search"
              placeholder="Buscar paciente..."
              aria-label="Buscar paciente"
              className="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-4 text-sm text-slate-900 shadow-sm outline-none placeholder:text-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-100"
            />
          </div>
          <button
            type="button"
            className="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition-colors hover:bg-blue-700"
          >
            <Plus className="size-4" />
            Nuevo Paciente
          </button>
        </div>

        <PatientsTable />
      </div>
    </main>
  )
}
