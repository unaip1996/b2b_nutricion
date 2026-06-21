# Reglas del Frontend (Next.js B2B Nutrición)

1. **Arquitectura:** Usa el App Router de Next.js (`app/`).
2. **Estilizado:** Tailwind CSS estricto. Diseño profesional, clínico y minimalista (tonos neutros, azules corporativos).
3. **Componentes:** - Crea componentes pequeños, reutilizables y tipados con TypeScript.
   - Usa Client Components (`"use client"`) solo donde haya interactividad o hooks (useState, useEffect). Prioriza Server Components.
4. **Consumo de API:** - Las llamadas al backend de Symfony deben usar `fetch` o `axios`, siempre enviando el token JWT en la cabecera `Authorization: Bearer <token>`.
5. **Manejo de Estado:** Evita Redux a menos que sea crítico; usa Zustand o React Query para el estado de los datos del servidor (pacientes, dietas).