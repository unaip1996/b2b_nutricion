import { Brain, Users, BookOpen } from "lucide-react"

export function ClinicalSidebar() {
  return (
    <aside className="flex w-64 flex-col bg-slate-900">
      {/* Header */}
      <div className="flex h-20 items-center border-b border-slate-800 px-6">
        <span className="text-xl font-bold text-white">
          NutriSupport<span className="text-blue-500">.AI</span>
        </span>
      </div>

      {/* Navigation */}
      <nav className="flex-1 px-4 py-6">
        <h2 className="mb-4 px-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Panel Clínico</h2>
        <ul className="flex flex-col gap-1">
          <li>
            <a href="#" aria-current="page" className="flex items-center gap-3 rounded-lg bg-blue-600/10 px-3 py-2.5 text-sm font-medium text-blue-400 transition-colors">
              <Brain className="h-5 w-5" aria-hidden="true" />
              Motor RAG Clínico
            </a>
          </li>
          <li>
            <a href="#" className="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-400 transition-colors hover:bg-slate-800 hover:text-slate-200">
              <Users className="h-5 w-5" aria-hidden="true" />
              Directorio Pacientes
            </a>
          </li>
          <li>
            <a href="#" className="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-400 transition-colors hover:bg-slate-800 hover:text-slate-200">
              <BookOpen className="h-5 w-5" aria-hidden="true" />
              Base de Conocimiento
            </a>
          </li>
        </ul>
      </nav>

      {/* User Profile Footer */}
      <div className="mt-auto flex items-center gap-3 border-t border-slate-800 p-4 px-2">
        <div className="flex h-8 w-8 items-center justify-center rounded-full bg-slate-700 text-xs font-bold text-slate-300">
          DR
        </div>
        <div>
          <p className="text-sm font-medium text-slate-200">Dr. Facultativo</p>
          <p className="text-xs text-slate-500">Licencia Activa</p>
        </div>
      </div>
    </aside>
  )
}