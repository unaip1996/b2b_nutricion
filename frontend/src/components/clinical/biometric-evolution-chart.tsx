"use client";

import React, { useState } from "react";
import {
    LineChart,
    Line,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    Legend,
    ResponsiveContainer,
} from "recharts";
import { Scale, TrendingUp, Percent } from "lucide-react";

interface MeasurementData {
    id: string;
    weight: number | null;
    bodyFatPercentage: number | null;
    muscleMass: number | null;
    waistCircumference: number | null;
    takenAt: string;
}

interface BiometricEvolutionChartProps {
    measurements: MeasurementData[];
}

export function BiometricEvolutionChart({ measurements }: BiometricEvolutionChartProps) {
    const [activeMetric, setActiveMetric] = useState<"weight" | "composition">("weight");

    // Ordenamos cronológicamente las mediciones antes de pasárselas a Recharts
    const chartData = React.useMemo(() => {
        return [...measurements]
            .sort((a, b) => new Date(a.takenAt).getTime() - new Date(b.takenAt).getTime())
            .map((m) => ({
                fecha: new Date(m.takenAt).toLocaleDateString("es-ES", {
                    day: "numeric",
                    month: "short",
                }),
                Peso: m.weight || 0,
                Grasa: m.bodyFatPercentage || 0,
                Músculo: m.muscleMass || 0,
            }));
    }, [measurements]);

    if (!measurements || measurements.length === 0) {
        return (
            <div className="flex h-64 flex-col items-center justify-center rounded-xl border border-dashed border-slate-200 bg-slate-50/50 p-6 text-center text-slate-400">
                <TrendingUp className="mb-2 h-8 w-8 opacity-40" />
                <p className="text-sm">No hay registros biométricos suficientes para trazar la evolución.</p>
            </div>
        );
    }

    return (
        <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            {/* Cabecera del Componente con Conmutador de Métricas */}
            <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 className="text-base font-bold text-slate-900 flex items-center gap-2">
                        <TrendingUp className="h-4 w-4 text-slate-500" />
                        Evolución Biométrica del Paciente
                    </h3>
                    <p className="text-xs text-slate-500">Histórico de composición corporal y antropometría</p>
                </div>

                <div className="flex rounded-lg bg-slate-100 p-1">
                    <button
                        type="button"
                        onClick={() => setActiveMetric("weight")}
                        className={`flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-medium transition-all ${
                            activeMetric === "weight"
                                ? "bg-white text-slate-900 shadow-sm"
                                : "text-slate-600 hover:text-slate-900"
                        }`}
                    >
                        <Scale className="h-3.5 w-3.5" />
                        Control de Peso
                    </button>
                    <button
                        type="button"
                        onClick={() => setActiveMetric("composition")}
                        className={`flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-medium transition-all ${
                            activeMetric === "composition"
                                ? "bg-white text-slate-900 shadow-sm"
                                : "text-slate-600 hover:text-slate-900"
                        }`}
                    >
                        <Percent className="h-3.5 w-3.5" />
                        Masa Magra / Grasa
                    </button>
                </div>
            </div>

            {/* El Contenedor de la Gráfica */}
            <div className="h-72 w-full">
                <ResponsiveContainer width="100%" height="100%">
                    <LineChart data={chartData} margin={{ top: 10, right: 10, left: -20, bottom: 0 }}>
                        <CartesianGrid strokeDasharray="3 3" stroke="#f1f5f9" vertical={false} />
                        <XAxis 
                            dataKey="fecha" 
                            stroke="#94a3b8" 
                            fontSize={11}
                            tickLine={false}
                            axisLine={false}
                        />
                        <YAxis 
                            stroke="#94a3b8" 
                            fontSize={11}
                            tickLine={false}
                            axisLine={false}
                            domain={activeMetric === "weight" ? ["dataMin - 3", "dataMax + 3"] : [0, "auto"]}
                        />
                        <Tooltip 
                            contentStyle={{ background: "#fff", border: "1px solid #e2e8f0", borderRadius: "12px", fontSize: "12px" }}
                        />
                        <Legend wrapperStyle={{ fontSize: "12px", paddingTop: "10px" }} />
                        
                        {activeMetric === "weight" ? (
                            <Line
                                type="monotone"
                                dataKey="Peso"
                                stroke="#3b82f6"
                                strokeWidth={2.5}
                                activeDot={{ r: 6 }}
                                dot={{ strokeWidth: 2, r: 3 }}
                                unit=" kg"
                            />
                        ) : (
                            <>
                                <Line
                                    type="monotone"
                                    dataKey="Grasa"
                                    stroke="#ef4444"
                                    strokeWidth={2}
                                    dot={{ r: 2 }}
                                    unit=" %"
                                />
                                <Line
                                    type="monotone"
                                    dataKey="Músculo"
                                    stroke="#10b981"
                                    strokeWidth={2}
                                    dot={{ r: 2 }}
                                    unit=" kg"
                                />
                            </>
                        )}
                    </LineChart>
                </ResponsiveContainer>
            </div>
        </div>
    );
}