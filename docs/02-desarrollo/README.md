# Documentación técnica de desarrollo

`main` incluye hasta el desarrollo integral (`354190f`). La rama local `feature/geoeduca-version-final` completa los flujos de administración, QR, salida, justificaciones y reportes. No está publicada.

PostgreSQL es el almacén de asistencia. El JSON original permanece como respaldo.

| Documento | Contenido |
|---|---|
| [`DIAGNOSTICO-TECNICO.md`](DIAGNOSTICO-TECNICO.md) | Qué hay, qué es simulado y qué no existe |
| [`ARQUITECTURA.md`](ARQUITECTURA.md) | Componentes actuales y destino de F1 |
| [`MODELO-DE-DATOS.md`](MODELO-DE-DATOS.md) | Esquema relacional (F1.1) |
| [`AUTENTICACION.md`](AUTENTICACION.md) | Login Sanctum, carnet y DNI+rol |
| [`SESIONES.md`](SESIONES.md) | Cómo React confirma la sesión con `/me` (F1.4) |
| [`RECUPERACION.md`](RECUPERACION.md) | Códigos hasheados y ausencia de canal de envío (F1.3) |
| [`PERMISOS.md`](PERMISOS.md) | Matriz rol × ruta (F1.3) |
| [`CONFIGURACION-POSTGRES.md`](CONFIGURACION-POSTGRES.md) | Docker, `.env`, `migrate` y `db:seed` |
| [`PLAN-DE-IMPLEMENTACION.md`](PLAN-DE-IMPLEMENTACION.md) | Módulos M0–M10 y Pull Requests de F1 |
| [`RIESGOS.md`](RIESGOS.md) | Riesgos de integración y de producto |
| [`DEPENDENCIAS.md`](DEPENDENCIAS.md) | Orden entre módulos y decisiones |
| [`CRITERIOS-DE-ACEPTACION.md`](CRITERIOS-DE-ACEPTACION.md) | Cómo se da por hecha cada etapa |
| [`MATRIZ-CUMPLIMIENTO-FUNCIONAL.md`](MATRIZ-CUMPLIMIENTO-FUNCIONAL.md) | Requisitos de esta entrega, estado y evidencia |
| [`ESTADO-VERSION-FINAL.md`](ESTADO-VERSION-FINAL.md) | Cómo ejecutar la versión local y qué queda pendiente |
| [`PROXIMAS-TAREAS.md`](PROXIMAS-TAREAS.md) | Siguiente etapa histórica: asistencia fuera del JSON |
| [`RESULTADOS-PHPUNIT-F1.1.md`](RESULTADOS-PHPUNIT-F1.1.md) | PHPUnit de F1.1 |
| [`RESULTADOS-PHPUNIT-F1.2.md`](RESULTADOS-PHPUNIT-F1.2.md) | PHPUnit de F1.2 (148 OK) |
| [`RESULTADOS-PHPUNIT-F1.3.md`](RESULTADOS-PHPUNIT-F1.3.md) | PHPUnit de F1.3 (173 OK) |
| [`RESULTADOS-PRUEBAS-F1.4.md`](RESULTADOS-PRUEBAS-F1.4.md) | PHPUnit, Vitest y navegador de F1.4 |

La guía académica (`docs/01-guia-pruebas-calidad/`) no se modifica en F1.4.
