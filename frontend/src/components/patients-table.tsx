import { type Patient, patients } from "@/lib/patients"

function ConditionBadge({ condition, isAllergy }: { condition: string; isAllergy: boolean }) {
  const classes = isAllergy ? "bg-red-100 text-red-800" : "bg-slate-100 text-slate-700"
  return <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${classes}`}>{condition}</span>
}

function PatientRow({ patient }: { patient: Patient }) {
  return (
    <tr className="border-t border-slate-200 transition-colors hover:bg-slate-50">
      <td className="p-4 font-mono text-sm text-slate-500">{patient.id}</td>
      <td className="p-4">
        <span className="font-medium text-slate-800">{patient.name}</span>
      </td>
      <td className="p-4 text-sm text-slate-600">
        {patient.age} años / IMC: {patient.bmi}
      </td>
      <td className="p-4">
        <ConditionBadge condition={patient.condition} isAllergy={patient.isAllergy} />
      </td>
      <td className="p-4 text-sm text-slate-600">{patient.goal}</td>
      <td className="p-4 text-right">
        <button type="button" className="text-sm font-medium text-blue-600 hover:text-blue-700">
          Editar Ficha
        </button>
      </td>
    </tr>
  )
}

export function PatientsTable() {
  return (
    <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
      <div className="overflow-x-auto">
        <table className="w-full text-left">
          <thead>
            <tr className="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
              <th className="p-4">ID Médico</th>
              <th className="p-4">Paciente</th>
              <th className="p-4">Biometría (Edad/IMC)</th>
              <th className="p-4">Condición Principal</th>
              <th className="p-4">Objetivo</th>
              <th className="p-4 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody>
            {patients.map((patient) => (
              <PatientRow key={patient.id} patient={patient} />
            ))}
          </tbody>
        </table>
      </div>
    </div>
  )
}
