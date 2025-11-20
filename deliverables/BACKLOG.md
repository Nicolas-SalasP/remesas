# Backlog de Desarrollo

| ID | Tarea | Prioridad | Estimación (h) | Descripción |
|----|-------|-----------|----------------|-------------|
| **BUG-01** | Fix Error Suppression | Alta | 4 | Eliminar `@` en conexión DB y manejo de IMAP. Implementar Try/Catch y logging real. |
| **SEC-01** | Remove Hardcoded Creds | Alta | 2 | Mover número de WhatsApp y emails a `config.php` o `.env`. |
| **TEST-01** | Unit Tests: Pricing | Alta | 8 | Crear tests para `PricingService` asegurando que la selección de tasas por rango funciona. |
| **TEST-02** | Unit Tests: Transaction | Alta | 8 | Tests para `createTransaction`: validación de saldo, inputs y generación de hash. |
| **INFRA-01** | Dockerize | Media | 6 | Crear `Dockerfile` y `docker-compose` para desarrollo y producción. |
| **FEAT-01** | API Validation Middleware | Media | 10 | Crear validador genérico de JSON en el backend para evitar `isset()` repetitivos. |
| **UX-01** | Feedback Carga Imágenes | Baja | 3 | Mejorar feedback visual (barra de progreso) al subir comprobantes grandes. |
| **DOC-01** | Swagger Docs | Baja | 5 | Generar documentación OpenAPI automática. |