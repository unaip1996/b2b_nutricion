import { CircleCheckBig, FileText } from "lucide-react"

type KnowledgeDoc = {
  titulo: string
  tipo: string
}

const docs: KnowledgeDoc[] = [
  { titulo: "Guía Nutrición OMS 2025", tipo: "Documento PDF" },
  { titulo: "Estudio Celiaquía PDF", tipo: "Investigación clínica" },
  { titulo: "Protocolo Diabetes Tipo 2", tipo: "Guía clínica" },
  { titulo: "Tablas Composición Alimentos", tipo: "Base de datos" },
]

export function KnowledgeBase() {
  return (
    <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
      <h2 className="text-lg font-semibold text-slate-800">Estado de la Base de Conocimiento</h2>
      <p className="mt-1 text-sm text-slate-500">Documentos ingeridos en la base vectorial.</p>

      <ul className="mt-6 flex flex-col gap-3">
        {docs.map((doc) => (
          <li
            key={doc.titulo}
            className="flex items-center gap-3 rounded-lg border border-slate-100 bg-slate-50/60 p-3"
          >
            <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-slate-500 shadow-sm">
              <FileText className="h-4 w-4" />
            </div>
            <div className="min-w-0 flex-1">
              <p className="truncate text-sm font-medium text-slate-800">{doc.titulo}</p>
              <p className="truncate text-xs text-slate-500">{doc.tipo}</p>
            </div>
            <span className="inline-flex items-center gap-1 text-xs font-medium text-emerald-600">
              <CircleCheckBig className="h-4 w-4" />
              Vectorizado
            </span>
          </li>
        ))}
      </ul>
    </div>
  )
}
