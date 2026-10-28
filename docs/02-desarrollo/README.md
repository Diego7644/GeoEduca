# Documentación técnica de desarrollo

Estado del prototipo en `main` (`4992279`) y avance de F1.1 en `feature/f1-persistencia`.

El JSON del prototipo **sigue siendo** el almacén de la API. F1.1 añade el esquema PostgreSQL, modelos Eloquent, seeders de desarrollo y pruebas de persistencia. No sustituye el login simulado.

| Documento | Contenido |
|---|---|
| [`DIAGNOSTICO-TECNICO.md`](DIAGNOSTICO-TECNICO.md) | Qué hay, qué es simulado y qué no existe (foto de M0, actualizada en F1.1) |
| [`ARQUITECTURA.md`](ARQUITECTURA.md) | Componentes actuales y destino de F1 |
| [`MODELO-DE-DATOS.md`](MODELO-DE-DATOS.md) | Esquema relacional, claves y decisiones de F1.1 |
| [`CONFIGURACION-POSTGRES.md`](CONFIGURACION-POSTGRES.md) | Docker, `.env`, `migrate` y `db:seed` |
| [`PLAN-DE-IMPLEMENTACION.md`](PLAN-DE-IMPLEMENTACION.md) | Módulos M0–M10 y Pull Requests de F1 |
| [`RIESGOS.md`](RIESGOS.md) | Riesgos de integración y de producto |
| [`DEPENDENCIAS.md`](DEPENDENCIAS.md) | Orden entre módulos y decisiones |
| [`CRITERIOS-DE-ACEPTACION.md`](CRITERIOS-DE-ACEPTACION.md) | Cómo se da por hecha cada etapa |
| [`PROXIMAS-TAREAS.md`](PROXIMAS-TAREAS.md) | Siguiente etapa: F1.2 autenticación real |
| [`RESULTADOS-PHPUNIT-INTEGRACION.md`](RESULTADOS-PHPUNIT-INTEGRACION.md) | PHPUnit de M0 (125 pruebas) |
| [`RESULTADOS-PHPUNIT-F1.1.md`](RESULTADOS-PHPUNIT-F1.1.md) | PHPUnit de F1.1 (137 pruebas, 9 fallos conocidos) |
| [`GRUPOS-DE-COMMIT.md`](GRUPOS-DE-COMMIT.md) | Cómo se publicó M0 |

Relacionado: [`../01-guia-pruebas-calidad/REGISTRO-DE-ADAPTACION-DE-PRUEBAS.md`](../01-guia-pruebas-calidad/REGISTRO-DE-ADAPTACION-DE-PRUEBAS.md). La guía académica no se modifica en F1.1.
