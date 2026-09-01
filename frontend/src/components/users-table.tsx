"use client";

import React from "react";
import { Search, Shield, ShieldCheck, Edit2 } from "lucide-react";
import { useRouter } from "next/navigation";

export interface UserRow {
  id: string;
  email: string;
  roles: string[];
  lastLogin: string | null;
}

interface UsersTableProps {
  users: UserRow[];
  isLoading: boolean;
  totalItems: number;
  currentPage: number;
  totalPages: number;
  itemsPerPage: number;
  onFilterChange: (col: string, val: string) => void;
  onPageChange: (page: number) => void;
}

export function UsersTable({
  users,
  isLoading,
  totalItems,
  currentPage,
  totalPages,
  itemsPerPage,
  onFilterChange,
  onPageChange,
}: UsersTableProps) {
  const router = useRouter();

  return (
    <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
      <div className="overflow-x-auto">
        <table className="w-full text-left text-sm whitespace-nowrap">
          <thead className="bg-slate-50 text-slate-600 font-semibold border-b border-slate-200">
            <tr>
              <th className="p-4 text-xs font-semibold uppercase tracking-wide text-slate-500">
                Cuenta (Email)
              </th>
              <th className="p-4 text-xs font-semibold uppercase tracking-wide text-slate-500">
                Privilegios
              </th>
              <th className="p-4 text-xs font-semibold uppercase tracking-wide text-slate-500">
                Último Acceso
              </th>
              <th className="p-4 text-xs font-semibold uppercase tracking-wide text-slate-500 text-right">
                Acciones
              </th>
            </tr>
            {/* Fila de Filtros */}
            <tr className="bg-white border-b border-slate-100">
              <th className="px-4 pb-3">
                <div className="relative">
                  <Search className="w-3 h-3 absolute left-2 top-2 text-slate-400" />
                  <input
                    type="text"
                    placeholder="Filtrar por email..."
                    className="w-full border rounded-md text-xs pl-7 pr-2 py-1.5 font-normal focus:ring-1 focus:ring-blue-500 outline-none"
                    onChange={(e) => onFilterChange("email", e.target.value)}
                  />
                </div>
              </th>
              <th className="px-4 pb-3">
                <select
                  className="w-full border rounded-md text-xs px-2 py-1.5 font-normal focus:ring-1 focus:ring-blue-500 outline-none"
                  onChange={(e) => onFilterChange("role", e.target.value)}
                >
                  <option value="">Todos los roles</option>
                  <option value="ROLE_ADMIN">Admin</option>
                  <option value="ROLE_NUTRITIONIST">Nutricionista</option>
                </select>
              </th>
              <th className="px-4 pb-3"></th>
              <th className="px-4 pb-3"></th>
            </tr>
          </thead>

          <tbody className="divide-y divide-slate-100">
            {isLoading ? (
              <tr>
                <td colSpan={4} className="text-center py-10 text-slate-500">
                  Cargando usuarios...
                </td>
              </tr>
            ) : users.length === 0 ? (
              <tr>
                <td colSpan={4} className="text-center py-10 text-slate-500">
                  No se han encontrado usuarios.
                </td>
              </tr>
            ) : (
              users.map((user) => (
                <tr key={user.id} className="border-t border-slate-200 transition-colors hover:bg-slate-50">
                  <td className="p-4 font-medium text-slate-900">{user.email}</td>
                  <td className="p-4 text-sm">
                    {user.roles.includes("ROLE_ADMIN") ? (
                      <span className="inline-flex items-center gap-1 rounded-full bg-purple-50 px-2.5 py-1 text-xs font-medium text-purple-700 border border-purple-200">
                        <ShieldCheck className="h-3 w-3" />
                        Admin
                      </span>
                    ) : (
                      <span className="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700 border border-blue-200">
                        <Shield className="h-3 w-3" />
                        Nutricionista
                      </span>
                    )}
                  </td>
                  <td className="p-4 text-sm text-slate-400 font-mono">{user.lastLogin || "Nunca"}</td>
                  <td className="p-4 text-right">
                    <button
                      onClick={() => router.push(`/dashboard/users/${user.id}`)}
                      className="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-slate-700 shadow-sm transition-colors hover:bg-slate-50"
                    >
                      <Edit2 className="h-3.5 w-3.5 text-slate-500" />
                      Editar
                    </button>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>

      {/* Paginación */}
      <div className="flex flex-col sm:flex-row justify-between items-center p-4 bg-slate-50 border-t border-slate-200 text-sm text-slate-600">
        <div className="mb-4 sm:mb-0">
          Mostrando del{" "}
          <span className="font-semibold text-slate-900">
            {totalItems === 0 ? 0 : (currentPage - 1) * itemsPerPage + 1}
          </span>{" "}
          al{" "}
          <span className="font-semibold text-slate-900">
            {Math.min(currentPage * itemsPerPage, totalItems)}
          </span>{" "}
          de <span className="font-semibold text-slate-900">{totalItems}</span> usuarios
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
