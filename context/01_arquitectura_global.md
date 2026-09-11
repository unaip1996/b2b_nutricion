# Arquitectura global

B2B Nutrición está compuesto por una API Symfony, un cliente Next.js y PostgreSQL con pgvector. La aplicación aplica una separación por capas y puertos para las integraciones externas.

## Capas del backend

- `src/Domain`: contratos del negocio y puertos para servicios externos. No depende de Symfony, Doctrine ni OpenAI.
- `src/Application`: casos de uso y coordinación del flujo de negocio.
- `src/Infrastructure`: controladores HTTP, comandos, adaptadores, persistencia Doctrine y entidades mapeadas.

Las entidades están mapeadas con Doctrine, por lo que pertenecen a `Infrastructure/Entity`. Los repositorios Doctrine también están centralizados en `Infrastructure/Repository`.

## Reglas de dependencia

1. `Infrastructure` puede depender de `Application` y `Domain`.
2. `Application` puede depender de contratos de `Domain`.
3. `Domain` no puede depender de las otras capas ni de librerías de infraestructura.
4. Las integraciones de IA, PDF y vectorización se exponen mediante interfaces en `Domain/Service` e implementaciones en `Infrastructure/Adapter`.

## Frontend

El cliente está en `frontend/` y usa Next.js App Router. Las rutas se organizan en `src/app`, los componentes por área funcional en `src/components` y las utilidades en `src/lib`.

## Calidad

El flujo de CI instala dependencias reproduciblemente, valida Composer, ejecuta PHPUnit y comprueba tipos, lint y compilación del frontend. Consulta el README raíz para los comandos de desarrollo.
