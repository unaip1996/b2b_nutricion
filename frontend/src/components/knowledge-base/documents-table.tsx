"use client";

import { Database, Trash2, AlertTriangle, CheckCircle2, Search } from "lucide-react";
import React from "react";

export interface ClinicalDocument {
    id: string;
    title: string;
    chunksCount: number;
    uploadedAt: string;
    status: string;
}

interface DocumentsTableProps {
    documents: ClinicalDocument[];
    isLoading: boolean;
    totalItems: number;
    currentPage: number;
    totalPages: number;
    itemsPerPage: number;
    onFilterChange: (col: string, val: string) => void;
    onPageChange: (page: number) => void;
    onDelete: (doc: { id: string; title: string }) => void;
}

export function DocumentsTable({
    documents,
    isLoading,
    totalItems,
    currentPage,
    totalPages,
    itemsPerPage,
    onFilterChange,
    onPageChange,
    onDelete,
}: DocumentsTableProps) {
    // Helper para formatear la fecha
    const formatDate = (isoString: string) => {
        return new Date(isoString).toLocaleDateString("es-ES", {
            year: "numeric",
            month: "short",
            day: "numeric",
            hour: "2-digit",
            minute: "2-digit"
        });
    };

    return (
        <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div className="overflow-x-auto">
                <table className="w-full text-left text-sm text-slate-600">
                    <thead className="bg-slate-50/50 border-b border-slate-200">
                        <tr>
                            <th className="p-4 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Documento Médico
                            </th>
                            <th className="p-4 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Fecha Ingesta
                            </th>
                            <th className="p-4 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Volumen
                            </th>
                            <th className="p-4 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Estado Vectorial
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
                                        placeholder="Filtrar por título..."
                                        className="w-full border rounded-md text-xs pl-7 pr-2 py-1.5 font-normal focus:ring-1 focus:ring-blue-500 outline-none"
                                        onChange={(e) => onFilterChange("title", e.target.value)}
                                    />
                                </div>
                            </th>
                            <th className="px-4 pb-3"></th>
                            <th className="px-4 pb-3"></th>
                            <th className="px-4 pb-3"></th>
                            <th className="px-4 pb-3"></th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {isLoading ? (
                            <tr>
                                <td
                                    colSpan={5}
                                    className="p-8 text-center text-slate-400"
                                >
                                    Cargando base de conocimiento...
                                </td>
                            </tr>
                        ) : documents.length === 0 ? (
                            <tr>
                                <td
                                    colSpan={5}
                                    className="p-8 text-center text-slate-400"
                                >
                                    No hay guías clínicas indexadas. Sube un documento para comenzar.
                                </td>
                            </tr>
                        ) : (
                            documents.map((doc) => (
                                <tr
                                    key={doc.id}
                                    className="transition-colors hover:bg-slate-50/50"
                                >
                                    <td className="p-4 font-medium text-slate-800 flex items-center gap-2">
                                        <span className="text-blue-500">📄</span>
                                        {doc.title}
                                    </td>
                                    <td className="p-4 font-mono text-xs text-slate-500">
                                        {formatDate(doc.uploadedAt)}
                                    </td>
                                    <td className="p-4">
                                        <span className="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-1 text-xs font-medium text-slate-600">
                                            <Database className="h-3 w-3" />
                                            {doc.chunksCount} chunks
                                        </span>
                                    </td>
                                    <td className="p-4">
                                        <span className="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-800 border border-emerald-200">
                                            <CheckCircle2 className="h-3 w-3" />
                                            Indexado (pgvector)
                                        </span>
                                    </td>
                                    <td className="p-4 text-right">
                                        <button
                                            onClick={() =>
                                                onDelete({
                                                    id: doc.id,
                                                    title: doc.title,
                                                })
                                            }
                                            className="text-slate-400 hover:text-red-500 transition-colors p-1.5 rounded-lg hover:bg-red-50"
                                            title="Eliminar documento y sus vectores"
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
                    de <span className="font-semibold text-slate-900">{totalItems}</span> documentos
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