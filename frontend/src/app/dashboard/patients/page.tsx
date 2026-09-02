"use client";

import React, { useState, useEffect } from "react";
import Link from "next/link";
import { Plus } from "lucide-react";
import { fetchWithAuth } from "@/lib/auth"; 
import { PatientsTable, Patient } from "@/components/patients/patients-table";

export default function PatientsPage() {
  const [patients, setPatients] = useState<Patient[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [totalItems, setTotalItems] = useState(0);

  // Estados de Paginación y Filtros
  const [currentPage, setCurrentPage] = useState(1);
  const [itemsPerPage, setItemsPerPage] = useState(10);
  const [filters, setFilters] = useState({
    medicalId: "",
    name: "",
    condition: "",
    objective: "",
  });

  const [debouncedFilters, setDebouncedFilters] = useState(filters);

  // Efecto Debounce (Protege el backend de múltiples llamadas rápidas)
  useEffect(() => {
    const timer = setTimeout(() => {
      setDebouncedFilters(filters);
      setCurrentPage(1); // Si se busca algo nuevo, volvemos a la página 1
    }, 500);
    return () => clearTimeout(timer);
  }, [filters]);

  // Fetch Server-Side
  useEffect(() => {
    const fetchPatients = async () => {
      setIsLoading(true);
      try {
        const queryParams = new URLSearchParams({
          page: currentPage.toString(),
          itemsPerPage: itemsPerPage.toString(),
        });

        if (debouncedFilters.medicalId) queryParams.append("medicalId", debouncedFilters.medicalId);
        if (debouncedFilters.name) queryParams.append("name", debouncedFilters.name);
        if (debouncedFilters.condition) queryParams.append("mainCondition", debouncedFilters.condition);
        if (debouncedFilters.objective) queryParams.append("objective", debouncedFilters.objective);

        const res = await fetchWithAuth(`/api/patients?${queryParams.toString()}`);
        
        if (res.ok) {
          const responseJson = await res.json();
          setPatients(responseJson.data || responseJson['hydra:member'] || []);
          setTotalItems(responseJson.total || responseJson['hydra:totalItems'] || 0);
        }
      } catch (error) {
        console.error("Error cargando pacientes:", error);
      } finally {
        setIsLoading(false);
      }
    };

    fetchPatients();
  }, [currentPage, itemsPerPage, debouncedFilters]);

  const totalPages = Math.ceil(totalItems / itemsPerPage);

  const handleFilterChange = (col: string, val: string) => {
    setFilters((prev) => ({ ...prev, [col]: val }));
  };

  const handlePageChange = (page: number) => {
    setCurrentPage(page);
  };

  return (
    <div className="p-8 max-w-7xl mx-auto">
      <div className="mb-8">
        <h1 className="text-3xl font-bold text-slate-900 mb-2">Directorio Clínico de Pacientes</h1>
        <p className="text-slate-500">Gestión de historiales biométricos y perfiles clínicos.</p>
      </div>

      {/* Controles Superiores */}
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div className="flex items-center space-x-3 bg-white p-2 rounded-lg border border-slate-200 shadow-sm">
          <span className="text-sm text-slate-500 font-medium pl-2">Mostrar</span>
          <select
            className="border-none text-sm focus:ring-0 cursor-pointer bg-slate-50 rounded p-1"
            value={itemsPerPage}
            onChange={(e) => {
              setItemsPerPage(Number(e.target.value));
              setCurrentPage(1); // Reset a página 1 si cambiamos el límite
            }}
          >
            <option value={10}>10</option>
            <option value={20}>20</option>
            <option value={50}>50</option>
          </select>
          <span className="text-sm text-slate-500 font-medium pr-2">registros</span>
        </div>

        <Link href="/dashboard/patients/create" className="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg flex items-center gap-2 text-sm font-semibold transition-colors shadow-sm">
          <Plus className="w-4 h-4" />
          Nuevo Paciente
        </Link>
      </div>

      {/* Tabla Inyectada como Componente Aislado */}
      <PatientsTable 
        patients={patients}
        isLoading={isLoading}
        totalItems={totalItems}
        currentPage={currentPage}
        totalPages={totalPages}
        itemsPerPage={itemsPerPage}
        onFilterChange={handleFilterChange}
        onPageChange={handlePageChange}
      />
    </div>
  );
}