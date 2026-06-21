"use client";

import { useRouter } from "next/navigation";
import { FileSpreadsheet, Plus, Calendar, Eye, ShieldCheck } from "lucide-react";
import { useState, useEffect } from "react";

interface DietPlan {
    id: string;
    createdAt: string;
    status: "Borrador" | "Validado";
    kcal: number;
}

export function PatientDietsSection({ patientId }: { patientId: string }) {
    const router = useRouter();
    const [diets, setDiets] = useState<DietPlan[]>([]);
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        const fetchDiets = async () => {
            try {
                const token = document.cookie.split("; ").find(row => row.startsWith("auth_token="))?.split("=")[1];
                const res = await fetch(`http://localhost:8000/api/patients/${patientId}/diets`, {
                    headers: { Authorization: `Bearer ${token}` }
                });
                if (res.ok) {
                    const result = await res.json();
                    setDiets(result.data);
                }
            } catch (error) {
                console.error("Error al recuperar las dietas del paciente:", error);
            } finally {
                setIsLoading(false);
            }
        };

        fetchDiets();
    }, [patientId]);

    return (
        <div className="space-y-6">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 className="text-lg font-bold text-slate-900">Planes Dietéticos</h3>
                    <p className="text-sm text-slate-500">Historial de pautas e inferencias clínicas generadas para este expediente.</p>
                </div>
                
                {/* Botón clave: Redirige al RAG pasando el patientId como Query Parameter */}
                <button
                    onClick={() => router.push(`/dashboard/rag?patientId=${patientId}`)}
                    className="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition-colors hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-600/30"
                >
                    <Plus className="size-4" />
                    Diseñar Dieta con IA
                </button>
            </div>

            <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div className="overflow-x-auto">
                    <table className="w-full text-left text-sm text-slate-600">
                        <thead className="bg-slate-50/50 border-b border-slate-200">
                            <tr>
                                <th className="p-4 text-xs font-semibold uppercase tracking-wide text-slate-500">Estructura</th>
                                <th className="p-4 text-xs font-semibold uppercase tracking-wide text-slate-500">Fecha Creación</th>
                                <th className="p-4 text-xs font-semibold uppercase tracking-wide text-slate-500">Ajuste Calórico</th>
                                <th className="p-4 text-xs font-semibold uppercase tracking-wide text-slate-500">Estado</th>
                                <th className="p-4 text-xs font-semibold uppercase tracking-wide text-slate-500 text-right">Acción</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {isLoading ? (
                                <tr><td colSpan={5} className="p-8 text-center text-slate-400">Cargando historial dietético...</td></tr>
                            ) : diets.length === 0 ? (
                                <tr><td colSpan={5} className="p-8 text-center text-slate-400">Este paciente aún no dispone de planes nutricionales autónomos.</td></tr>
                            ) : (
                                diets.map((diet) => (
                                    <tr key={diet.id} className="transition-colors hover:bg-slate-50/50">
                                        <td className="p-4 font-medium text-slate-800 flex items-center gap-2">
                                            <FileSpreadsheet className="h-4 w-4 text-slate-400" />
                                            Plan Nutricional de 4 Niveles
                                        </td>
                                        <td className="p-4 text-xs font-mono">
                                            <span className="inline-flex items-center gap-1">
                                                <Calendar className="h-3 w-3 text-slate-400" />
                                                {new \DateTime(diet.createdAt).format('d M Y')} {/* Ajustar según formato real */}
                                            </span>
                                        </td>
                                        <td className="p-4 font-medium text-slate-900">{diet.kcal} kcal</td>
                                        <td className="p-4">
                                            <span className={`inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium border ${
                                                diet.status === "Validado" 
                                                    ? "bg-emerald-50 text-emerald-700 border-emerald-200" 
                                                    : "bg-amber-50 text-amber-700 border-amber-200"
                                            }`}>
                                                {diet.status === "Validado" && <ShieldCheck className="h-3 w-3" />}
                                                {diet.status}
                                            </span>
                                        </td>
                                        <td className="p-4 text-right">
                                            <button 
                                                onClick={() => router.push(`/dashboard/diets/${diet.id}`)}
                                                className="text-slate-500 hover:text-blue-600 transition-colors inline-flex items-center gap-1 text-xs font-medium"
                                            >
                                                <Eye className="h-3 w-3" />
                                                Auditar
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
    );
}