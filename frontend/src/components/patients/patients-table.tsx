"use client";

import React from "react";
import Link from "next/link";
import { Search, FileText, BrainCircuit } from "lucide-react";

export interface Patient {
  id: string;
  medicalHistoryNumber?: string;
  name?: string;
  age?: number;
  bmi?: number;
  condition?: string;
  goal?: string;
  isAllergy?: boolean;
}

function ConditionBadge({ condition, isAllergy }: { condition: string; isAllergy: boolean }) {
  const classes = isAllergy ? "bg-red-100 text-red-800" : "bg-orange-100 text-orange-800";
  return <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${classes}`}>{condition}</span>;
}

function PatientRow({ patient }: { patient: Patient }) {
  return (
    <tr className="border-t border-slate-200 transition-colors hover:bg-slate-50">
      <td className="p-4 font-mono text-sm text-slate-500">
        {patient.medicalHistoryNumber || `PAC-${patient.id.substring(0, 4)}`}
      </td>
      <td className="p-4">
        <span className="font-medium text-slate-800">{patient.name || "Paciente Anónimo"}</span>
      </td>
      <td className="p-4 text-sm text-slate-600">
        {patient.age || "--"} años / IMC: {patient.bmi || "--"}
      </td>
      <td className="p-4">
        <ConditionBadge condition={patient.condition || ""} isAllergy={patient.isAllergy || false} />
      </td>
      <td className="p-4 text-sm text-slate-600">{patient.goal || "--"}</td>
      <td className="p-4">
        <div className="flex items-center justify-end gap-3">
          <Link
            href={`/dashboard/patients/${patient.id}`}
            className="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition-colors hover:text-blue-600"
          >
            <FileText className="h-4 w-4" />
            Ficha
          </Link>
          <Link
            href={`/dashboard/patients/${patient.id}/diets`}
            className="inline-flex items-center gap-1.5 rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 transition-colors hover:bg-blue-100 border border-blue-200"
          >
            <BrainCircuit className="h-3.5 w-3.5" />
            Dietas
          </Link>
        </div>
      </td>
    </tr>
  );
}

// Interfaz de las Props que le enviará la página principal a la tabla
interface PatientsTableProps {
  patients: Patient[];
  isLoading: boolean;
  totalItems: number;
  currentPage: number;
  totalPages: number;
  itemsPerPage: number;
  onFilterChange: (col: string, val: string) => void;
  onPageChange: (page: number) => void;
}

export function PatientsTable({
  patients,
  isLoading,
  totalItems,
  currentPage,
  totalPages,
  itemsPerPage,
  onFilterChange,
  onPageChange
}: PatientsTableProps) {
  return (
    <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
      <div className="overflow-x-auto">
        <table className="w-full text-left text-sm whitespace-nowrap">
          <thead className="bg-slate-50 text-slate-600 font-semibold border-b border-slate-200">
            <tr>
              <th className="p-4 text-xs font-semibold uppercase tracking-wide text-slate-500">ID MÉDICO</th>
              <th className="p-4 text-xs font-semibold uppercase tracking-wide text-slate-500">PACIENTE</th>
              <th className="p-4 text-xs font-semibold uppercase tracking-wide text-slate-500">BIOMETRÍA (EDAD/IMC)</th>
              <th className="p-4 text-xs font-semibold uppercase tracking-wide text-slate-500">CONDICIÓN PRINCIPAL</th>
              <th className="p-4 text-xs font-semibold uppercase tracking-wide text-slate-500">OBJETIVO</th>
              <th className="p-4 text-xs font-semibold uppercase tracking-wide text-slate-500 text-right">ACCIONES</th>
            </tr>
            {/* Inputs de Filtro (Dumb components avisando al padre) */}
            <tr className="bg-white border-b border-slate-100">
              <th className="px-4 pb-3">
                <div className="relative">
                  <Search className="w-3 h-3 absolute left-2 top-2 text-slate-400" />
                  <input type="text" placeholder="Filtrar ID..." className="w-full border rounded-md text-xs pl-7 pr-2 py-1.5 font-normal focus:ring-1 focus:ring-blue-500 outline-none" onChange={(e) => onFilterChange('medicalId', e.target.value)} />
                </div>
              </th>
              <th className="px-4 pb-3">
                <div className="relative">
                  <Search className="w-3 h-3 absolute left-2 top-2 text-slate-400" />
                  <input type="text" placeholder="Buscar nombre..." className="w-full border rounded-md text-xs pl-7 pr-2 py-1.5 font-normal focus:ring-1 focus:ring-blue-500 outline-none" onChange={(e) => onFilterChange('name', e.target.value)} />
                </div>
              </th>
              <th className="px-4 pb-3"></th>
              <th className="px-4 pb-3">
                <input type="text" placeholder="Filtrar..." className="w-full border rounded-md text-xs px-2 py-1.5 font-normal focus:ring-1 focus:ring-blue-500 outline-none" onChange={(e) => onFilterChange('condition', e.target.value)} />
              </th>
              <th className="px-4 pb-3">
                <input type="text" placeholder="Filtrar..." className="w-full border rounded-md text-xs px-2 py-1.5 font-normal focus:ring-1 focus:ring-blue-500 outline-none" onChange={(e) => onFilterChange('objective', e.target.value)} />
              </th>
              <th className="px-4 pb-3"></th>
            </tr>
          </thead>
          
          <tbody className="divide-y divide-slate-100">
            {isLoading ? (
              <tr><td colSpan={6} className="text-center py-10 text-slate-500">Consultando base de datos...</td></tr>
            ) : patients.length === 0 ? (
              <tr><td colSpan={6} className="text-center py-10 text-slate-500">No se han encontrado pacientes.</td></tr>
            ) : (
              patients.map((patient) => (
                <PatientRow key={patient.id} patient={patient} />
              ))
            )}
          </tbody>
        </table>
      </div>

      {/* Paginación */}
      <div className="flex flex-col sm:flex-row justify-between items-center p-4 bg-slate-50 border-t border-slate-200 text-sm text-slate-600">
        <div className="mb-4 sm:mb-0">
          Mostrando del <span className="font-semibold text-slate-900">{totalItems === 0 ? 0 : (currentPage - 1) * itemsPerPage + 1}</span> al <span className="font-semibold text-slate-900">{Math.min(currentPage * itemsPerPage, totalItems)}</span> de <span className="font-semibold text-slate-900">{totalItems}</span> expedientes
        </div>
        <div className="flex space-x-2">
          <button 
            onClick={() => onPageChange(Math.max(currentPage - 1, 1))}
            disabled={currentPage === 1}
            className="px-4 py-2 border border-slate-200 rounded-md bg-white hover:bg-slate-100 disabled:opacity-50 disabled:hover:bg-white font-medium transition-colors"
          >
            Anterior
          </button>
          <button 
            onClick={() => onPageChange(Math.min(currentPage + 1, totalPages))}
            disabled={currentPage >= totalPages || totalPages === 0}
            className="px-4 py-2 border border-slate-200 rounded-md bg-white hover:bg-slate-100 disabled:opacity-50 disabled:hover:bg-white font-medium transition-colors"
          >
            Siguiente
          </button>
        </div>
      </div>
    </div>
  );
}