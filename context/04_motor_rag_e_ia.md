# Reglas del Motor Vectorial y OpenAI (RAG)

1. **Aislamiento:** La interacción con OpenAI o cualquier LLM debe estar encapsulada en un Adaptador en la capa de Infraestructura (`src/Infrastructure/Adapter/OpenAiRagAdapter.php`), el cual implementa una interfaz del Dominio (`RagEngineInterface`).
2. **Procesamiento de PDFs (Ingesta):** - El texto extraído debe dividirse en `DocumentChunks` coherentes.
   - Cada chunk debe convertirse en un vector numérico (Embedding) llamando a la API de embeddings (ej. `text-embedding-3-small`).
3. **Base de Datos Vectorial:** Los vectores se guardan en la tabla de chunks usando la extensión `pgvector` de PostgreSQL.
4. **Generación de Prompts:** Los prompts para generar dietas deben ser deterministas, inyectando como contexto explícito el perfil biométrico del paciente y los chunks médicos recuperados por similitud (Coseno).