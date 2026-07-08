import { Sparkles } from "lucide-react"

export function RagWorkspace() {
  return (
    <div className="grid grid-cols-1 gap-8 lg:grid-cols-2">
      {/* Panel 1: Input */}
      <section className="flex min-h-[500px] flex-col rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <label
          htmlFor="clinical-context"
          className="mb-3 block text-sm font-semibold uppercase tracking-wide text-slate-700"
        >
          Contexto Clínico del Paciente
        </label>
        <textarea
          id="clinical-context"
          className="w-full flex-1 resize-none rounded-lg border border-slate-200 bg-slate-50 p-4 text-slate-700 outline-none transition-shadow focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
          placeholder="Ej: Paciente varón, 29 años, intolerancia a la lactosa severa. Objetivo: hipertrofia muscular..."
        />
        <button
          type="button"
          className="mt-6 flex w-full items-center justify-center gap-2 rounded-lg bg-slate-900 px-4 py-3 font-medium text-white transition-colors hover:bg-slate-800"
        >
          <Sparkles className="h-5 w-5" aria-hidden="true" />
          Generar Dieta (IA)
        </button>
      </section>

      {/* Panel 2: Output */}
      <section className="flex min-h-[500px] flex-col rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 className="mb-3 border-b border-slate-100 pb-2 text-sm font-semibold uppercase tracking-wide text-slate-700">
          Respuesta del Motor Clínico
        </h2>
        <div className="flex flex-1 items-center justify-center overflow-y-auto whitespace-pre-wrap rounded-lg border border-slate-100 bg-slate-50 p-6 font-mono text-sm leading-relaxed text-slate-800">
          <p className="text-center italic text-slate-400">
            Esperando variables biométricas para iniciar la recuperación de documentos (RAG)...
          </p>
        </div>
      </section>
    </div>
  )
}
