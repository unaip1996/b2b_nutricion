"use client";

import { useEffect, useState } from "react";
import { 
    Users, BrainCircuit, FileText, Loader2, 
    ArrowRight, Activity, Calendar, PieChart as PieIcon 
} from "lucide-react";
import Link from "next/link";
import { ResponsiveContainer, PieChart, Pie, Cell, Tooltip, Legend } from "recharts";
import { fetchWithAuth } from "@/lib/auth";

interface DashboardData {
    kpis: {
        totalPatients: number;
        totalDiets: number;
        knowledgeBaseSize: number;
    };
    recentDiets: Array<{
        id: string;
        patientName: string;
        name: string;
        kcal: number;
        status: string;
        date: string;
    }>;
    chartData: Array<{
        name: string;
        value: number;
    }>;
}

// Colores corporativos coherentes con los badges de estado
// Activos = Verde, Programados = Azul, Expirados = Gris/Slate
const STATUS_COLORS: Record<string, string> = {
    "Activos": "#10b981",
    "Programados": "#3b82f6",
    "Expirados": "#64748b"
};

export default function DashboardPage() {
    const [data, setData] = useState<DashboardData | null>(null);
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        const fetchDashboardStats = async () => {
            setIsLoading(true);
            try {
                const res = await fetchWithAuth(`/api/dashboard/stats`);

                if (res.ok) {
                    const result = await res.json();
                    setData(result.data);
                }
            } catch (error) {
                console.error("Error cargando analíticas de cuadro de mando:", error);
            } finally {
                setIsLoading(false);
            }
        };

        fetchDashboardStats();
    }, []);

    if (isLoading) {
        return (
            <div className="flex h-[70vh] items-center justify-center gap-2">
                <Loader2 className="h-6 w-6 animate-spin text-blue-600" />
                <span className="text-sm font-medium text-slate-500">Cargando...</span>
            </div>
        );
    }

    if (!data) return <div className="p-6 text-red-500">Error al inicializar el panel analítico.</div>;

    return (
        <div className="mx-auto max-w-7xl space-y-8">
            {/* CABECERA */}
            <header>
                <p className="mt-1 text-sm text-slate-500">
                </p>
                <h1 className="text-3xl font-bold tracking-tight text-slate-800">Panel nutricional</h1>
            </header>

            {/* TARJETAS KPI */}
            <div className="grid gap-5 sm:grid-cols-3">
                <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm flex items-center justify-between">
                    <div className="space-y-2">
                        <span className="text-xs font-semibold text-slate-400 uppercase tracking-wider">Pacientes Totales</span>
                        <h3 className="text-3xl font-bold text-slate-800 tracking-tight">{data.kpis.totalPatients}</h3>
                    </div>
                    <div className="p-3 bg-slate-50 rounded-xl border border-slate-100 text-slate-700">
                        <Users className="size-5" />
                    </div>
                </div>

                <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm flex items-center justify-between">
                    <div className="space-y-2">
                        <span className="text-xs font-semibold text-slate-400 uppercase tracking-wider">Dietas IA Emitidas</span>
                        <h3 className="text-3xl font-bold text-slate-800 tracking-tight">{data.kpis.totalDiets}</h3>
                    </div>
                    <div className="p-3 bg-blue-50 rounded-xl border border-blue-100 text-blue-600">
                        <BrainCircuit className="size-5" />
                    </div>
                </div>

                <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm flex items-center justify-between">
                    <div className="space-y-2">
                        <span className="text-xs font-semibold text-slate-400 uppercase tracking-wider">Documentos Vectorizados</span>
                        <h3 className="text-3xl font-bold text-slate-800 tracking-tight">{data.kpis.knowledgeBaseSize}</h3>
                    </div>
                    <div className="p-3 bg-emerald-50 rounded-xl border border-emerald-100 text-emerald-600">
                        <FileText className="size-5" />
                    </div>
                </div>
            </div>

            <div className="grid gap-6 lg:grid-cols-3">
                {/* LISTADO DE INFERENCIAS RECIENTES */}
                <div className="lg:col-span-2 rounded-2xl border border-slate-200 bg-white shadow-sm flex flex-col overflow-hidden">
                    <div className="border-b border-slate-100 bg-slate-50/50 px-6 py-4 flex items-center justify-between">
                        <h2 className="text-sm font-bold uppercase tracking-wider text-slate-700 flex items-center gap-2">
                            <Activity className="size-4 text-blue-600" /> Auditoría RAG: Historial Reciente
                        </h2>
                        <Link href="/dashboard/patients" className="text-xs font-semibold text-blue-600 hover:text-blue-800 flex items-center gap-1">
                            Ver directorio <ArrowRight className="size-3" />
                        </Link>
                    </div>

                    <div className="flex-1 overflow-x-auto">
                        {data.recentDiets.length === 0 ? (
                            <div className="p-8 text-center text-slate-400 text-sm italic">
                                Aún no se han orquestado pautas mediante el motor de IA.
                            </div>
                        ) : (
                            <table className="w-full text-left border-collapse">
                                <thead>
                                    <tr className="border-b border-slate-100 text-[10px] font-bold uppercase text-slate-400 tracking-wider bg-slate-50/30">
                                        <th className="px-6 py-3">Paciente</th>
                                        <th className="px-6 py-3">Objetivo</th>
                                        <th className="px-6 py-3">Vigencia</th>
                                        <th className="px-6 py-3">Estado</th>
                                        <th className="px-6 py-3 text-right">Acción</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 text-sm font-medium text-slate-700">
                                    {data.recentDiets.map((diet) => (
                                        <tr key={diet.id} className="hover:bg-slate-50/50 transition-colors">
                                            <td className="px-6 py-4 text-slate-900">{diet.patientName}</td>
                                            <td className="px-6 py-4">
                                                <span className="inline-flex items-center gap-1 bg-slate-100 px-2.5 py-0.5 rounded-md text-xs font-semibold font-mono text-slate-700">
                                                    {diet.kcal} kcal
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 text-slate-500 text-xs flex items-center gap-1 mt-0.5">
                                                <Calendar className="size-3.5 text-slate-400" /> {diet.date}
                                            </td>
                                            <td className="px-6 py-4">
                                                <span className={`inline-flex items-center text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full border ${
                                                    diet.status === 'Activo' ? 'bg-emerald-50 text-emerald-700 border-emerald-100' :
                                                    diet.status === 'Programado' ? 'bg-blue-50 text-blue-700 border-blue-100' :
                                                    'bg-slate-50 text-slate-600 border-slate-200'
                                                }`}>
                                                    {diet.status}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 text-right">
                                                <Link href={`/dashboard/diets/${diet.id}`} className="text-xs font-bold text-slate-900 hover:text-blue-600 bg-slate-50 hover:bg-blue-50 border border-slate-200 hover:border-blue-200 px-3 py-1.5 rounded-lg transition-colors">
                                                    Abrir Editor
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        )}
                    </div>
                </div>

                {/* GRÁFICO CIRCULAR ACTUALIZADO: VIGENCIA DE PAUTAS */}
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm flex flex-col p-6">
                    <h2 className="text-sm font-bold uppercase tracking-wider text-slate-700 mb-4 flex items-center gap-2">
                        <PieIcon className="size-4 text-slate-600" /> Estado de Tratamientos
                    </h2>
                    
                    <div className="flex-1 min-h-[260px] flex items-center justify-center">
                        <ResponsiveContainer width="100%" height="100%">
                            <PieChart>
                                <Pie
                                    data={data.chartData.filter(item => item.value > 0)} // Solo pinta los que tengan datos
                                    cx="50%"
                                    cy="45%"
                                    innerRadius={60}
                                    outerRadius={85}
                                    paddingAngle={4}
                                    dataKey="value"
                                >
                                    {data.chartData.map((entry, index) => (
                                        <Cell 
                                            key={`cell-${index}`} 
                                            fill={STATUS_COLORS[entry.name] || "#cbd5e1"} 
                                        />
                                    ))}
                                </Pie>
                                <Tooltip 
                                    contentStyle={{ background: '#0f172a', color: '#fff', borderRadius: '12px', border: 'none', fontSize: '12px' }}
                                    itemStyle={{ color: '#fff' }}
                                />
                                <Legend 
                                    layout="horizontal" 
                                    verticalAlign="bottom" 
                                    align="center"
                                    iconType="circle"
                                    iconSize={8}
                                    wrapperStyle={{ fontSize: '12px', fontWeight: '600', color: '#475569' }}
                                />
                            </PieChart>
                        </ResponsiveContainer>
                    </div>
                </div>
            </div>
        </div>
    );
}