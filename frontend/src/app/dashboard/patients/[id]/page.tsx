"use client";

import { useState, useEffect } from "react";
import { useParams, useRouter } from "next/navigation";
import { PatientHeader } from "@/components/clinical/patient-header";
import { PersonalDataColumn } from "@/components/clinical/personal-data-column";
import { ClinicalHistoryColumn } from "@/components/clinical/clinical-history-column";

export default function PatientPage() {
    const params = useParams();
    const router = useRouter();
    const id = params?.id as string;

    const isCreateMode = id === "create" || id === "nuevo";

    // Estado global para el formulario, "Lifting State Up"
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
    });

    const [isLoading, setIsLoading] = useState(false);

    // Cargar datos si no es modo creación
    useEffect(() => {
        if (isCreateMode) return;

        const fetchPatientData = async () => {
            setIsLoading(true);
            try {
                const getCookie = (name: string) => {
                    const value = `; ${document.cookie}`;
                    const parts = value.split(`; ${name}=`);
                    if (parts.length === 2) return parts.pop()?.split(";").shift();
                    return "";
                };
                const token = getCookie("auth_token");

                const response = await fetch(`http://localhost:8000/api/patients/${id}`, {
                    method: "GET",
                    headers: {
                        Authorization: `Bearer ${token}`,
                    },
                });

                if (response.ok) {
                    const json = await response.json();
                    // Rellenamos el estado con los datos que vienen del backend
                    setFormData(json.data);
                } else {
                    console.error("Error al obtener el paciente");
                }
            } catch (error) {
                console.error("Error de conexión:", error);
            } finally {
                setIsLoading(false);
            }
        };

        fetchPatientData();
    }, [id, isCreateMode]);

    // Función delegada a los hijos para reaccionar al cambio
    const handleFieldChange = (field: string, value: any) => {
        setFormData((prev) => ({ ...prev, [field]: value }));
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

            const pesoNum = parseFloat(formData.weight) || 0;
            const alturaNum = parseFloat(formData.height) || 0;
            const ageNum = parseInt(formData.age) || 0;

            // Calcular % de grasa corporal de forma dinámica (Fórmula de Deurenberg)
            let bodyFatCalc = 0;
            if (alturaNum > 0 && pesoNum > 0 && ageNum > 0 && formData.gender) {
                const imcNum = pesoNum / Math.pow(alturaNum / 100, 2);
                const isMale = formData.gender.toLowerCase() === "hombre";
                bodyFatCalc = (1.20 * imcNum) + (0.23 * ageNum) - (10.8 * (isMale ? 1 : 0)) - 5.4;
                
                // Prevenir valores negativos por incoherencias matemáticas
                bodyFatCalc = Math.max(0, bodyFatCalc);
            }

            const payload = {
                name: formData.name,
                age: ageNum,
                gender: formData.gender,
                phone: formData.phone,
                email: formData.email,
                weight: pesoNum,
                height: alturaNum,
                bodyFatPercentage: parseFloat(bodyFatCalc.toFixed(2)),
                pathologies: formData.pathologies,
                goal: formData.goal,
                notes: formData.notes,
                allergies: formData.allergies,
            };

            const response = await fetch("http://localhost:8000/api/patients", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Authorization: `Bearer ${token}`,
                },
                body: JSON.stringify(payload),
            });

            if (response.ok) {
                router.push("/dashboard/patients");
            } else {
                // Extraemos el cuerpo del error enviado por Symfony
                const errorData = await response.json().catch(() => null);
                console.error(
                    "Error al guardar el paciente (Status:",
                    response.status,
                    ")",
                    errorData
                );
                alert(`Error al guardar el paciente:\n${errorData?.error || response.statusText}`);
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
        </form>
    );
}
