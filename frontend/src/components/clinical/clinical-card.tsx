import type { ReactNode } from "react"

interface ClinicalCardProps {
  label: string
  children: ReactNode
  action?: ReactNode
}

export function ClinicalCard({ label, children, action }: ClinicalCardProps) {
  return (
    <section className="rounded-xl border border-slate-200 bg-white shadow-sm">
      <header className="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
        <h2 className="text-xs font-semibold uppercase tracking-wider text-slate-500">{label}</h2>
        {action}
      </header>
      <div className="p-5">{children}</div>
    </section>
  )
}
