"use client";

import { FileText, Database, Trash2, AlertTriangle, CheckCircle2 } from "lucide-react";
import { useEffect, useState } from "react";

interface ClinicalDocument {
    id: string;
    title: string;
    chunksCount: number;
    uploadedAt: string;
    status: string;
}

export function DocumentsTable() {
    const [documents, setDocuments] = useState<ClinicalDocument[]>([]);
    const [isLoading, setIsLoading] = useState(true);

    // Guardamos el objeto entero (o id/título) para poder mostrar el nombre en el modal pero borrar por ID
    const [documentToDelete, setDocumentToDelete] = useState<{ id: string; title: string } | null>(null);
    const [isDeleting, setIsDeleting] = useState(false);

    const fetchDocuments = async () => {
        setIsLoading(true);
        try {
            const token = document.cookie
                .split("; ")
                .find((row) => row.startsWith("auth_token="))
                ?.split("=")[1];
            const response = await fetch(
                `${process.env.NEXT_PUBLIC_API_URL}/api/knowledge-base`,
                {
                    headers: { Authorization: `Bearer ${token}` },
                },
            );
            const result = await response.json();
            
            // El nuevo controlador de Symfony devuelve directamente un array JSON
            if (response.ok) {
                setDocuments(Array.isArray(result) ? result : result.data || []);
            }
        } catch (error) {
            console.error("Error al cargar documentos", error);
        } finally {
            setIsLoading(false);
        }
    };

    // Cargar al montar el componente y escuchar el evento de subida (Ingesta)
    useEffect(() => {
        fetchDocuments();

        const handleUpload = () => {
            console.log("Nuevo documento ingerido, refrescando tabla...");
            fetchDocuments();
        };
        
        window.addEventListener("documentUploaded", handleUpload);
        return () => window.removeEventListener("documentUploaded", handleUpload);
    }, []);

    const handleDelete = async () => {
        if (!documentToDelete) return;
        setIsDeleting(true);

        try {
            const token = document.cookie
                .split("; ")
                .find((row) => row.startsWith("auth_token="))
                ?.split("=")[1];

            // 🚨 Apuntamos al endpoint DELETE /api/knowledge-base/{id}
            const response = await fetch(
                `${process.env.NEXT_PUBLIC_API_URL}/api/knowledge-base/${documentToDelete.id}`,
                {
                    method: "DELETE",
                    headers: {
                        Authorization: `Bearer ${token}`,
                    },
                }
            );

            if (response.ok) {
                setDocumentToDelete(null);
                fetchDocuments();
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
        <>
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
                                            <FileText className="h-4 w-4 text-blue-500" />
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
                                                    setDocumentToDelete({
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
            </div>

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
        </>
    );
}