"use client";

import { useParams, useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import {
    Plus,
    ChevronLeft,
    AlertTriangle,
} from "lucide-react";
import Link from "next/link";
import { fetchWithAuth } from "@/lib/auth";
import { PatientDietsTable, PatientDiet } from "@/components/diets/patient-diets-table";

interface PatientHeaderData {
    name: string;
    historyNumber: string;
}

export default function PatientDietsPage() {
    const params = useParams();
    const router = useRouter();
    const patientId = params.id as string;

    // 1. Estado de Montaje Seguro para evitar el Hydration Mismatch
    const [isMounted, setIsMounted] = useState(false);

    const [diets, setDiets] = useState<PatientDiet[]>([]);
    const [patient, setPatient] = useState<PatientHeaderData | null>(null);
    const [isLoading, setIsLoading] = useState(true);
    const [totalItems, setTotalItems] = useState(0);

    // Estados de Paginación y Filtros (Ahora incluye todos los campos)
    const [currentPage, setCurrentPage] = useState(1);
    const [itemsPerPage, setItemsPerPage] = useState(10);
    const [filters, setFilters] = useState({
        name: "",
        createdAt: "",
        kcal: "",
        status: "",
    });

    const [debouncedFilters, setDebouncedFilters] = useState(filters);

    // Guardamos el objeto entero para poder mostrar el nombre en el modal pero borrar por ID
    const [dietToDelete, setDietToDelete] = useState<{ id: string; name: string } | null>(null);
    const [isDeleting, setIsDeleting] = useState(false);

    // Efecto de Montaje Seguro
    useEffect(() => {
        setIsMounted(true);
    }, []);

    // Efecto Debounce
    useEffect(() => {
        const timer = setTimeout(() => {
            setDebouncedFilters(filters);
            setCurrentPage(1);
        }, 500);
        return () => clearTimeout(timer);
    }, [filters]);

    // Fetch Patient Data
    useEffect(() => {
        const fetchPatient = async () => {
            try {
                const patientRes = await fetchWithAuth(`/api/patients/${patientId}`);

                if (patientRes.ok) {
                    const pData = await patientRes.json();
                    setPatient({
                        name: pData.data.name || "Paciente Anónimo",
                        historyNumber:
                            pData.data.medicalHistoryNumber ||
                            patientId.split("-")[0],
                    });
                }
            } catch (error) {
                console.error("Error al cargar datos del paciente:", error);
            }
        };

        if (patientId) {
            fetchPatient();
        }
    }, [patientId]);

    // Fetch Diets Server-Side
    useEffect(() => {
        const fetchDiets = async () => {
            setIsLoading(true);
            try {
                const queryParams = new URLSearchParams({
                    page: currentPage.toString(),
                    itemsPerPage: itemsPerPage.toString(),
                });

                // Añadimos TODOS los filtros al query string
                if (debouncedFilters.name) queryParams.append("name", debouncedFilters.name);
                if (debouncedFilters.createdAt) queryParams.append("createdAt", debouncedFilters.createdAt);
                if (debouncedFilters.kcal) queryParams.append("kcal", debouncedFilters.kcal);
                if (debouncedFilters.status) queryParams.append("status", debouncedFilters.status);

                const dietsRes = await fetchWithAuth(`/api/patients/${patientId}/diets?${queryParams.toString()}`);

                if (dietsRes.ok) {
                    const dData = await dietsRes.json();
                    setDiets(dData.data || []);
                    setTotalItems(dData.total || 0);
                }
            } catch (error) {
                console.error("Error cargando dietas:", error);
            } finally {
                setIsLoading(false);
            }
        };

        if (patientId && isMounted) {
            fetchDiets();
        }
    }, [patientId, currentPage, itemsPerPage, debouncedFilters, isMounted]);

    const totalPages = Math.ceil(totalItems / itemsPerPage);

    const handleFilterChange = (col: string, val: string) => {
        setFilters((prev) => ({ ...prev, [col]: val }));
    };

    const handlePageChange = (page: number) => {
        setCurrentPage(page);
    };

    const handleDelete = async () => {
        if (!dietToDelete) return;
        setIsDeleting(true);

        try {
            const response = await fetchWithAuth(`/api/diets/${dietToDelete.id}`, {
                method: "DELETE",
            });

            if (response.ok) {
                setDietToDelete(null);
                // Refrescar la lista incluyendo todos los filtros activos
                const queryParams = new URLSearchParams({
                    page: currentPage.toString(),
                    itemsPerPage: itemsPerPage.toString(),
                });
                if (debouncedFilters.name) queryParams.append("name", debouncedFilters.name);
                if (debouncedFilters.createdAt) queryParams.append("createdAt", debouncedFilters.createdAt);
                if (debouncedFilters.kcal) queryParams.append("kcal", debouncedFilters.kcal);
                if (debouncedFilters.status) queryParams.append("status", debouncedFilters.status);

                const res = await fetchWithAuth(`/api/patients/${patientId}/diets?${queryParams.toString()}`);
                if (res.ok) {
                    const responseJson = await res.json();
                    setDiets(responseJson.data || []);
                    setTotalItems(responseJson.total || 0);
                }
            } else {
                const errorData = await response.json();
                alert(errorData.error || "No se pudo eliminar la dieta.");
            }
        } catch (error) {
            alert("Error de red al eliminar.");
        } finally {
            setIsDeleting(false);
        }
    };

    // Guardia de hidratación: Renderiza una pantalla de carga neutral en el servidor
    if (!isMounted) {
        return (
            <div className="min-h-screen bg-slate-50 flex items-center justify-center">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-slate-900"></div>
            </div>
        );
    }

    return (
        <main className="min-h-screen bg-slate-50 p-6 text-slate-900">
            <div className="mx-auto max-w-6xl">
                {/* Botón de retorno al directorio */}
                <Link
                    href="/dashboard/patients"
                    className="mb-4 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-800 transition-colors"
                >
                    <ChevronLeft className="h-4 w-4" />
                    Volver al directorio
                </Link>

                {/* Cabecera del CRUD */}
                <div className="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-slate-200 pb-6">
                    <div>
                        <span className="text-xs font-mono font-semibold uppercase tracking-wider text-blue-600">
                            ID Médico: {patient?.historyNumber || "..."}
                        </span>
                        <h1 className="text-3xl font-bold text-slate-800 tracking-tight mt-1">
                            Historial de Dietas: {patient?.name || "..."}
                        </h1>
                        <p className="text-sm text-slate-500 mt-1">
                            Gestión, auditoría y creación de pautas
                            nutricionales personalizadas.
                        </p>
                    </div>

                    {/* Botón de creación con booleano estricto */}
                    <button
                        onClick={() =>
                            router.push(`/dashboard/rag?patientId=${patientId}`)
                        }
                        disabled={Boolean(isLoading)}
                        className="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition-colors hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-600/30 disabled:opacity-50"
                    >
                        <Plus className="size-4" />
                        Diseñar Nueva Dieta (RAG)
                    </button>
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
                                setCurrentPage(1);
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
                <PatientDietsTable
                    diets={diets}
                    isLoading={isLoading}
                    totalItems={totalItems}
                    currentPage={currentPage}
                    totalPages={totalPages}
                    itemsPerPage={itemsPerPage}
                    onFilterChange={handleFilterChange}
                    onPageChange={handlePageChange}
                    onDelete={setDietToDelete}
                />

                {/* Modal de Confirmación */}
                {dietToDelete && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
                        <div className="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                            <div className="flex items-center gap-4 text-red-600 mb-4">
                                <div className="p-3 bg-red-100 rounded-full">
                                    <AlertTriangle className="h-6 w-6" />
                                </div>
                                <h3 className="text-lg font-bold text-slate-900">
                                    ¿Eliminar dieta?
                                </h3>
                            </div>
                            <p className="text-slate-600 text-sm mb-6 leading-relaxed">
                                Estás a punto de eliminar{" "}
                                <strong className="text-slate-800">"{dietToDelete.name}"</strong>. 
                                Esta acción no se puede deshacer.
                            </p>
                            <div className="flex justify-end gap-3">
                                <button
                                    onClick={() => setDietToDelete(null)}
                                    disabled={Boolean(isDeleting)}
                                    className="px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors disabled:opacity-50"
                                >
                                    Cancelar
                                </button>
                                <button
                                    onClick={handleDelete}
                                    disabled={Boolean(isDeleting)}
                                    className="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-xl transition-colors shadow-sm focus:ring-2 focus:ring-red-500/20 disabled:opacity-50"
                                >
                                    {isDeleting ? "Eliminando..." : "Sí, eliminar"}
                                </button>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </main>
    );
}