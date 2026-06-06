import { Plus, X } from "lucide-react"
import { ClinicalCard } from "./clinical-card"
import { FormField, SelectInput, TextArea } from "./form-field"

const allergies = [
  { label: "Intolerancia a la lactosa (Severa)" },
  { label: "Gluten (Leve)" },
]

export function ClinicalHistoryColumn() {
  return (
    <div className="flex flex-col gap-8 lg:col-span-2">
      <ClinicalCard
        label="Restricciones y Alergias"
        action={
          <button
            type="button"
            className="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 transition-colors hover:bg-slate-50"
          >
            <Plus className="h-3.5 w-3.5" aria-hidden="true" />
            Añadir Alérgeno
          </button>
        }
      >
        <div className="flex flex-wrap gap-2">
          {allergies.map((allergy) => (
            <span
              key={allergy.label}
              className="inline-flex items-center gap-1.5 rounded-full bg-red-100 px-3 py-1 text-sm font-medium text-red-800"
            >
              {allergy.label}
              <button
                type="button"
                aria-label={`Eliminar ${allergy.label}`}
                className="rounded-full text-red-500 transition-colors hover:text-red-700"
              >
                <X className="h-3.5 w-3.5" aria-hidden="true" />
              </button>
            </span>
          ))}
        </div>
      </ClinicalCard>

      <ClinicalCard label="Patologías Previas">
        <FormField label="Condiciones clínicas registradas" htmlFor="patologias">
          <TextArea
            id="patologias"
            rows={3}
            defaultValue="Ninguna patología cardiovascular registrada."
          />
        </FormField>
      </ClinicalCard>

      <ClinicalCard label="Objetivos Nutricionales">
        <div className="flex flex-col gap-4">
          <FormField label="Objetivo principal" htmlFor="objetivo">
            <SelectInput
              id="objetivo"
              defaultValue="Hipertrofia Muscular"
              options={[
                "Hipertrofia Muscular",
                "Pérdida de Grasa",
                "Mantenimiento",
                "Rendimiento Deportivo",
                "Recomposición Corporal",
              ]}
            />
          </FormField>
          <FormField label="Notas del profesional" htmlFor="notas">
            <TextArea
              id="notas"
              rows={5}
              placeholder="Añade observaciones, pautas y seguimiento del paciente..."
              defaultValue="Aumento progresivo de la ingesta proteica a 1.8 g/kg. Revisión de adherencia en 4 semanas."
            />
          </FormField>
        </div>
      </ClinicalCard>
    </div>
  )
}
