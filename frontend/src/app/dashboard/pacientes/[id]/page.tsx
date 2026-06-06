import { PatientHeader } from "@/components/clinical/patient-header"
import { PersonalDataColumn } from "@/components/clinical/personal-data-column"
import { ClinicalHistoryColumn } from "@/components/clinical/clinical-history-column"

export default function Page() {
  return (
    <main className="min-h-screen bg-slate-50 text-slate-900">
      <div className="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
        <PatientHeader />
        <div className="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-3">
          <PersonalDataColumn />
          <ClinicalHistoryColumn />
        </div>
      </div>
    </main>
  )
}
