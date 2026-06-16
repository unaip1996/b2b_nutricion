import { Users, Sparkles, TriangleAlert, ArrowUpRight } from "lucide-react"

type KpiCardProps = {
  label: string
  value: string
  icon: React.ReactNode
  children: React.ReactNode
}

function KpiCard({ label, value, icon, children }: KpiCardProps) {
  return (
    <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
      <div className="flex items-start justify-between">
        <p className="text-sm font-medium text-slate-500">{label}</p>
        <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-50 text-slate-600">
          {icon}
        </div>
      </div>
      <p className="mt-4 text-3xl font-bold text-slate-800">{value}</p>
      <div className="mt-3">{children}</div>
    </div>
  )
}

export function KpiCards() {
  return (
    <section className="grid grid-cols-1 gap-6 md:grid-cols-3">
      <KpiCard label="Pacientes Activos" value="142" icon={<Users className="h-5 w-5" />}>
        <span className="inline-flex items-center gap-1 text-sm font-medium text-emerald-600">
          <ArrowUpRight className="h-4 w-4" />
          +12% este mes
        </span>
      </KpiCard>

      <KpiCard label="Dietas Generadas (IA)" value="854" icon={<Sparkles className="h-5 w-5" />}>
        <span className="inline-flex items-center gap-1 text-sm font-medium text-emerald-600">
          <ArrowUpRight className="h-4 w-4" />
          +5% este mes
        </span>
      </KpiCard>

      <KpiCard label="Alertas Clínicas" value="3" icon={<TriangleAlert className="h-5 w-5" />}>
        <span className="inline-flex items-center gap-1 rounded-md bg-red-50 px-2 py-1 text-sm font-medium text-red-600">
          <TriangleAlert className="h-3.5 w-3.5" />
          Pacientes requieren atención
        </span>
      </KpiCard>
    </section>
  )
}
