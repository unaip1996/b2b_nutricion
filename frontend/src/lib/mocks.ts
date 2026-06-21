export type KnowledgeDoc = {
  titulo: string
  tipo: string
}

export const knowledgeDocs: KnowledgeDoc[] = [
  { titulo: "Guía Nutrición OMS 2025", tipo: "Documento PDF" },
  { titulo: "Estudio Celiaquía PDF", tipo: "Investigación clínica" },
  { titulo: "Protocolo Diabetes Tipo 2", tipo: "Guía clínica" },
  { titulo: "Tablas Composición Alimentos", tipo: "Base de datos" },
]

export type DietStatus = "Validada" | "Borrador"

export type RecentDiet = {
  paciente: string
  fecha: string
  objetivo: string
  estado: DietStatus
}

export const recentDiets: RecentDiet[] = [
  { paciente: "María González", fecha: "16 Jun 2026", objetivo: "Pérdida de peso", estado: "Validada" },
  { paciente: "Javier Ramírez", fecha: "16 Jun 2026", objetivo: "Control glucémico", estado: "Borrador" },
  { paciente: "Lucía Fernández", fecha: "15 Jun 2026", objetivo: "Dieta sin gluten", estado: "Validada" },
  { paciente: "Carlos Méndez", fecha: "15 Jun 2026", objetivo: "Ganancia muscular", estado: "Borrador" },
]