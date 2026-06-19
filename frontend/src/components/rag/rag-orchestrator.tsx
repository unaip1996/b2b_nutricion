"use client";

import { useState } from "react";
import { BrainCircuit, Loader2, User, Activity } from "lucide-react";

// Este componente ahora asume que recibe los datos del paciente desde la página padre
export function RagOrchestrator({ patientId, patientName, patientMetrics }: { patientId: string, patientName: string, patientMetrics: string }) {
    const [query, setQuery] = useState("");
    const [response, setResponse] = useState<string | null>(null);
    const [isGenerating, setIsGenerating] = useState(false);

    const handleGenerate = async () => {
        if (!query.trim()) return;
        setIsGenerating(true);

        try {
            const token = document.cookie.split("; ").find(row => row.startsWith("auth_token="))?.split("=")[1];
            
            // Llamada al DietController que configuramos en el backend
            const res = await fetch("http://localhost:8000/api/diets/generate", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Authorization: `Bearer ${token}`
                },
                body: JSON.stringify({
                    patientId: patientId, // ID inyectado automáticamente
                    query: query          // La directriz del nutricionista
                })
            });

            if (res.ok) {
                const data = await res.json();
                setResponse(data.data.dietary_proposal);
            } else {
                alert("Error al generar la propuesta clínica.");
            }
        } catch (error) {
            console.error(error);
        } finally {
            setIsGenerating(false);
        }
    };

    return (
        <div className="grid gap-6 lg:grid-cols-2">
            {/* PANEL DE CONTEXTO (Izquierda) */}
            <div className="flex flex-col gap-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div className="border-b border-slate-100 pb-4">
                    <h3 className="text-sm font-semibold uppercase tracking-wide text-slate-900 flex items-center gap-2">
                        <User className="h-4 w-4 text-blue-600" />
                        Paciente Activo: {patientName}
                    </h3>
                    <p className="mt-1 text-xs text-slate-500 flex items-center gap-2">
                        <Activity className="h-3 w-3" />
                        {patientMetrics} {/* Ej: "29 años | 78kg | Celiaquía" */}
                    </p>
                </div>

                <div className="flex-1">
                    <label htmlFor="rag-query" className="mb-2 block text-sm font-medium text-slate-700">
                        Directriz Nutricional (Prompt)
                    </label>
                    <textarea
                        id="rag-query"
                        value={query}
                        onChange={(e) => setQuery(e.target.value)}
                        placeholder="Ej: Generar pauta de 2500 kcal distribuida en 4 ingestas. Priorizar volumen de alimentos por saciedad..."
                        className="h-48 w-full resize-none rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-900 outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20"
                    />
                </div>

                <button
                    onClick={handleGenerate}
                    disabled={isGenerating || !query.trim()}
                    className="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-3 text-sm font-medium text-white shadow-sm transition-colors hover:bg-slate-800 disabled:opacity-50 focus:outline-none focus:ring-2 focus:ring-slate-900/30"
                >
                    {isGenerating ? <Loader2 className="h-4 w-4 animate-spin" /> : <BrainCircuit className="h-4 w-4" />}
                    {isGenerating ? "Recuperando literatura y generando..." : "Ejecutar Inferencia (IA)"}
                </button>
            </div>

            {/* VISOR DE INFERENCIA (Derecha) */}
            <div className="flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div className="border-b border-slate-100 bg-slate-50/50 p-4">
                    <h3 className="text-sm font-semibold uppercase tracking-wide text-slate-900">
                        Respuesta del Motor Clínico
                    </h3>
                </div>
                <div className="flex-1 bg-slate-50 p-6 text-sm text-slate-700">
                    {response ? (
                        <div className="prose prose-slate prose-sm max-w-none whitespace-pre-wrap">
                            {response}
                        </div>
                    ) : (
                        <div className="flex h-full items-center justify-center italic text-slate-400 text-center text-balance">
                            Esperando directriz para iniciar la recuperación de documentos (RAG)...
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}