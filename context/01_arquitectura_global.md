# Reglas Globales: Arquitectura Hexagonal y DDD

El proyecto "B2B Nutrición" sigue estrictamente la Arquitectura Hexagonal (Puertos y Adaptadores) y Diseño Guiado por el Dominio (DDD).

## 1. Capas del Proyecto
- **Domain (`src/Domain/`):** El corazón. Entidades puras, Value Objects, y Puertos (Interfaces). PROHIBIDO usar librerías externas o frameworks aquí (ni Doctrine, ni Symfony, ni OpenAI).
- **Application (`src/Application/`):** Casos de Uso (Use Cases). Orquestan el flujo. Llaman a los repositorios (puertos) y al dominio, pero no saben de HTTP ni de bases de datos.
- **Infrastructure (`src/Infrastructure/`):** Adaptadores. Aquí vive Doctrine (Entidades de DB, Repositorios), Controladores API, conexión a OpenAI, y pgvector.

## 2. Estándares PHP 8.4
- `declare(strict_types=1);` es OBLIGATORIO en el 100% de los archivos.
- Tipado estricto en parámetros y valores de retorno SIEMPRE (`?string`, `void`, `array`, etc.).
- Usa "Constructor Property Promotion" siempre que sea posible para ahorrar código.
- NUNCA uses `mixed` a menos que sea estrictamente inevitable.