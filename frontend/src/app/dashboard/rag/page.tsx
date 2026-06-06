'use client';

import { useState } from 'react';

export default function RagEngine() {
  const [query, setQuery] = useState('');
  const [response, setResponse] = useState('');
  const [isLoading, setIsLoading] = useState(false);

  const handleGenerate = async () => {
    if (!query.trim()) return;
    
    setIsLoading(true);
    setResponse('');

    try {
      const res = await fetch('http://localhost:8000/api/diets/generate', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ query }),
      });

      const data = await res.json();
      
      if (res.ok) {
        setResponse(data.data.dietary_proposal);
      } else {
        setResponse(`Error del servidor: ${data.error || 'Desconocido'}`);
      }
    } catch (error) {
      setResponse('Error de conexión con el motor RAG clínico. Revisa que el contenedor Docker nutri_php esté corriendo.');
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="max-w-6xl mx-auto">
      <header className="mb-8 border-b border-slate-200 pb-4">
        <h2 className="text-3xl font-bold text-slate-800">Orquestador de Inferencia RAG</h2>
        <p className="text-sm text-slate-500 mt-2">Generación de pautas nutricionales basadas en similitud vectorial sobre evidencia indexada.</p>
      </header>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
        {/* Panel Izquierdo: Input de Contexto */}
        <div className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm flex flex-col min-h-[500px]">
          <label className="block text-sm font-semibold text-slate-700 mb-3 uppercase tracking-wide">
            Contexto Clínico del Paciente
          </label>
          <textarea 
            value={query}
            onChange={(e) => setQuery(e.target.value)}
            placeholder="Ej: Paciente varón, 29 años, intolerancia a la lactosa severa. Objetivo: hipertrofia muscular. Solicito opciones de desayuno..."
            className="flex-1 w-full p-4 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none resize-none text-slate-700 transition-shadow"
          />
          <button 
            onClick={handleGenerate}
            disabled={isLoading || !query.trim()}
            className="mt-6 w-full bg-slate-900 hover:bg-slate-800 disabled:bg-slate-300 disabled:cursor-not-allowed text-white font-medium py-3 px-4 rounded-lg transition-colors flex justify-center items-center gap-2"
          >
            {isLoading ? (
              <>
                <svg className="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle><path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                Procesando Vectores...
              </>
            ) : (
              'Ejecutar Inferencia (IA)'
            )}
          </button>
        </div>

        {/* Panel Derecho: Output del Motor */}
        <div className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm flex flex-col min-h-[500px]">
          <h3 className="text-sm font-semibold text-slate-700 mb-3 uppercase tracking-wide border-b border-slate-100 pb-2">
            Respuesta del Motor Clínico
          </h3>
          <div className="flex-1 bg-slate-50 border border-slate-100 p-6 rounded-lg overflow-y-auto font-mono text-sm leading-relaxed whitespace-pre-wrap text-slate-800">
            {response ? (
              response
            ) : (
              <span className="text-slate-400 italic flex items-center justify-center h-full text-center">
                Esperando variables biométricas para iniciar la recuperación de documentos (RAG)...
              </span>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}