"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { BrainCircuit, FileText } from "lucide-react"; // Añade esta línea

export interface Patient {
  id: string;
  name?: string;
  age?: number;
  bmi?: number | string;
  condition?: string;
  isAllergy?: boolean;
  goal?: string;
  medicalHistoryNumber?: string; // Campo real que puede venir de tu API
}

function ConditionBadge({ condition, isAllergy }: { condition: string; isAllergy: boolean }) {
  if (!condition) return <span className="text-slate-400">-</span>;
  const classes = isAllergy ? "bg-red-100 text-red-800" : "bg-slate-100 text-slate-700"
  return <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${classes}`}>{condition}</span>
}

function PatientRow({ patient }: { patient: Patient }) {
  return (
    <tr className="border-t border-slate-200 transition-colors hover:bg-slate-50">
      <td className="p-4 font-mono text-sm text-slate-500">{patient.medicalHistoryNumber || patient.id.split('-')[0]}</td>
      <td className="p-4">
        <span className="font-medium text-slate-800">{patient.name || "Paciente Anónimo"}</span>
      </td>
      <td className="p-4 text-sm text-slate-600">
        {patient.age || "--"} años / IMC: {patient.bmi || "--"}
      </td>
      <td className="p-4">
        <ConditionBadge condition={patient.condition || ""} isAllergy={patient.isAllergy || false} />
      </td>
      <td className="p-4 text-sm text-slate-600">{patient.goal || "--"}</td>
      <td className="p-4">
        <div className="flex items-center justify-end gap-3">
            <Link 
                href={`/dashboard/patients/${patient.id}`} 
                className="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition-colors hover:text-blue-600"
            >
                <FileText className="h-4 w-4" />
                Ver Ficha
            </Link>
            {/* CORRECCIÓN: Ahora apunta al CRUD de dietas del paciente */}
            <Link 
                href={`/dashboard/patients/${patient.id}/diets`} 
                className="inline-flex items-center gap-1.5 rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 transition-colors hover:bg-blue-100 border border-blue-200"
            >
                <BrainCircuit className="h-3.5 w-3.5" />
                Dietas
            </Link>
        </div>
      </td>
    </tr>
  )
}

export function PatientsTable() {
  const [patients, setPatients] = useState<Patient[]>([]);
  const [isLoading, setIsLoading] = useState<boolean>(true);
  const [error, setError] = useState<string>("");

  useEffect(() => {
    const fetchPatients = async () => {
      try {
        // 1. Extractor robusto de cookies (evita fallos de RegExp)
        const getCookie = (name: string) => {
          const value = `; ${document.cookie}`;
          const parts = value.split(`; ${name}=`);
          if (parts.length === 2) return parts.pop()?.split(';').shift();
          return null;
        };

        const token = getCookie('auth_token');
        
        // CHIVATO 1: Comprobamos si el frontend realmente tiene el token
        console.log("Token extraído en frontend:", token ? "SÍ (Mide " + token.length + " chars)" : "NO / NULO");

        if (!token) {
          throw new Error("No se ha encontrado el token de sesión en el navegador.");
        }

        // 2. Ejecutar la petición
        const response = await fetch("http://localhost:8000/api/patients", {
          method: "GET",
          headers: {
            "Authorization": `Bearer ${token}`,
            "Content-Type": "application/json"
          }
        });

        // CHIVATO 2: Ver qué responde el backend antes de que salte el error
        console.log("Status de la respuesta Symfony:", response.status);

        if (!response.ok) {
          throw new Error(response.status === 401 ? "No autorizado (401): Sesión expirada o token inválido." : `Error del servidor: ${response.status}`);
        }

        const json = await response.json();
        setPatients(json.data || []);
        
      } catch (err: unknown) {
        console.error("Error capturado en el fetch:", err);
        setError(err instanceof Error ? err.message : "Fallo desconocido de conexión.");
      } finally {
        setIsLoading(false);
      }
    };

    fetchPatients();
  }, []);

  return (
    <div className="space-y-4">
      {error && (
        <div className="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-600 shadow-sm">
          {error}
        </div>
      )}
      <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
      <div className="overflow-x-auto">
        <table className="w-full text-left">
          <thead>
            <tr className="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
              <th className="p-4">ID Médico</th>
              <th className="p-4">Paciente</th>
              <th className="p-4">Biometría (Edad/IMC)</th>
              <th className="p-4">Condición Principal</th>
              <th className="p-4">Objetivo</th>
              <th className="p-4 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody>
              {isLoading ? (
                <tr>
                  <td colSpan={6} className="p-8 text-center text-sm font-medium text-slate-500">
                    Cargando directorio de pacientes...
                  </td>
                </tr>
              ) : patients.length === 0 && !error ? (
                <tr>
                  <td colSpan={6} className="p-8 text-center text-sm font-medium text-slate-500">
                    No hay pacientes registrados actualmente.
                  </td>
                </tr>
              ) : (
                patients.map((patient) => (
                  <PatientRow key={patient.id} patient={patient} />
                ))
              )}
          </tbody>
        </table>
      </div>
    </div>
    </div>
  )
}
