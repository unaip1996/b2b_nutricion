const documents = [
  {
    nombre: "Guía OMS - Nutrición Deportiva 2025.pdf",
    tipo: "Guía Clínica",
    fecha: "16 Jun 2026",
    chunks: "245 Chunks",
  },
  {
    nombre: "Protocolo Diabetes Tipo 2 - ADA.pdf",
    tipo: "Protocolo",
    fecha: "12 Jun 2026",
    chunks: "389 Chunks",
  },
  {
    nombre: "Metaanálisis Suplementación Proteica.pdf",
    tipo: "Estudio Científico",
    fecha: "08 Jun 2026",
    chunks: "172 Chunks",
  },
  {
    nombre: "Manual Dietético Hospitalario 2026.pdf",
    tipo: "Manual",
    fecha: "01 Jun 2026",
    chunks: "512 Chunks",
  },
]

export function DocumentsTable() {
  return (
    <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
      <div className="overflow-x-auto">
        <table className="w-full border-collapse text-left">
          <thead>
            <tr className="border-b border-slate-200 bg-slate-50/50">
              <th className="p-4 text-xs font-semibold uppercase tracking-wide text-slate-500">Documento Médico</th>
              <th className="p-4 text-xs font-semibold uppercase tracking-wide text-slate-500">Tipo</th>
              <th className="p-4 text-xs font-semibold uppercase tracking-wide text-slate-500">Fecha Ingesta</th>
              <th className="p-4 text-xs font-semibold uppercase tracking-wide text-slate-500">Fragmentos (Chunks)</th>
              <th className="p-4 text-xs font-semibold uppercase tracking-wide text-slate-500">Estado Vectorial</th>
              <th className="p-4 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Acciones</th>
            </tr>
          </thead>
          <tbody>
            {documents.map((doc) => (
              <tr key={doc.nombre} className="border-b border-slate-100 last:border-0 hover:bg-slate-50/50">
                <td className="p-4 text-sm font-medium text-slate-800">{doc.nombre}</td>
                <td className="p-4">
                  <span className="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                    {doc.tipo}
                  </span>
                </td>
                <td className="p-4 text-sm text-slate-600">{doc.fecha}</td>
                <td className="p-4 text-sm text-slate-600">{doc.chunks}</td>
                <td className="p-4">
                  <span className="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-800">
                    Indexado (pgvector)
                  </span>
                </td>
                <td className="p-4 text-right">
                  <button type="button" className="text-sm font-medium text-blue-600 hover:text-blue-700">
                    Ver Metadatos
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  )
}
