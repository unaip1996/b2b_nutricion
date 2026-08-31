"use client";

import { useState, useEffect } from "react";
import {
    BrainCircuit,
    Loader2,
    User,
    Activity,
    Flame,
    Calendar,
    Info,
    Sparkles
} from "lucide-react";
import { GeneratedDietDisplay } from "./generated-diet-display";

// Este componente ahora asume que recibe los datos del paciente desde la página padre
export function RagOrchestrator({ patientId, patientName, patientMetrics }: { patientId: string, patientName: string, patientMetrics: string }) {
    const [query, setQuery] = useState("");
    const [response, setResponse] = useState<string | null>(null);
    const [isGenerating, setIsGenerating] = useState(false);
    
    // CÁLCULO DE FECHA POR DEFECTO: 30 días a partir de hoy
    const defaultEndDate = new Date();
    defaultEndDate.setDate(defaultEndDate.getDate() + 30);

    // NUEVOS ESTADOS: Parámetros estructurados clínicos
    const [kcal, setKcal] = useState<number>(2000);
    const [startDate, setStartDate] = useState<string>(new Date().toISOString().split("T")[0]);
    const [endDate, setEndDate] = useState<string>(defaultEndDate.toISOString().split("T")[0]);

    // Plantilla base para directrices manuales
    useEffect(() => {
        const template = `- Distribución de macronutrientes: [EJ: 40% HC, 30% PROT, 30% GRASAS]\n- Número de ingestas: [EJ: 4 o 5 comidas]\n- Enfoque / Preferencias: [EJ: Priorizar recetas de alta saciedad, preparación rápida, etc.]`;
        setQuery(template);
        setResponse(null); // Reseteamos la respuesta si cambia el paciente
    }, [patientId]);

    const handleGenerate = async () => {
        if (!query.trim() || !kcal || !startDate || !endDate) return;
        setIsGenerating(true);

        try {
            const res = await fetchWithAuth(`/api/diets/generate`, {
                method: "POST",
                body: JSON.stringify({
                    patientId: patientId, // ID inyectado automáticamente
                    query: query,         // La directriz del nutricionista
                    kcal: Number(kcal),
                    startDate: startDate,
                    endDate: endDate
                })
            });

            if (res.ok) {
                const data = await res.json();
                setResponse(data.data.dietary_proposal);
            } else {
                const errorData = await res.json();
                alert(`Error: ${errorData.error || "Error en la inferencia del motor RAG."}`);
            }
        } catch (error) {
            console.error(error);
        } finally {
            setIsGenerating(false);
        }
    };

    return (
        <div className="grid gap-6 lg:grid-cols-2 lg:h-[calc(100vh-220px)]">
            {/* PANEL IZQUIERDO: CONFIGURACIÓN */}
            <div className="flex flex-col gap-5 overflow-y-auto rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                
                {/* Bloque de Expediente */}
                <div className="shrink-0 rounded-xl bg-slate-50 p-4 border border-slate-100">
                    <label className="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">
                        Paciente Activo
                    </label>
                    <div className="flex items-center gap-2 py-1">
                        <User className="h-4 w-4 text-blue-600" />
                        <span className="text-sm font-semibold text-slate-800">{patientName}</span>
                    </div>
                    <div className="mt-3 pt-3 border-t border-slate-200/60 text-xs text-slate-600 flex items-center gap-2 font-mono">
                        <Activity className="h-3.5 w-3.5 text-blue-600" />
                        {patientMetrics}
                    </div>
                </div>

                {/* SECCIÓN: Métricas Estructuradas Obligatorias */}
                <div className="shrink-0 grid gap-4 sm:grid-cols-3 rounded-xl border border-slate-100 bg-slate-50/50 p-4">
                    <div>
                        <label className="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500 flex items-center gap-1">
                            <Flame className="size-3 text-amber-500" /> Objetivo Kcal
                        </label>
                        <input
                            type="number"
                            value={kcal}
                            onChange={(e) => setKcal(Math.max(0, Number(e.target.value)))}
                            className="w-full rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-800 outline-none shadow-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600"
                            placeholder="Ej: 2000"
                        />
                    </div>
                    <div>
                        <label className="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500 flex items-center gap-1">
                            <Calendar className="size-3 text-blue-500" /> Fecha Inicio
                        </label>
                        <input
                            type="date"
                            value={startDate}
                            onChange={(e) => setStartDate(e.target.value)}
                            className="w-full rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-800 outline-none shadow-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600"
                        />
                    </div>
                    <div>
                        <label className="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500 flex items-center gap-1">
                            <Calendar className="size-3 text-blue-500" /> Fecha Fin
                        </label>
                        <input
                            type="date"
                            value={endDate}
                            onChange={(e) => setEndDate(e.target.value)}
                            className="w-full rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-800 outline-none shadow-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600"
                        />
                    </div>
                </div>

                {/* Cuadro de texto para Directrices del Prompt */}
                <div className="flex-1 flex flex-col min-h-[200px]">
                    <div className="mb-3 flex items-center justify-between shrink-0">
                        <label htmlFor="rag-instructions" className="block text-sm font-medium text-slate-700">
                            Directrices de Personalización
                        </label>
                        <span className="flex items-center gap-1 text-[10px] font-bold uppercase tracking-wider text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full border border-blue-100">
                            <Sparkles className="h-3 w-3" /> Prompt Estructurado
                        </span>
                    </div>
                    
                    <div className="mb-3 flex items-start gap-2 rounded-lg bg-blue-50/60 p-3 text-xs text-blue-700 border border-blue-100/50">
                        <Info className="h-4 w-4 shrink-0 mt-0.5 text-blue-500" />
                        <p>
                            El peso, alergias y patologías de este paciente se inyectan en el motor clínico de forma invisible y segura.
                        </p>
                    </div>

                    <textarea
                        id="rag-instructions"
                        value={query}
                        onChange={(e) => setQuery(e.target.value)}
                        className="w-full flex-1 resize-none rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm font-mono text-slate-800 outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-inner"
                    />
                </div>

                <button
                    onClick={handleGenerate}
                    suppressHydrationWarning={true}
                    disabled={!query.trim() || !startDate || !endDate || !kcal || isGenerating || response !== null}
                    className="shrink-0 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-slate-900 py-3 text-sm font-medium text-white shadow-sm transition-colors hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-slate-900/30"
                >
                    {isGenerating ? <Loader2 className="h-4 w-4 animate-spin" /> : <BrainCircuit className="h-4 w-4" />}
                    {isGenerating ? "Recuperando conocimiento y computando..." : response ? "Dieta Generada Correctamente" : "Generar Dieta"}
                </button>
            </div>

            {/* PANEL DERECHO: VISTA DE LA PROPUESTA */}
            <div className="flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div className="border-b border-slate-100 bg-slate-50/50 p-4">
                    <h3 className="text-xs font-bold uppercase tracking-wider text-slate-700">
                        Propuesta Estructurada del Motor Clínico
                    </h3>
                </div>
                <div className="flex-1 overflow-y-auto bg-slate-50/30 p-6 text-sm text-slate-700">
                    {response ? (
                        <GeneratedDietDisplay dietContent={response} />
                    ) : (
                        <div className="flex h-full items-center justify-center p-8 text-center text-slate-500 italic">
                            Completa los parámetros y pulsa "Generar Dieta" para comenzar.
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}