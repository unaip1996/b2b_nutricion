"use client";

import { useState, useEffect } from "react";
import { useParams, useRouter } from "next/navigation";
import { fetchWithAuth } from "@/lib/auth";
import { PatientHeader } from "@/components/clinical/patient-header";
import { PersonalDataColumn } from "@/components/clinical/personal-data-column";
import { ClinicalHistoryColumn } from "@/components/clinical/clinical-history-column";
import { Trash2 } from "lucide-react";
import { BiometricEvolutionChart } from "@/components/clinical/biometric-evolution-chart";

export default function PatientPage() {
    const params = useParams();
    const router = useRouter();
    const id = params?.id as string;

    const isCreateMode = id === "create" || id === "nuevo";

    const [formData, setFormData] = useState({
        name: "",
        age: "",
        gender: "",
        phone: "",
        email: "",
        weight: "",
        height: "",
        pathologies: "",
        goal: "",
        notes: "",
        allergies: [] as string[],
        measurements: [] as any[], // Añadido para que la gráfica no rompa al iniciar
    });

    const [isLoading, setIsLoading] = useState(false);

    // Efecto para cargar los datos del paciente si NO estamos creando
    useEffect(() => {
        if (isCreateMode) return;

        const fetchPatient = async () => {
            try {
                const res = await fetchWithAuth(`/api/patients/${id}`);

                if (res.ok) {
                    const { data } = await res.json();
                    // Volcamos los datos de BBDD en el estado del formulario
                    setFormData({
                        name: data.name || "",
                        age: data.age?.toString() || "",
                        gender: data.gender || "",
                        phone: data.phone || "",
                        email: data.email || "",
                        weight: data.weight?.toString() || "",
                        height: data.height?.toString() || "",
                        pathologies: data.pathologies || "",
                        goal: data.goal || "",
                        notes: data.notes || "",
                        allergies: data.allergies || [],
                        measurements: data.measurements || [], // Recuperamos el histórico de la API
                    });
                }
            } catch (error) {
                console.error("Error al cargar el paciente", error);
            }
        };

        fetchPatient();
    }, [id, isCreateMode]);

    const handleFieldChange = (field: string, value: any) => {
        setFormData((prev) => ({ ...prev, [field]: value }));
    };

    // FUNCIÓN PARA EL BORRADO LÓGICO
    const handleDelete = async () => {
        const message = `¿Estás seguro de que deseas eliminar a ${formData.name || "este paciente"}?\n\nEsta acción realizará un borrado lógico (Soft Delete) de su ficha clínica e historial de mediciones vinculadas.`;
        
        if (!window.confirm(message)) {
            return;
        }

        setIsLoading(true);
        try {
            const response = await fetchWithAuth(`/api/patients/${id}`, {
                method: "DELETE",
            });

            if (response.ok) {
                router.push("/dashboard/patients");
            } else {
                console.error("Fallo al eliminar el registro en backend");
            }
        } catch (error) {
            console.error("Error de red al intentar eliminar:", error);
        } finally {
            setIsLoading(false);
        }
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setIsLoading(true);

        try {
            const getCookie = (name: string) => {
                const value = `; ${document.cookie}`;
                const parts = value.split(`; ${name}=`);
                if (parts.length === 2) return parts.pop()?.split(";").shift();
                return "";
            };
            const token = getCookie("auth_token") || "";

            const payload = {
                name: formData.name,
                age: parseInt(formData.age) || 0,
                gender: formData.gender,
                phone: formData.phone,
                email: formData.email,
                weight: parseFloat(formData.weight) || 0,
                height: parseFloat(formData.height) || 0,
                pathologies: formData.pathologies,
                goal: formData.goal,
                notes: formData.notes,
                allergies: formData.allergies,
            };

            // Cambiamos URL y Método según si estamos editando o creando
            const method = isCreateMode ? "POST" : "PUT";
            const endpoint = isCreateMode
                ? `/api/patients`
                : `/api/patients/${id}`;

            const response = await fetchWithAuth(endpoint, {
                method: method,
                body: JSON.stringify(payload),
            });

            if (response.ok) {
                router.push("/dashboard/patients");
            } else {
                console.error(
                    "Error al guardar el paciente (Status:",
                    response.status,
                    ")",
                );
            }
        } catch (error) {
            console.error("Fallo de red al enviar formulario:", error);
        } finally {
            setIsLoading(false);
        }
    };

    return (
        <form
            onSubmit={handleSubmit}
            className="mx-auto flex max-w-7xl flex-col gap-8 px-4 py-8 sm:px-6 lg:px-8"
        >
            <PatientHeader
                isCreateMode={isCreateMode}
                isLoading={isLoading}
                patientName={formData.name}
            />
            
            <div className="grid grid-cols-1 gap-8 lg:grid-cols-3">
                <PersonalDataColumn
                    formData={formData}
                    onChange={handleFieldChange}
                />
                <ClinicalHistoryColumn
                    formData={formData}
                    onChange={handleFieldChange}
                />
            </div>

            {/* GRÁFICA DE EVOLUCIÓN: Condicionada, ocupa todo el ancho y justo antes del footer */}
            {!isCreateMode && (
                <div className="w-full">
                    <BiometricEvolutionChart measurements={formData.measurements} />
                </div>
            )}

            {/* ZONA INFERIOR: BOTÓN ELIMINAR (Solo visible en edición) */}
            {!isCreateMode && (
                <div className="mt-4 flex justify-end border-t border-slate-200 pt-6">
                    <button
                        type="button"
                        onClick={handleDelete}
                        disabled={isLoading}
                        className="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-600/20 disabled:opacity-50"
                    >
                        <Trash2 className="h-4 w-4" aria-hidden="true" />
                        Eliminar Paciente
                    </button>
                </div>
            )}
        </form>
    );
}