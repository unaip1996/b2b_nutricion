"use client";

import { useState, useEffect, Suspense } from "react";
import { useSearchParams } from "next/navigation";
import {
    BrainCircuit,
    Loader2,
    User,
    Activity,
    ChevronDown,
    Sparkles,
    Calendar,
    Flame
} from "lucide-react";

interface PatientSelectData {
    id: string;
    name: string;
    metricsSummary: string;
}

function RagContent() {
    const searchParams = useSearchParams();
    const urlPatientId = searchParams.get("patientId");

    const [patients, setPatients] = useState<PatientSelectData[]>([]);
    const [selectedPatientId, setSelectedPatientId] = useState<string>("");
    const [activePatient, setActivePatient] = useState<PatientSelectData | null>(null);
    const [query, setQuery] = useState("");
    const [response, setResponse] = useState<string | null>(null);
    const [isGenerating, setIsGenerating] = useState(false);
    const [isLoadingPatients, setIsLoadingPatients] = useState(false);

    // CÁLCULO DE FECHA POR DEFECTO: 30 días a partir de hoy
    const defaultEndDate = new Date();
    defaultEndDate.setDate(defaultEndDate.getDate() + 30);

    // NUEVOS ESTADOS: Parámetros estructurados clínicos
    const [kcal, setKcal] = useState<number>(2000);
    const [startDate, setStartDate] = useState<string>(new Date().toISOString().split("T")[0]);
    const [endDate, setEndDate] = useState<string>(defaultEndDate.toISOString().split("T")[0]);

    useEffect(() => {
        if (urlPatientId) {
            setSelectedPatientId(String(urlPatientId));
        }
    }, [urlPatientId]);

    useEffect(() => {
        const loadPatients = async () => {
            setIsLoadingPatients(true);
            try {
                const token = document.cookie
                    .split("; ")
                    .find((row) => row.startsWith("auth_token="))
                    ?.split("=")[1];
                const res = await fetch("http://localhost:8000/api/patients", {
                    headers: { Authorization: `Bearer ${token}` },
                });

                if (res.ok) {
                    const result = await res.json();
                    const mapped = result.data.map((p: any) => ({
                        id: String(p.id),
                        name: p.name || "Paciente Anónimo",
                        metricsSummary: `${p.age || "--"} años | IMC: ${p.bmi || "--"} | ${p.condition || "Sin patologías registradas"}`,
                    }));

                    setPatients(mapped);

                    if (urlPatientId) {
                        const target = mapped.find((item: any) => item.id === String(urlPatientId));
                        if (target) {
                            setActivePatient(target);
                        }
                    }
                }
            } catch (error) {
                console.error("Error al cargar pacientes:", error);
            } finally {
                setIsLoadingPatients(false);
            }
        };

        loadPatients();
    }, [urlPatientId]);

    // Sincronizar plantilla de prompt dinámicamente cuando cambian las kcal
    useEffect(() => {
        if (activePatient) {
            const template = `Diseñar una pauta nutricional clínica para ${activePatient.name}.

Contexto Biométrico:
- ${activePatient.metricsSummary}

Directrices Operativas:
- Objetivo calórico asignado en sistema: ${kcal} kcal
- Distribución de macronutrientes: [EJ: 40% HC, 30% PROT, 30% GRASAS]
- Restricciones absolutas: [INDICAR ALÉRGENOS O TEXTURAS]
- Número de ingestas: [INDICAR CANTIDAD]

Instrucción de Inferencia:
Cruzar estos parámetros con las guías clínicas indexadas para emitir un protocolo seguro y justificado.`;
            setQuery(template);
        } else {
            setQuery("");
        }
    }, [activePatient, kcal]);

    const handlePatientChange = (id: string) => {
        setSelectedPatientId(id);
        const target = patients.find((p) => p.id === id) || null;
        setActivePatient(target);
        setResponse(null);
    };

    const handleExecuteInference = async () => {
        if (!selectedPatientId || !query.trim() || !startDate || !endDate || !kcal) return;
        setIsGenerating(true);

        try {
            const token = document.cookie
                .split("; ")
                .find((row) => row.startsWith("auth_token="))
                ?.split("=")[1];
            const res = await fetch("http://localhost:8000/api/diets/generate", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Authorization: `Bearer ${token}`,
                },
                body: JSON.stringify({
                    patientId: selectedPatientId,
                    query: query,
                    kcal: Number(kcal),       // Enviado como integer estructurado
                    startDate: startDate,     // Enviado como Y-m-d
                    endDate: endDate          // Enviado como Y-m-d
                }),
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
        <div className="mx-auto max-w-7xl">
            <header className="mb-8">
                <h1 className="text-3xl font-bold text-slate-800 tracking-tight">
                    Orquestador de Inferencia RAG
                </h1>
                <p className="mt-1 text-sm text-slate-500">
                    Generación automatizada fundamentada en la base de conocimiento vectorial e historiales clínicos.
                </p>
            </header>

            <div className="grid gap-6 lg:grid-cols-2">
                {/* PANEL IZQUIERDO: CONFIGURACIÓN */}
                <div className="flex flex-col gap-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    
                    {/* Bloque de Expediente */}
                    <div className="rounded-xl bg-slate-50 p-4 border border-slate-100">
                        <label className="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">
                            Expediente Clínico Destinatario
                        </label>

                        {urlPatientId ? (
                            <div className="flex items-center justify-between py-1">
                                <span className="text-sm font-semibold text-slate-800 flex items-center gap-2">
                                    <User className="h-4 w-4 text-blue-600" />
                                    {activePatient ? activePatient.name : "Localizando expediente..."}
                                </span>
                                <span className="text-xs bg-blue-50 text-blue-700 px-2.5 py-1 rounded-md font-medium border border-blue-100">
                                    Enrutado Directo
                                </span>
                            </div>
                        ) : (
                            <div className="relative">
                                <select
                                    value={selectedPatientId}
                                    onChange={(e) => handlePatientChange(e.target.value)}
                                    disabled={isLoadingPatients}
                                    className="w-full appearance-none rounded-xl border border-slate-200 bg-white py-2.5 pl-4 pr-10 text-sm font-medium text-slate-800 outline-none shadow-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 disabled:opacity-50"
                                >
                                    <option value="">-- Selecciona un paciente del directorio --</option>
                                    {patients.map((p) => (
                                        <option key={p.id} value={p.id}>{p.name}</option>
                                    ))}
                                </select>
                                <ChevronDown className="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
                            </div>
                        )}

                        {activePatient && (
                            <div className="mt-3 pt-3 border-t border-slate-200/60 text-xs text-slate-600 flex items-center gap-2 font-mono">
                                <Activity className="h-3.5 w-3.5 text-blue-600" />
                                {activePatient.metricsSummary}
                            </div>
                        )}
                    </div>

                    {/* SECCIÓN: Métricas Estructuradas Obligatorias */}
                    <div className="grid gap-4 sm:grid-cols-3 rounded-xl border border-slate-100 bg-slate-50/50 p-4">
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
                    <div className="flex-1 flex flex-col">
                        <div className="mb-2 flex items-center justify-between">
                            <label htmlFor="rag-instructions" className="block text-sm font-medium text-slate-700">
                                Directrices de Personalización
                            </label>
                            <span className="flex items-center gap-1 text-[10px] font-bold uppercase tracking-wider text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full border border-blue-100">
                                <Sparkles className="h-3 w-3" /> Prompt Estructurado
                            </span>
                        </div>
                        <textarea
                            id="rag-instructions"
                            value={query}
                            onChange={(e) => setQuery(e.target.value)}
                            className="w-full flex-1 min-h-[220px] resize-none rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm font-mono text-slate-800 outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-inner"
                        />
                    </div>

                    <button
                        onClick={handleExecuteInference}
                        suppressHydrationWarning={true}
                        disabled={
                            !selectedPatientId ||
                            query.trim() === "" ||
                            !startDate ||
                            !endDate ||
                            !kcal ||
                            isGenerating
                        }
                        className="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-slate-900 py-3 text-sm font-medium text-white shadow-sm transition-colors hover:bg-slate-800 disabled:opacity-40 focus:outline-none focus:ring-2 focus:ring-slate-900/30"
                    >
                        {isGenerating ? (
                            <Loader2 className="h-4 w-4 animate-spin" />
                        ) : (
                            <BrainCircuit className="h-4 w-4" />
                        )}
                        {isGenerating
                            ? "Recuperando literatura y computando..."
                            : "Ejecutar Inferencia RAG"}
                    </button>
                </div>

                {/* PANEL DERECHO: VISTA DE LA PROPUESTA */}
                <div className="flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div className="border-b border-slate-100 bg-slate-50/50 p-4">
                        <h3 className="text-xs font-bold uppercase tracking-wider text-slate-700">
                            Propuesta Estructurada del Motor Clínico
                        </h3>
                    </div>
                    <div className="flex-1 bg-slate-50/30 p-6 text-sm text-slate-700">
                        {response ? (
                            <div className="prose prose-slate prose-sm max-w-none whitespace-pre-wrap font-sans leading-relaxed">
                                {response}
                            </div>
                        ) : (
                            <div className="flex h-full min-h-[400px] items-center justify-center italic text-slate-400 text-center px-8 text-balance">
                                Define los parámetros en el prompt izquierdo y ejecuta la inferencia para iniciar la búsqueda de similitud vectorial y generar la pauta.
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}

export default function RagPage() {
    return (
        <main className="min-h-screen bg-slate-50 p-6 text-slate-900">
            <Suspense
                fallback={
                    <div className="flex h-[60vh] items-center justify-center gap-2 text-slate-500">
                        <Loader2 className="h-5 w-5 animate-spin text-blue-600" />
                        Cargando entorno de inferencia clínica...
                    </div>
                }
            >
                <RagContent />
            </Suspense>
        </main>
    );
}