"use client";

import { Search, Upload, Loader2 } from "lucide-react";
import { useState, useRef } from "react";
import { useRouter } from "next/navigation";

export function KnowledgeBaseToolbar() {
    const router = useRouter();
    const [isUploading, setIsUploading] = useState(false);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const handleFileUpload = async (event: React.ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0];
        if (!file || file.type !== "application/pdf") {
            alert("Por favor, selecciona un archivo PDF válido.");
            return;
        }

        setIsUploading(true);

        try {
            const token = document.cookie.split("; ").find(row => row.startsWith("auth_token="))?.split("=")[1];
            const formData = new FormData();
            formData.append("file", file);

            const response = await fetch("http://localhost:8000/api/ingest", {
                method: "POST",
                headers: {
                    Authorization: `Bearer ${token}`,
                },
                body: formData,
            });

            if (response.ok) {
                // Forzamos la recarga de los datos de la página para ver el nuevo documento en la tabla
                window.dispatchEvent(new Event("documentUploaded"));
            } else {
                const data = await response.json();
                alert(data.error || "Error al subir el documento.");
            }
        } catch (error) {
            alert("Error de red al subir el documento.");
        } finally {
            setIsUploading(false);
            if (fileInputRef.current) fileInputRef.current.value = ''; // Limpiamos el input
        }
    };

    return (
        <div className="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div className="relative w-full sm:max-w-sm">
                <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                <input
                    type="search"
                    placeholder="Buscar guías o protocolos..."
                    aria-label="Buscar documentos"
                    className="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-4 text-sm text-slate-900 shadow-sm outline-none placeholder:text-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20"
                />
            </div>
            
            {/* Input oculto para la selección del archivo */}
            <input 
                type="file" 
                accept="application/pdf" 
                ref={fileInputRef} 
                className="hidden" 
                onChange={handleFileUpload}
            />

            <button
                type="button"
                onClick={() => fileInputRef.current?.click()}
                disabled={isUploading}
                className="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition-colors hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/30 disabled:opacity-50"
            >
                {isUploading ? <Loader2 className="size-4 animate-spin" /> : <Upload className="size-4" />}
                {isUploading ? "Ingiriendo..." : "Subir Documento (PDF)"}
            </button>
        </div>
    );
}