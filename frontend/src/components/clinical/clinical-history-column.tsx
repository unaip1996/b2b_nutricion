import { Plus, X } from "lucide-react"
import { ClinicalCard } from "./clinical-card"
import { FormField, SelectInput, TextArea } from "./form-field"

interface ClinicalHistoryColumnProps {
  formData: any;
  onChange: (field: string, value: any) => void;
}

export function ClinicalHistoryColumn({ formData, onChange }: ClinicalHistoryColumnProps) {
  const allergies = formData.allergies || [];

  const handleAddAllergen = () => {
    const result = window.prompt("Introduce el nombre del alérgeno:");
    if (result && result.trim()) {
      onChange("allergies", [...allergies, result.trim()]);
    }
  };

  const handleRemoveAllergen = (allergenToRemove: string) => {
    onChange("allergies", allergies.filter((a: string) => a !== allergenToRemove));
  };

  return (
    <div className="flex flex-col gap-8 lg:col-span-2">
      <ClinicalCard
        label="Restricciones y Alergias"
        action={
          <button
            type="button"
            onClick={handleAddAllergen}
            className="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 transition-colors hover:bg-slate-50"
          >
            <Plus className="h-3.5 w-3.5" aria-hidden="true" />
            Añadir Alérgeno
          </button>
        }
      >
        <div className="flex flex-wrap gap-2">
          {allergies.length === 0 ? (
            <span className="text-sm text-slate-500">No hay alérgenos registrados.</span>
          ) : (
            allergies.map((allergy: string, idx: number) => (
            <span
              key={`${allergy}-${idx}`}
              className="inline-flex items-center gap-1.5 rounded-full bg-red-100 px-3 py-1 text-sm font-medium text-red-800"
            >
              {allergy}
              <button
                type="button"
                onClick={() => handleRemoveAllergen(allergy)}
                aria-label={`Eliminar ${allergy}`}
                className="rounded-full text-red-500 transition-colors hover:text-red-700"
              >
                <X className="h-3.5 w-3.5" aria-hidden="true" />
              </button>
            </span>
            ))
          )}
        </div>
      </ClinicalCard>

      <ClinicalCard label="Patologías Previas">
        <FormField label="Condiciones clínicas registradas" htmlFor="patologias">
          <TextArea
            id="patologias"
            rows={3}
            value={formData.pathologies || ''}
            onChange={(e) => onChange('pathologies', e.target.value)}
          />
        </FormField>
      </ClinicalCard>

      <ClinicalCard label="Objetivos Nutricionales">
        <div className="flex flex-col gap-4">
          <FormField label="Objetivo principal" htmlFor="objetivo">
            <SelectInput
              id="objetivo"
              value={formData.goal || ''}
              onChange={(e) => onChange('goal', e.target.value)}
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
              value={formData.notes || ''}
              onChange={(e) => onChange('notes', e.target.value)}
            />
          </FormField>
        </div>
      </ClinicalCard>
    </div>
  )
}
