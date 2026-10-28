# Documentación técnica de desarrollo

`main` incluye F1.1 (`6678249`). F1.2 (autenticación Sanctum) está en `feature/f1-autenticacion`, sin publicar.

El JSON sigue siendo el almacén de **asistencia**. El login, `/me` y el logout usan `users` + Sanctum.

| Documento | Contenido |
|---|---|
| [`DIAGNOSTICO-TECNICO.md`](DIAGNOSTICO-TECNICO.md) | Qué hay, qué es simulado y qué no existe |
| [`ARQUITECTURA.md`](ARQUITECTURA.md) | Componentes actuales y destino de F1 |
| [`MODELO-DE-DATOS.md`](MODELO-DE-DATOS.md) | Esquema relacional (F1.1) |
| [`AUTENTICACION.md`](AUTENTICACION.md) | Login Sanctum, contrato y DNI+rol (F1.2) |
| [`CONFIGURACION-POSTGRES.md`](CONFIGURACION-POSTGRES.md) | Docker, `.env`, `migrate` y `db:seed` |
| [`PLAN-DE-IMPLEMENTACION.md`](PLAN-DE-IMPLEMENTACION.md) | Módulos M0–M10 y Pull Requests de F1 |
| [`RIESGOS.md`](RIESGOS.md) | Riesgos de integración y de producto |
| [`DEPENDENCIAS.md`](DEPENDENCIAS.md) | Orden entre módulos y decisiones |
| [`CRITERIOS-DE-ACEPTACION.md`](CRITERIOS-DE-ACEPTACION.md) | Cómo se da por hecha cada etapa |
| [`PROXIMAS-TAREAS.md`](PROXIMAS-TAREAS.md) | Siguiente etapa: recuperación y datos de asistencia |
| [`RESULTADOS-PHPUNIT-F1.1.md`](RESULTADOS-PHPUNIT-F1.1.md) | PHPUnit de F1.1 |
| [`RESULTADOS-PHPUNIT-F1.2.md`](RESULTADOS-PHPUNIT-F1.2.md) | PHPUnit de F1.2 (148 OK) |

La guía académica (`docs/01-guia-pruebas-calidad/`) no se modifica en F1.2.
