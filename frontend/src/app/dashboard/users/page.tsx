"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { Users, Plus } from "lucide-react";
import { fetchWithAuth } from "@/lib/auth";
import { UsersTable, UserRow } from "@/components/users-table";

export default function UsersListPage() {
  const [users, setUsers] = useState<UserRow[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [totalItems, setTotalItems] = useState(0);
  const router = useRouter();

  // Estados de Paginación y Filtros
  const [currentPage, setCurrentPage] = useState(1);
  const [itemsPerPage, setItemsPerPage] = useState(10);
  const [filters, setFilters] = useState({
    email: "",
    role: "",
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
    const fetchUsers = async () => {
      setIsLoading(true);
      try {
        const queryParams = new URLSearchParams({
          page: currentPage.toString(),
          itemsPerPage: itemsPerPage.toString(),
        });

        if (debouncedFilters.email) queryParams.append("email", debouncedFilters.email);
        if (debouncedFilters.role) queryParams.append("role", debouncedFilters.role);

        const response = await fetchWithAuth(`/api/users?${queryParams.toString()}`);
        if (response.ok) {
          const responseJson = await response.json();
          setUsers(responseJson.data || []);
          setTotalItems(responseJson.total || 0);
        }
      } catch (error) {
        console.error("Error cargando usuarios:", error);
      } finally {
        setIsLoading(false);
      }
    };
    fetchUsers();
  }, [currentPage, itemsPerPage, debouncedFilters]);

  const totalPages = Math.ceil(totalItems / itemsPerPage);

  const handleFilterChange = (col: string, val: string) => {
    setFilters((prev) => ({ ...prev, [col]: val }));
  };

  const handlePageChange = (page: number) => {
    setCurrentPage(page);
  };

  return (
    <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <header className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-slate-200 pb-6 mb-8">
        <div>
          <h1 className="text-3xl font-bold text-slate-800 flex items-center gap-3">
            <Users className="h-8 w-8 text-blue-600" />
            Gestión de Usuarios
          </h1>
          <p className="mt-1 text-sm text-slate-500">
            Administración de accesos y roles de la plataforma
          </p>
        </div>
        {/* Botón de acceso a la pantalla de creación */}
        <div>
          <button
            onClick={() => router.push("/dashboard/users/create")}
            className="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-blue-700"
          >
            <Plus className="h-4 w-4" />
            Nuevo Usuario
          </button>
        </div>
      </header>

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
      </div>

      {/* Tabla */}
      <UsersTable
        users={users}
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

