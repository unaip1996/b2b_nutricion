import { Sparkles } from "lucide-react"
import { KpiCards } from "@/components/dashboard/kpi-cards"
import { RecentActivity } from "@/components/dashboard/recent-activity"
import { KnowledgeBase } from "@/components/knowledge-base/knowledge-base"

export default function DashboardPage() {
  return (
    <div className="mx-auto max-w-7xl px-6 py-10">
      <header className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
          <h1 className="text-3xl font-bold text-balance text-slate-800">Panel de Control Clínico</h1>
          <p className="mt-2 text-slate-500">
            Resumen operativo y métricas de inferencia RAG. Hola, Dr. Facultativo.
          </p>
        </div>
        <button
          type="button"
          className="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition-colors hover:bg-slate-800"
        >
          <Sparkles className="h-4 w-4" />
          Generar Nueva Dieta (IA)
        </button>
      </header>

      <div className="mt-8">
        <KpiCards />
      </div>

      <div className="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-3">
        <RecentActivity />
        <KnowledgeBase />
      </div>
    </div>
  )
}
