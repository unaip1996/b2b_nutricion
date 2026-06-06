import { ClinicalCard } from "./clinical-card"
import { FormField, TextInput } from "./form-field"

export function PersonalDataColumn() {
  return (
    <div className="flex flex-col gap-8">
      <ClinicalCard label="Datos Personales">
        <div className="flex flex-col gap-4">
          <div className="grid grid-cols-2 gap-4">
            <FormField label="Edad" htmlFor="edad">
              <TextInput id="edad" defaultValue="29" />
            </FormField>
            <FormField label="Género" htmlFor="genero">
              <TextInput id="genero" defaultValue="Varón" />
            </FormField>
          </div>
          <FormField label="Teléfono" htmlFor="telefono">
            <TextInput id="telefono" type="tel" defaultValue="+34 612 345 678" />
          </FormField>
          <FormField label="Email" htmlFor="email">
            <TextInput id="email" type="email" defaultValue="carlos.ruiz@email.com" />
          </FormField>
        </div>
      </ClinicalCard>

      <ClinicalCard label="Perfil Biométrico">
        <div className="flex flex-col gap-4">
          <div className="grid grid-cols-2 gap-4">
            <FormField label="Peso (kg)" htmlFor="peso">
              <TextInput id="peso" defaultValue="78.5" />
            </FormField>
            <FormField label="Altura (cm)" htmlFor="altura">
              <TextInput id="altura" defaultValue="178" />
            </FormField>
          </div>
          <div className="rounded-lg border border-blue-100 bg-blue-50 px-4 py-3">
            <p className="text-xs font-medium uppercase tracking-wider text-blue-700/80">Índice de Masa Corporal</p>
            <div className="mt-1 flex items-baseline gap-2">
              <span className="text-2xl font-bold text-blue-700">24.8</span>
              <span className="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800">
                Normopeso
              </span>
            </div>
          </div>
        </div>
      </ClinicalCard>
    </div>
  )
}
