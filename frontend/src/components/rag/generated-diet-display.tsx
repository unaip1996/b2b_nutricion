"use client";

import { AlertCircle, Clock, Flame, Info, Utensils, Activity } from "lucide-react";
import { useMemo } from "react";

// Interfaces que coinciden con nuestro OpenAI JSON Schema
interface DietMealItem {
    foodName: string;
    quantity: string;
    kcal: number;
    proteins: number;
    carbs: number;
    fats: number;
}

interface DietMeal {
    type: string;
    time: string;
    items: DietMealItem[];
}

interface DietDay {
    dayNumber: number;
    meals: DietMeal[];
}

interface DietPlan {
    observations: string;
    totalKcal: number;
    days: DietDay[];
}

interface GeneratedDietDisplayProps {
    dietContent: string;
}

export function GeneratedDietDisplay({ dietContent }: GeneratedDietDisplayProps) {
    // Intentamos parsear el JSON de forma segura
    const plan = useMemo<DietPlan | null>(() => {
        try {
            if (!dietContent) return null;
            return JSON.parse(dietContent);
        } catch (e) {
            console.error("Error al parsear el JSON de la dieta", e);
            return null;
        }
    }, [dietContent]);

    if (!plan) {
        return (
            <div className="flex h-64 items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 text-slate-500">
                <div className="text-center">
                    <Utensils className="mx-auto mb-2 h-8 w-8 opacity-50" />
                    <p>No hay datos estructurados válidos para mostrar.</p>
                </div>
            </div>
        );
    }

    return (
        <div className="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
            {/* Cabecera Clínica: Observaciones y Kcal Totales */}
            <div className="rounded-xl border border-blue-100 bg-blue-50/50 p-5 shadow-sm">
                <div className="mb-3 flex items-start justify-between">
                    <h3 className="flex items-center gap-2 font-semibold text-blue-900">
                        <Info className="h-5 w-5 text-blue-600" />
                        Justificación Clínica (RAG)
                    </h3>
                    <div className="flex items-center gap-1.5 rounded-full bg-blue-100 px-3 py-1 text-sm font-bold text-blue-800">
                        <Flame className="h-4 w-4" />
                        {plan.totalKcal} kcal / día
                    </div>
                </div>
                <p className="text-sm leading-relaxed text-blue-800/80">
                    {plan.observations}
                </p>
            </div>

            {/* Listado de Días y Comidas */}
            <div className="space-y-8">
                {plan.days.map((day) => (
                    <div key={`day-${day.dayNumber}`} className="space-y-4">
                        <h4 className="flex items-center gap-2 border-b pb-2 text-lg font-bold text-slate-800">
                            <span className="flex h-7 w-7 items-center justify-center rounded-md bg-slate-800 text-xs text-white">
                                D{day.dayNumber}
                            </span>
                            Día {day.dayNumber}
                        </h4>

                        <div className="grid gap-4 md:grid-cols-2">
                            {day.meals.map((meal, index) => (
                                <div key={`meal-${index}`} className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition-all hover:shadow-md">
                                    <div className="flex items-center justify-between border-b bg-slate-50 px-4 py-3">
                                        <h5 className="font-semibold text-slate-700">{meal.type}</h5>
                                        <div className="flex items-center gap-1.5 text-xs font-medium text-slate-500">
                                            <Clock className="h-3.5 w-3.5" />
                                            {meal.time}
                                        </div>
                                    </div>
                                    
                                    <div className="p-4">
                                        <ul className="space-y-3">
                                            {meal.items.map((item, i) => (
                                                <li key={`item-${i}`} className="flex flex-col gap-1 text-sm">
                                                    <div className="flex items-start justify-between">
                                                        <span className="font-medium text-slate-800">
                                                            {item.foodName}
                                                        </span>
                                                        <span className="shrink-0 text-slate-500">
                                                            {item.quantity}
                                                        </span>
                                                    </div>
                                                    
                                                    {/* Badge de Macros */}
                                                    <div className="flex flex-wrap items-center gap-2 text-[11px] text-slate-500">
                                                        <span className="flex items-center gap-1 font-medium text-amber-600">
                                                            <Flame className="h-3 w-3" /> {item.kcal} kcal
                                                        </span>
                                                        <span className="text-slate-300">|</span>
                                                        <span title="Proteínas">P: {item.proteins}g</span>
                                                        <span className="text-slate-300">|</span>
                                                        <span title="Carbohidratos">C: {item.carbs}g</span>
                                                        <span className="text-slate-300">|</span>
                                                        <span title="Grasas">G: {item.fats}g</span>
                                                    </div>
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}