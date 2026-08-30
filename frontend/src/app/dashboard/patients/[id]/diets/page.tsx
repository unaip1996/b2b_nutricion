"use client";

import { useParams, useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import {
    FileSpreadsheet,
    Plus,
    Calendar,
    Eye,
    ShieldCheck,
    Pencil,
    ChevronLeft,
    Loader2,
    Trash2,
} from "lucide-react";
import Link from "next/link";

interface DietPlan {
    id: string;
    name: string;
    createdAt: string;
    status: string;
    kcal: number;
}

interface PatientHeaderData {
    name: string;
    historyNumber: string;
}

export default function PatientDietsPage() {
    const params = useParams();
    const router = useRouter();
    const patientId = params.id as string;

    const [diets, setDiets] = useState<DietPlan[]>([]);
    const [patient, setPatient] = useState<PatientHeaderData | null>(null);
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        const fetchPatientAndDiets = async () => {
            setIsLoading(true);
            try {
                const token = document.cookie
                    .split("; ")
                    .find((row) => row.startsWith("auth_token="))
                    ?.split("=")[1];

                // 1. Recuperar info básica del paciente para la cabecera
                const patientRes = await fetch(
                    `http://localhost:8000/api/patients/${patientId}`,
                    {
                        headers: { Authorization: `Bearer ${token}` },
                    },
                );

                // 2. Recuperar el listado de dietas del paciente
                const dietsRes = await fetch(
                    `http://localhost:8000/api/patients/${patientId}/diets`,
                    {
                        headers: { Authorization: `Bearer ${token}` },
                    },
                );

                if (patientRes.ok) {
                    const pData = await patientRes.json();
                    setPatient({
                        name: pData.data.name || "Paciente Anónimo",
                        historyNumber:
                            pData.data.medicalHistoryNumber ||
                            patientId.split("-")[0],
                    });
                }

                if (dietsRes.ok) {
                    const dData = await dietsRes.json();
                    setDiets(dData.data || []);
                }
            } catch (error) {
                console.error("Error al cargar el CRUD de dietas:", error);
            } finally {
                setIsLoading(false);
            }
        };

        if (patientId) {
            fetchPatientAndDiets();
        }
    }, [patientId]);

    const handleDeleteDiet = async (dietId: string) => {
        const confirmDelete = window.confirm(
            "¿Estás seguro de que deseas eliminar esta pauta? Esta acción no se puede deshacer de la vista.",
        );
        if (!confirmDelete) return;

        try {
            const token = document.cookie
                .split("; ")
                .find((row) => row.startsWith("auth_token="))
                ?.split("=")[1];
            const res = await fetch(
                `http://localhost:8000/api/diets/${dietId}`,
                {
                    method: "DELETE",
                    headers: { Authorization: `Bearer ${token}` },
                },
            );

            if (res.ok) {
                // Actualizamos el estado local para que desaparezca sin tener que recargar la página entera
                setDiets((prevDiets) =>
                    prevDiets.filter((diet) => diet.id !== dietId),
                );
            } else {
                const errorData = await res.json();
                alert(`Error al eliminar: ${errorData.error}`);
            }
        } catch (error) {
            console.error("Error en la petición de borrado:", error);
            alert("Error de red al intentar eliminar la pauta.");
        }
    };

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

                    {/* Botón de creación con redirección limpia al RAG pre-cargado */}
                    <button
                        onClick={() =>
                            router.push(`/dashboard/rag?patientId=${patientId}`)
                        }
                        suppressHydrationWarning={true}
                        disabled={Boolean(isLoading)}
                        className="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition-colors hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-600/30 disabled:opacity-50"
                    >
                        <Plus className="size-4" />
                        Diseñar Nueva Dieta (RAG)
                    </button>
                </div>

                {/* Tabla del CRUD */}
                <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm text-slate-600">
                            <thead className="bg-slate-50/50 border-b border-slate-200">
                                <tr>
                                    <th className="p-4 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Plan Nutricional
                                    </th>
                                    <th className="p-4 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Fecha de Inferencia
                                    </th>
                                    <th className="p-4 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Ajuste Energético
                                    </th>
                                    <th className="p-4 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Estado
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
                                            <div className="flex items-center justify-center gap-2">
                                                <Loader2 className="h-4 w-4 animate-spin text-blue-600" />
                                                Recuperando registros
                                                clínicos...
                                            </div>
                                        </td>
                                    </tr>
                                ) : diets.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={5}
                                            className="p-8 text-center text-slate-400 italic"
                                        >
                                            Este expediente no registra planes
                                            dietéticos activos. Haz clic en
                                            "Diseñar Nueva Dieta" para iniciar
                                            el motor de IA.
                                        </td>
                                    </tr>
                                ) : (
                                    diets.map((diet) => (
                                        <tr
                                            key={diet.id}
                                            className="transition-colors hover:bg-slate-50/50"
                                        >
                                            <td className="p-4 font-medium text-slate-800 flex items-center gap-2">
                                                <FileSpreadsheet className="h-4 w-4 text-slate-400" />
                                                {diet.name}
                                            </td>
                                            <td className="p-4 text-xs font-mono">
                                                <span className="inline-flex items-center gap-1">
                                                    <Calendar className="h-3 w-3 text-slate-400" />
                                                    {diet.createdAt}
                                                </span>
                                            </td>
                                            <td className="p-4 font-semibold text-slate-900">
                                                {diet.kcal} kcal
                                            </td>
                                            <td className="p-4">
                                                <span
                                                    className={`inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium border ${
                                                        diet.status ===
                                                        "Validado"
                                                            ? "bg-emerald-50 text-emerald-700 border-emerald-200"
                                                            : "bg-amber-50 text-amber-700 border-amber-200"
                                                    }`}
                                                >
                                                    {diet.status ===
                                                        "Validado" && (
                                                        <ShieldCheck className="h-3 w-3" />
                                                    )}
                                                    {diet.status}
                                                </span>
                                            </td>
                                            <td className="whitespace-nowrap px-3 py-4 text-sm text-slate-500 text-right">
                                                <button
                                                    onClick={() =>
                                                        router.push(`/dashboard/diets/${diet.id}`)
                                                    }
                                                    className="inline-flex items-center justify-center p-2 text-slate-400 transition-colors hover:text-blue-600 hover:bg-blue-50 rounded-lg focus:outline-none"
                                                    title="Editar pauta"
                                                >
                                                    <Pencil className="size-4" />
                                                </button>
                                                <button
                                                    onClick={() =>
                                                        handleDeleteDiet(
                                                            diet.id,
                                                        )
                                                    }
                                                    className="inline-flex items-center justify-center p-2 text-slate-400 transition-colors hover:text-red-600 hover:bg-red-50 rounded-lg focus:outline-none"
                                                    title="Eliminar pauta"
                                                >
                                                    <Trash2 className="size-4" />
                                                </button>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    );
}
