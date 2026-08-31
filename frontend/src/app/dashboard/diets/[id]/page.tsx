"use client";

import { useParams, useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import { fetchWithAuth } from "@/lib/auth";
import { 
    Loader2, Save, ChevronLeft, Calendar, Flame, 
    Trash2, PlusCircle, Clock, Utensils, Info 
} from "lucide-react";

// Interfaces para el estado del formulario
interface MealItemState {
    id?: string;
    foodItemId?: string;
    foodName: string;
    quantity: number;
    unit: string;
}

interface MealState {
    id?: string;
    name: string;
    mealTime: string;
    items: MealItemState[];
}

interface DietDayState {
    id?: string;
    dayNumber: number;
    meals: MealState[];
}

interface DietPlanState {
    id: string;
    name: string;
    kcal: number | null;
    startDate: string | null;
    endDate: string | null;
    observations: string | null;
    days: DietDayState[];
    patient?: {
        name: string;
    };
}

export default function EditDietPage() {
    const params = useParams();
    const router = useRouter();
    const dietId = params.id as string;

    const [diet, setDiet] = useState<DietPlanState | null>(null);
    const [isLoading, setIsLoading] = useState(true);
    const [isSaving, setIsSaving] = useState(false);

    useEffect(() => {
        if (!dietId) return;

        const fetchDiet = async () => {
            setIsLoading(true);
            try {
                const res = await fetchWithAuth(`/api/diets/${dietId}`);

                if (res.ok) {
                    const { data } = await res.json();
                    setDiet(data);
                } else {
                    alert("Error al cargar la dieta.");
                    router.back();
                }
            } catch (error) {
                console.error("Error fetching diet:", error);
                alert("Error de red al cargar la dieta.");
            } finally {
                setIsLoading(false);
            }
        };

        fetchDiet();
    }, [dietId, router]);
    
    const handleFieldChange = (field: keyof DietPlanState, value: any) => {
        if (!diet) return;
        setDiet({ ...diet, [field]: value });
    };
    
    const handleMealChange = (dayIndex: number, mealIndex: number, field: keyof MealState, value: string) => {
        if (!diet) return;
        const newDiet = structuredClone(diet);
        (newDiet.days[dayIndex].meals[mealIndex] as any)[field] = value;
        setDiet(newDiet);
    };

    const handleMealItemChange = (dayIndex: number, mealIndex: number, itemIndex: number, field: keyof MealItemState, value: any) => {
        if (!diet) return;
        const newDiet = structuredClone(diet);
        const item = newDiet.days[dayIndex].meals[mealIndex].items[itemIndex];
        (item as any)[field] = value;
        setDiet(newDiet);
    };

    const addMealItem = (dayIndex: number, mealIndex: number) => {
        if (!diet) return;
        const newDiet = structuredClone(diet);
        newDiet.days[dayIndex].meals[mealIndex].items.push({
            foodName: "",
            quantity: 1,
            unit: "ud"
        });
        setDiet(newDiet);
    };

    const removeMealItem = (dayIndex: number, mealIndex: number, itemIndex: number) => {
        if (!diet) return;
        const newDiet = structuredClone(diet);
        newDiet.days[dayIndex].meals[mealIndex].items.splice(itemIndex, 1);
        setDiet(newDiet);
    };

    const handleSave = async () => {
        if (!diet) return;
        setIsSaving(true);
        try {
            const res = await fetchWithAuth(`/api/diets/${dietId}`, {
                method: 'PUT',
                body: JSON.stringify(diet)
            });

            if (res.ok) {
                alert("Dieta actualizada correctamente.");
                router.back();
            } else {
                const errorData = await res.json();
                alert(`Error al guardar: ${errorData.error}`);
            }
        } catch (error) {
            console.error("Error saving diet:", error);
            alert("Error de red al guardar la dieta.");
        } finally {
            setIsSaving(false);
        }
    };

    if (isLoading) {
        return (
            <div className="flex h-screen items-center justify-center bg-slate-50">
                <Loader2 className="h-8 w-8 animate-spin text-blue-600" />
                <span className="ml-2 text-slate-600 font-medium tracking-tight">Cargando editor clínico...</span>
            </div>
        );
    }

    if (!diet) {
        return <div className="flex h-screen items-center justify-center text-red-500 font-medium">No se pudo cargar la pauta nutricional.</div>;
    }

    return (
        <main className="min-h-screen bg-slate-50 p-6 text-slate-900 pb-24">
            <div className="mx-auto max-w-5xl">
                {/* HEADER */}
                <button onClick={() => router.back()} className="mb-6 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-800 transition-colors">
                    <ChevronLeft className="h-4 w-4" />
                    Volver al listado
                </button>

                <div className="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between border-b border-slate-200 pb-6">
                    <div className="flex-1">
                        <h1 className="text-3xl font-bold text-slate-800 tracking-tight">
                            {diet.patient ? `${diet.patient.name}` : 'Modo Edición'}
                        </h1>
                        <input 
                            type="text" 
                            value={diet.name} 
                            onChange={e => handleFieldChange('name', e.target.value)}
                            className="mt-2 w-full max-w-md bg-transparent text-lg font-medium text-slate-600 outline-none border-b border-transparent hover:border-slate-300 focus:border-blue-600 transition-colors"
                            placeholder="Nombre de la dieta..."
                        />
                    </div>
                    <button onClick={handleSave} disabled={isSaving} className="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-slate-900 px-6 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-slate-800 disabled:opacity-50 transition-all focus:ring-2 focus:ring-slate-900/30">
                        {isSaving ? <Loader2 className="h-4 w-4 animate-spin" /> : <Save className="h-4 w-4" />}
                        {isSaving ? "Guardando..." : "Guardar Cambios"}
                    </button>
                </div>

                <div className="space-y-8">
                    {/* PANEL DE CONFIGURACIÓN GENERAL */}
                    <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 className="text-sm font-bold uppercase tracking-wider text-slate-700 mb-4">Parámetros Operativos</h2>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                            <div className="rounded-xl bg-slate-50 p-4 border border-slate-100">
                                <label className="mb-2 block text-xs font-semibold uppercase tracking-wider text-slate-500 flex items-center gap-1">
                                    <Flame className="size-3.5 text-amber-500" /> Objetivo Kcal
                                </label>
                                <input type="number" value={diet.kcal ?? ''} onChange={e => handleFieldChange('kcal', Number(e.target.value))} className="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-sm" />
                            </div>
                            <div className="rounded-xl bg-slate-50 p-4 border border-slate-100">
                                <label className="mb-2 block text-xs font-semibold uppercase tracking-wider text-slate-500 flex items-center gap-1">
                                    <Calendar className="size-3.5 text-blue-500" /> Fecha Inicio
                                </label>
                                <input type="date" value={diet.startDate || ''} onChange={e => handleFieldChange('startDate', e.target.value)} className="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-sm" />
                            </div>
                            <div className="rounded-xl bg-slate-50 p-4 border border-slate-100">
                                <label className="mb-2 block text-xs font-semibold uppercase tracking-wider text-slate-500 flex items-center gap-1">
                                    <Calendar className="size-3.5 text-blue-500" /> Fecha Fin
                                </label>
                                <input type="date" value={diet.endDate || ''} onChange={e => handleFieldChange('endDate', e.target.value)} className="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-sm" />
                            </div>
                        </div>

                        <div>
                            <div className="flex items-center gap-2 mb-2">
                                <label className="block text-xs font-semibold uppercase tracking-wider text-slate-500">Observaciones del LLM</label>
                                <span className="flex items-center gap-1 text-[10px] font-bold uppercase tracking-wider text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full border border-blue-100">
                                    <Info className="size-3" /> Solo Lectura
                                </span>
                            </div>
                            <div className="w-full rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600 min-h-[80px] whitespace-pre-wrap font-medium">
                                {diet.observations || 'No hay observaciones adicionales para esta pauta.'}
                            </div>
                        </div>
                    </div>

                    {/* DÍAS Y COMIDAS (ESTILO RAG INFERENCE) */}
                    <div className="space-y-10">
                        {diet.days.map((day, dayIndex) => (
                            <div key={day.id || dayIndex} className="relative rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                                <div className="absolute -top-3 left-6 flex h-7 items-center justify-center rounded-md bg-slate-800 px-3 text-xs font-bold text-white shadow-sm">
                                    DÍA {day.dayNumber}
                                </div>
                                
                                <div className="mt-4 grid gap-4 lg:grid-cols-3">
                                    {day.meals.map((meal, mealIndex) => (
                                        <div key={meal.id || mealIndex} className="overflow-hidden rounded-xl border border-slate-200 bg-white transition-all focus-within:ring-2 focus-within:ring-blue-500/20 focus-within:border-blue-400">
                                            {/* Header de la Comida */}
                                            <div className="flex items-center justify-between gap-2 border-b border-slate-100 bg-slate-50 px-4 py-3">
                                                <div className="flex items-center gap-2 flex-1">
                                                    <Utensils className="size-4 text-slate-400" />
                                                    <input 
                                                        type="text"
                                                        value={meal.name}
                                                        onChange={e => handleMealChange(dayIndex, mealIndex, 'name', e.target.value)}
                                                        className="w-full bg-transparent text-sm font-bold uppercase tracking-wider text-slate-700 outline-none border-b border-transparent focus:border-blue-500 transition-colors"
                                                        placeholder="Ej: Desayuno"
                                                    />
                                                </div>
                                                <div className="flex items-center gap-1 text-slate-500 shrink-0 bg-white px-2 py-1 rounded-md border border-slate-200">
                                                    <Clock className="size-3" />
                                                    <input 
                                                        type="time"
                                                        value={meal.mealTime}
                                                        onChange={e => handleMealChange(dayIndex, mealIndex, 'mealTime', e.target.value)}
                                                        className="bg-transparent text-xs font-medium outline-none"
                                                    />
                                                </div>
                                            </div>

                                            {/* Items de la Comida */}
                                            <div className="p-4">
                                                <div className="grid grid-cols-12 gap-2 items-center mb-2 px-1 pb-1 border-b border-slate-100">
                                                    <div className="col-span-6 text-[10px] font-bold uppercase text-slate-400 tracking-wider">Alimento</div>
                                                    <div className="col-span-2 text-[10px] font-bold uppercase text-slate-400 tracking-wider text-center">Cant.</div>
                                                    <div className="col-span-3 text-[10px] font-bold uppercase text-slate-400 tracking-wider">Und.</div>
                                                    <div className="col-span-1"></div>
                                                </div>
                                                
                                                <div className="space-y-1">
                                                    {meal.items.map((item, itemIndex) => (
                                                        <div key={item.id || itemIndex} className="grid grid-cols-12 gap-2 items-center group rounded-md hover:bg-slate-50 p-1 -mx-1 transition-colors">
                                                            <div className="col-span-6">
                                                                <input type="text" placeholder="Nombre" value={item.foodName} onChange={e => handleMealItemChange(dayIndex, mealIndex, itemIndex, 'foodName', e.target.value)} className="w-full bg-transparent text-sm font-medium text-slate-700 outline-none border-b border-transparent focus:border-slate-300" />
                                                            </div>
                                                            <div className="col-span-2">
                                                                <input type="number" placeholder="0" value={item.quantity} onChange={e => handleMealItemChange(dayIndex, mealIndex, itemIndex, 'quantity', Number(e.target.value))} className="w-full bg-transparent text-sm font-medium text-slate-700 text-center outline-none border-b border-transparent focus:border-slate-300" />
                                                            </div>
                                                            <div className="col-span-3">
                                                                <input type="text" placeholder="ud/g" value={item.unit} onChange={e => handleMealItemChange(dayIndex, mealIndex, itemIndex, 'unit', e.target.value)} className="w-full bg-transparent text-sm font-medium text-slate-500 outline-none border-b border-transparent focus:border-slate-300" />
                                                            </div>
                                                            <div className="col-span-1 flex justify-end">
                                                                <button onClick={() => removeMealItem(dayIndex, mealIndex, itemIndex)} className="text-slate-300 hover:text-red-500 transition-colors opacity-0 group-hover:opacity-100 focus:opacity-100">
                                                                    <Trash2 className="size-4" />
                                                                </button>
                                                            </div>
                                                        </div>
                                                    ))}
                                                </div>
                                                
                                                <button onClick={() => addMealItem(dayIndex, mealIndex)} className="mt-4 flex items-center gap-1.5 text-xs font-semibold text-blue-600 hover:text-blue-800 transition-colors bg-blue-50 hover:bg-blue-100 px-3 py-1.5 rounded-lg w-full justify-center">
                                                    <PlusCircle className="size-3.5" /> Añadir Ingrediente
                                                </button>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </main>
    );
}