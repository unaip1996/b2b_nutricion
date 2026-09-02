"use client";

import React, { useState, useEffect } from "react";
import { Trash2, Pencil, FileSpreadsheet, Calendar, ShieldCheck } from "lucide-react";
import { useRouter } from "next/navigation";

export interface PatientDiet {
  id: string;
  name: string;
  createdAt: string;
  kcal: number;
  status: "Activo" | "Expirado" | "Programado" | "Borrador" | "Validado";
}

interface PatientDietsTableProps {
  diets: PatientDiet[];
  isLoading: boolean;
  totalItems: number;
  currentPage: number;
  totalPages: number;
  itemsPerPage: number;
  onFilterChange: (col: string, val: string) => void;
  onPageChange: (page: number) => void;
  onDelete: (diet: { id: string; name: string }) => void;
}

const statusStyles = {
  Activo: "bg-green-100 text-green-800 border-green-200",
  Expirado: "bg-red-100 text-red-800 border-red-200",
  Programado: "bg-blue-100 text-blue-800 border-blue-200",
  Borrador: "bg-gray-100 text-gray-800 border-gray-200",
  Validado: "bg-emerald-50 text-emerald-700 border-emerald-200",
};

export function PatientDietsTable({
  diets,
  isLoading,
  totalItems,
  currentPage,
  totalPages,
  itemsPerPage,
  onFilterChange,
  onPageChange,
  onDelete,
}: PatientDietsTableProps) {
  const router = useRouter();
  
  // 1. Estado para controlar la hidratación
  const [isMounted, setIsMounted] = useState(false);

  // 2. Efecto para confirmar que estamos en el navegador (cliente)
  useEffect(() => {
    setIsMounted(true);
  }, []);

  const startRecord = (currentPage - 1) * itemsPerPage + 1;
  const endRecord = Math.min(currentPage * itemsPerPage, totalItems);

  // 3. Guardia de hidratación: Renderiza un estado neutral en el servidor
  if (!isMounted) {
    return (
      <div className="bg-white rounded-lg border border-slate-200 shadow-md min-h-[400px] flex items-center justify-center">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-slate-900"></div>
      </div>
    );
  }

  return (
    <div className="bg-white rounded-lg border border-slate-200 shadow-md overflow-hidden">
      <div className="overflow-x-auto">
        <table className="w-full">
          {/* Encabezados */}
          <thead>
            <tr className="border-b border-slate-200 bg-slate-50">
              <th className="px-6 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">
                Plan Nutricional
              </th>
              <th className="px-6 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">
                Fecha de Inferencia
              </th>
              <th className="px-6 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">
                Ajuste Energético
              </th>
              <th className="px-6 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">
                Estado
              </th>
              <th className="px-6 py-3 text-center text-xs font-semibold text-slate-700 uppercase tracking-wider">
                Acciones
              </th>
            </tr>

            {/* Fila de filtros */}
            <tr className="border-b border-slate-200 bg-slate-100">
              <td className="px-6 py-3">
                <input
                  type="text"
                  placeholder="Buscar por nombre..."
                  onChange={(e) => onFilterChange("name", e.target.value)}
                  className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </td>
              <td className="px-6 py-3">
                <input
                  type="date"
                  onChange={(e) => onFilterChange("createdAt", e.target.value)}
                  className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 text-slate-500"
                />
              </td>
              <td className="px-6 py-3">
                <input
                  type="number"
                  placeholder="Buscar por Kcal"
                  onChange={(e) => onFilterChange("kcal", e.target.value)}
                  className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </td>
              <td className="px-6 py-3">
                <select
                  onChange={(e) => onFilterChange("status", e.target.value)}
                  className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white text-slate-600"
                >
                  <option value="">Todos los estados</option>
                  <option value="Activo">Activo</option>
                  <option value="Expirado">Expirado</option>
                </select>
              </td>
              <td className="px-6 py-3"></td>
            </tr>
          </thead>

          {/* Body */}
          <tbody>
            {isLoading ? (
              <tr>
                <td colSpan={5} className="px-6 py-12 text-center">
                  <div className="flex justify-center">
                    <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-slate-900"></div>
                  </div>
                </td>
              </tr>
            ) : diets.length === 0 ? (
              <tr>
                <td colSpan={5} className="px-6 py-12 text-center text-slate-500 italic">
                  Este expediente no registra planes dietéticos activos. Haz clic en "Diseñar Nueva Dieta" para iniciar el motor de IA.
                </td>
              </tr>
            ) : (
              diets.map((diet) => (
                <tr key={diet.id} className="border-b border-slate-200 hover:bg-slate-50 transition-colors">
                  <td className="px-6 py-4 text-sm font-medium text-slate-900 flex items-center gap-2">
                    <FileSpreadsheet className="h-4 w-4 text-slate-400" />
                    {diet.name}
                  </td>
                  <td className="px-6 py-4 text-sm text-slate-600">
                    <span className="inline-flex items-center gap-1 text-xs font-mono">
                      <Calendar className="h-3 w-3 text-slate-400" />
                      {diet.createdAt}
                    </span>
                  </td>
                  <td className="px-6 py-4 text-sm font-semibold text-slate-900">
                    {diet.kcal} kcal
                  </td>
                  <td className="px-6 py-4">
                    <span
                      className={`inline-flex items-center gap-1 px-2.5 py-0.5 text-xs font-medium rounded-full border ${
                        statusStyles[diet.status]
                      }`}
                    >
                      {diet.status === "Validado" && (
                        <ShieldCheck className="h-3 w-3" />
                      )}
                      {diet.status}
                    </span>
                  </td>
                  <td className="px-6 py-4 text-center space-x-2">
                    <button
                      onClick={() => router.push(`/dashboard/diets/${diet.id}`)}
                      className="inline-flex items-center gap-2 px-3 py-1 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded-lg transition-colors"
                      title="Editar"
                    >
                      <Pencil className="h-4 w-4" />
                    </button>
                    <button
                      onClick={() => onDelete({ id: diet.id, name: diet.name })}
                      className="inline-flex items-center gap-2 px-3 py-1 bg-red-100 hover:bg-red-200 text-red-700 rounded-lg transition-colors"
                      title="Eliminar"
                    >
                      <Trash2 className="h-4 w-4" />
                    </button>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>

      {/* Footer con paginación y contadores */}
      <div className="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
        <span className="text-sm text-slate-600">
          Mostrando del {startRecord} al {endRecord} de {totalItems} dietas
        </span>

        <div className="flex items-center gap-2">
          <button
            onClick={() => onPageChange(currentPage - 1)}
            disabled={Boolean(currentPage <= 1 || isLoading)}
            className="px-3 py-1 border border-slate-300 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            Anterior
          </button>

          <span className="text-sm text-slate-600 px-2">
            {currentPage} / {totalPages || 1}
          </span>

          <button
            onClick={() => onPageChange(currentPage + 1)}
            disabled={Boolean(currentPage >= (totalPages || 1) || isLoading)}
            className="px-3 py-1 border border-slate-300 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            Siguiente
          </button>
        </div>
      </div>
    </div>
  );
}