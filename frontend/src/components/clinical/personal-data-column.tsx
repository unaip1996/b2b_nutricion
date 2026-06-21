import { ClinicalCard } from "./clinical-card"
import { FormField, SelectInput, TextInput } from "./form-field"

interface PersonalDataColumnProps {
  formData: any;
  onChange: (field: string, value: any) => void;
}

export function PersonalDataColumn({ formData, onChange }: PersonalDataColumnProps) {
  const pesoValue = parseFloat(formData.weight);
  const alturaValue = parseFloat(formData.height) / 100;
  let imc = "--";
  let imcLabel = "--";

  if (!isNaN(pesoValue) && !isNaN(alturaValue) && alturaValue > 0) {
    const calculatedImc = (pesoValue / (alturaValue * alturaValue));
    imc = calculatedImc.toFixed(1);
    if (calculatedImc < 18.5) imcLabel = "Bajo Peso";
    else if (calculatedImc < 25) imcLabel = "Normopeso";
    else if (calculatedImc < 30) imcLabel = "Sobrepeso";
    else imcLabel = "Obesidad";
  }

  return (
    <div className="flex flex-col gap-8">
      <ClinicalCard label="Datos Personales">
        <div className="flex flex-col gap-4">
          <FormField label="Nombre completo" htmlFor="nombre">
            <TextInput id="nombre" value={formData.name || ''} onChange={(e) => onChange('name', e.target.value)} />
          </FormField>
          <div className="grid grid-cols-2 gap-4">
            <FormField label="Edad" htmlFor="edad">
              <TextInput id="edad" type="number" value={formData.age || ''} onChange={(e) => onChange('age', e.target.value)} />
            </FormField>
            <FormField label="Género" htmlFor="genero">
              <SelectInput
                id="genero"
                value={formData.gender || ''}
                onChange={(e) => onChange('gender', e.target.value)}
                options={["Hombre", "Mujer"]}
              />
            </FormField>
          </div>
          <FormField label="Teléfono" htmlFor="telefono">
            <TextInput id="telefono" type="tel" value={formData.phone || ''} onChange={(e) => onChange('phone', e.target.value)} />
          </FormField>
          <FormField label="Email" htmlFor="email">
            <TextInput id="email" type="email" value={formData.email || ''} onChange={(e) => onChange('email', e.target.value)} />
          </FormField>
        </div>
      </ClinicalCard>

      <ClinicalCard label="Perfil Biométrico">
        <div className="flex flex-col gap-4">
          <div className="grid grid-cols-2 gap-4">
            <FormField label="Peso (kg)" htmlFor="peso">
              <TextInput id="peso" type="number" step="0.1" value={formData.weight || ''} onChange={(e) => onChange('weight', e.target.value)} />
            </FormField>
            <FormField label="Altura (cm)" htmlFor="altura">
              <TextInput id="altura" type="number" value={formData.height || ''} onChange={(e) => onChange('height', e.target.value)} />
            </FormField>
          </div>
          <div className="rounded-lg border border-blue-100 bg-blue-50 px-4 py-3">
            <p className="text-xs font-medium uppercase tracking-wider text-blue-700/80">Índice de Masa Corporal</p>
            <div className="mt-1 flex items-baseline gap-2">
              <span className="text-2xl font-bold text-blue-700">{imc}</span>
              <span className="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800">
                {imcLabel}
              </span>
            </div>
          </div>
        </div>
      </ClinicalCard>
    </div>
  )
}
