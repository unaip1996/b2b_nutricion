"use client"

import { Save } from "lucide-react"
import { useRouter } from "next/navigation"

interface PatientHeaderProps {
  isCreateMode: boolean;
  isLoading: boolean;
  patientName?: string;
}

export function PatientHeader({ isCreateMode, isLoading, patientName }: PatientHeaderProps) {
  const router = useRouter()

  return (
    <header className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
      <div>
        <h1 className="text-3xl font-bold text-balance text-slate-800">
          {isCreateMode ? "Nuevo Paciente" : (patientName || "Cargando...")}
        </h1>
        <p className="mt-1 text-sm text-slate-500">
          {isCreateMode ? "Completando ficha de ingreso" : "ID: PAC-001 | Fecha de alta: 10 May 2026"}
        </p>
      </div>
      <div className="flex items-center gap-3">
        <button
          type="button"
          onClick={() => router.back()}
          className="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-50"
        >
          Cancelar
        </button>
        <button
          type="submit"
          disabled={isLoading}
          className="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <Save className="h-4 w-4" aria-hidden="true" />
          {isLoading ? "Guardando..." : "Guardar Cambios"}
        </button>
      </div>
    </header>
  )
}
