# B2B Nutrición

Plataforma para la gestión clínica nutricional, con una API Symfony, un cliente Next.js y generación de dietas asistida por recuperación de contexto (RAG).

## Estructura

```text
src/Application/       Casos de uso
src/Domain/            Puertos del negocio y contratos de servicios
src/Infrastructure/    HTTP, Doctrine, adaptadores externos y comandos
frontend/              Cliente Next.js
tests/                 Pruebas automatizadas del backend
context/               Documentación de arquitectura
diagrams/              Diagramas Mermaid
```

Las entidades y repositorios Doctrine viven actualmente en `Infrastructure`: el proyecto usa una arquitectura por capas con puertos para los servicios externos. No se debe introducir una dependencia nueva de Symfony, Doctrine u OpenAI en `Domain`.

## Inicio rápido

1. Copia las variables necesarias en `.env.local`; no subas secretos al repositorio.
2. Define `POSTGRES_PASSWORD` y, si procede, `NEXT_PUBLIC_API_URL`.
3. Ejecuta `docker compose up --build`.
4. La API queda disponible en `http://localhost:8000` y el frontend en `http://localhost:3000`.

## Desarrollo y validación

```bash
composer install
php bin/phpunit

cd frontend
npm ci
npm run check
npm run lint
npm run build
```

Las migraciones se almacenan en `migrations/`. Los dumps de base de datos y los informes de cobertura son artefactos locales y no se versionan.

## Documentación

Consulta `context/01_arquitectura_global.md` para las decisiones de arquitectura y `diagrams/` para los diagramas del sistema.
