type DietStatus = "Validada" | "Borrador"

type DietRow = {
  paciente: string
  fecha: string
  objetivo: string
  estado: DietStatus
}

const rows: DietRow[] = [
  { paciente: "María González", fecha: "16 Jun 2026", objetivo: "Pérdida de peso", estado: "Validada" },
  { paciente: "Javier Ramírez", fecha: "16 Jun 2026", objetivo: "Control glucémico", estado: "Borrador" },
  { paciente: "Lucía Fernández", fecha: "15 Jun 2026", objetivo: "Dieta sin gluten", estado: "Validada" },
  { paciente: "Carlos Méndez", fecha: "15 Jun 2026", objetivo: "Ganancia muscular", estado: "Borrador" },
]

function StatusBadge({ estado }: { estado: DietStatus }) {
  const styles =
    estado === "Validada"
      ? "bg-emerald-50 text-emerald-700"
      : "bg-amber-50 text-amber-700"
  return (
    <span className={`inline-flex items-center rounded-md px-2.5 py-1 text-xs font-medium ${styles}`}>
      {estado}
    </span>
  )
}

export function RecentActivity() {
  return (
    <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
      <h2 className="text-lg font-semibold text-slate-800">Actividad Reciente del Motor RAG</h2>
      <p className="mt-1 text-sm text-slate-500">Últimas dietas generadas por el sistema.</p>

      <div className="mt-6 overflow-x-auto">
        <table className="w-full text-left text-sm">
          <thead>
            <tr className="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
              <th className="pb-3 font-medium">Paciente</th>
              <th className="pb-3 font-medium">Fecha</th>
              <th className="pb-3 font-medium">Objetivo</th>
              <th className="pb-3 text-right font-medium">Estado</th>
            </tr>
          </thead>
          <tbody>
            {rows.map((row) => (
              <tr key={row.paciente} className="border-b border-slate-100 last:border-0">
                <td className="py-4 font-medium text-slate-800">{row.paciente}</td>
                <td className="py-4 text-slate-500">{row.fecha}</td>
                <td className="py-4 text-slate-600">{row.objetivo}</td>
                <td className="py-4 text-right">
                  <StatusBadge estado={row.estado} />
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  )
}
