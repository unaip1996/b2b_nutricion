// src/app/dashboard/rag/page.tsx
import { RagWorkspace } from "@/components/rag-workspace"

export default function RagPage() {
  return (
    <div className="mx-auto w-full max-w-7xl p-8">
      <header className="mb-8 border-b border-slate-200 pb-4">
        <h1 className="text-3xl font-bold text-slate-800">Orquestador de Inferencia RAG</h1>
        <p className="mt-2 text-sm text-slate-500">
          Generación de pautas nutricionales basadas en similitud vectorial sobre evidencia indexada.
        </p>
      </header>
      <RagWorkspace />
    </div>
  )
}