// src/app/dashboard/layout.tsx
import type React from "react"
import { ClinicalSidebar } from "@/components/clinical-sidebar"

export default function DashboardLayout({
  children,
}: {
  children: React.ReactNode
}) {
  return (
    <div className="flex">
      <ClinicalSidebar />
      <main className="h-screen flex-1 overflow-y-auto bg-slate-50 text-slate-900 antialiased">
        {children}
      </main>
    </div>
  )
}