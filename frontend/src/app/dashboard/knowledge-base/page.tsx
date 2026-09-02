"use client";

import React, { useState, useEffect } from "react";
import { AlertTriangle } from "lucide-react";
import { fetchWithAuth } from "@/lib/auth";
import { KnowledgeBaseHeader } from "@/components/knowledge-base/knowledge-base-header";
import { KnowledgeBaseToolbar } from "@/components/knowledge-base/knowledge-base-toolbar";
import { DocumentsTable, ClinicalDocument } from "@/components/knowledge-base/documents-table";

export default function KnowledgeBasePage() {
  const [documents, setDocuments] = useState<ClinicalDocument[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [totalItems, setTotalItems] = useState(0);

  // Estados de Paginación y Filtros
  const [currentPage, setCurrentPage] = useState(1);
  const [itemsPerPage, setItemsPerPage] = useState(10);
  const [filters, setFilters] = useState({
    title: "",
  });

  const [debouncedFilters, setDebouncedFilters] = useState(filters);

  // Guardamos el objeto entero (o id/título) para poder mostrar el nombre en el modal pero borrar por ID
  const [documentToDelete, setDocumentToDelete] = useState<{ id: string; title: string } | null>(null);
  const [isDeleting, setIsDeleting] = useState(false);

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
    const fetchDocuments = async () => {
      setIsLoading(true);
      try {
        const queryParams = new URLSearchParams({
          page: currentPage.toString(),
          itemsPerPage: itemsPerPage.toString(),
        });

        if (debouncedFilters.title) queryParams.append("title", debouncedFilters.title);

        const res = await fetchWithAuth(`/api/knowledge-base?${queryParams.toString()}`);

        if (res.ok) {
          const responseJson = await res.json();
          setDocuments(responseJson.data || []);
          setTotalItems(responseJson.total || 0);
        }
      } catch (error) {
        console.error("Error cargando documentos:", error);
      } finally {
        setIsLoading(false);
      }
    };

    fetchDocuments();

    // Escuchar evento de subida (Ingesta) desde el toolbar
    const handleUpload = () => {
      console.log("Nuevo documento ingerido, refrescando tabla...");
      fetchDocuments();
    };
    
    window.addEventListener("documentUploaded", handleUpload);
    return () => window.removeEventListener("documentUploaded", handleUpload);
  }, [currentPage, itemsPerPage, debouncedFilters]);

  const totalPages = Math.ceil(totalItems / itemsPerPage);

  const handleFilterChange = (col: string, val: string) => {
    setFilters((prev) => ({ ...prev, [col]: val }));
  };

  const handlePageChange = (page: number) => {
    setCurrentPage(page);
  };

  const handleDelete = async () => {
    if (!documentToDelete) return;
    setIsDeleting(true);

    try {
      // 🚨 Apuntamos al endpoint DELETE /api/knowledge-base/{id}
      const response = await fetchWithAuth(`/api/knowledge-base/${documentToDelete.id}`, {
        method: "DELETE",
      });

      if (response.ok) {
        setDocumentToDelete(null);
        // Refrescar la lista
        const queryParams = new URLSearchParams({
          page: currentPage.toString(),
          itemsPerPage: itemsPerPage.toString(),
        });
        if (debouncedFilters.title) queryParams.append("title", debouncedFilters.title);

        const res = await fetchWithAuth(`/api/knowledge-base?${queryParams.toString()}`);
        if (res.ok) {
          const responseJson = await res.json();
          setDocuments(responseJson.data || []);
          setTotalItems(responseJson.total || 0);
        }
      } else {
        const errorData = await response.json();
        alert(errorData.error || "No se pudo eliminar el documento.");
      }
    } catch (error) {
      alert("Error de red al eliminar.");
    } finally {
      setIsDeleting(false);
    }
  };

  return (
    <main className="min-h-screen bg-slate-50 text-slate-900">
      <div className="mx-auto max-w-6xl px-6 py-10">
        <KnowledgeBaseHeader />
        <KnowledgeBaseToolbar />

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
        <DocumentsTable
          documents={documents}
          isLoading={isLoading}
          totalItems={totalItems}
          currentPage={currentPage}
          totalPages={totalPages}
          itemsPerPage={itemsPerPage}
          onFilterChange={handleFilterChange}
          onPageChange={handlePageChange}
          onDelete={setDocumentToDelete}
        />

        {/* Modal de Confirmación Estilo Tailwind */}
        {documentToDelete && (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
            <div className="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
              <div className="flex items-center gap-4 text-red-600 mb-4">
                <div className="p-3 bg-red-100 rounded-full">
                  <AlertTriangle className="h-6 w-6" />
                </div>
                <h3 className="text-lg font-bold text-slate-900">
                  ¿Eliminar guía clínica?
                </h3>
              </div>
              <p className="text-slate-600 text-sm mb-6 leading-relaxed">
                Estás a punto de eliminar{" "}
                <strong className="text-slate-800">"{documentToDelete.title}"</strong>. 
                Esto purgará todos sus fragmentos vectoriales de PostgreSQL y la IA dejará de 
                tener acceso a esta literatura para generar dietas. Esta acción no se puede deshacer.
              </p>
              <div className="flex justify-end gap-3">
                <button
                  onClick={() => setDocumentToDelete(null)}
                  disabled={isDeleting}
                  className="px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors disabled:opacity-50"
                >
                  Cancelar
                </button>
                <button
                  onClick={handleDelete}
                  disabled={isDeleting}
                  className="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-xl transition-colors shadow-sm focus:ring-2 focus:ring-red-500/20 disabled:opacity-50"
                >
                  {isDeleting ? "Eliminando..." : "Sí, purgar vectores"}
                </button>
              </div>
            </div>
          </div>
        )}
      </div>
    </main>
  );
}

