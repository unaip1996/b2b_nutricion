# Reglas de Capa API (Controladores Symfony)

1. **Ubicación:** Los controladores van en `src/Infrastructure/Controller/` o `src/Infrastructure/Api/`.
2. **Responsabilidad:** Los controladores son "tontos". Su ÚNICO trabajo es:
   - Recibir la petición HTTP (Request).
   - Validar el JSON de entrada (DTOs).
   - Llamar a un Caso de Uso (Application Layer).
   - Devolver una respuesta JSON (JsonResponse).
3. **Seguridad:** Los endpoints privados deben usar el atributo `#[IsGranted('ROLE_USER')]` (o el rol pertinente) para asegurar el endpoint mediante JWT.
4. **Respuestas:** - HTTP 200/201 para éxito.
   - HTTP 400 para errores de validación.
   - HTTP 404 si el Caso de Uso lanza un "NotFoundException".
   - Todo se devuelve estructurado en JSON.