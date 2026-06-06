export type Patient = {
  id: string
  name: string
  age: number
  bmi: number
  condition: string
  isAllergy: boolean
  goal: string
}

export const patients: Patient[] = [
  {
    id: "PAC-001",
    name: "Carlos Ruiz",
    age: 29,
    bmi: 24.5,
    condition: "Intolerancia a la lactosa",
    isAllergy: true,
    goal: "Hipertrofia",
  },
  {
    id: "PAC-002",
    name: "Lucía Fernández",
    age: 42,
    bmi: 27.8,
    condition: "Hipertensión arterial",
    isAllergy: false,
    goal: "Pérdida de peso",
  },
  {
    id: "PAC-003",
    name: "Miguel Ángel Torres",
    age: 35,
    bmi: 22.1,
    condition: "Celiaquía",
    isAllergy: true,
    goal: "Mantenimiento",
  },
  {
    id: "PAC-004",
    name: "Ana Belén Morales",
    age: 51,
    bmi: 30.4,
    condition: "Diabetes tipo 2",
    isAllergy: false,
    goal: "Control glucémico",
  },
  {
    id: "PAC-005",
    name: "Javier Domínguez",
    age: 24,
    bmi: 21.3,
    condition: "Alergia a frutos secos",
    isAllergy: true,
    goal: "Definición muscular",
  },
]
