"use client"

import Link from "next/link"
import { usePathname } from "next/navigation"
import { Brain, Users, BookOpen } from "lucide-react"

const navigation = [
  { name: "Motor RAG Clínico", href: "/dashboard/rag", icon: Brain },
  { name: "Directorio Pacientes", href: "/dashboard/patients", icon: Users },
  { name: "Base de Conocimiento", href: "/dashboard/knowledge-base", icon: BookOpen }, // Asumiendo una ruta /kb
]

export function ClinicalSidebar() {
  const pathname = usePathname()

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
          {navigation.map((item) => {
            const isActive = pathname.startsWith(item.href)
            return (
              <li key={item.name}>
                <Link
                  href={item.href}
                  aria-current={isActive ? "page" : undefined}
                  className={`flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors ${isActive ? "bg-blue-600/10 text-blue-400" : "text-slate-400 hover:bg-slate-800 hover:text-slate-200"}`}
                >
                  <item.icon className="h-5 w-5" aria-hidden="true" />
                  {item.name}
                </Link>
              </li>
            )
          })}
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