# PHPUnit contra la rama de Diego

Ejecución **R1**, 2026-10-28. Comando: `php vendor/bin/phpunit --testdox` en `backend/`, configuración `backend/phpunit.xml`. PHPUnit 11.5.57, PHP 8.2.12. Tiempo: 12,164 s. Memoria: 34 MB.

Salida completa (rutas de máquina sustituidas por `<RAIZ_PROYECTO>`): [`resultados-phpunit-integracion.txt`](resultados-phpunit-integracion.txt).

**No se modificó código para forzar aprobaciones.** Código de producción de Diego: 0 diferencias.

## Totales

| Suite | Pruebas | Resultado |
|---|---:|---|
| Caracterizacion | 90 | OK |
| RequisitosSeguridad | 35 | 9 fallos |
| **Total** | **125** | **116 aprueban, 9 fallan** (426 aserciones) |

Aislamiento: `storage/app/geoeduca-state.json` no aparece en el repositorio. Directorios temporales `geoeduca-pruebas-*`: 0 al terminar.

## Fallos (todos en `tests/Seguridad`, expectativas sin cambiar)

| Prueba | Esperado | Obtenido |
|---|---|---|
| `GET /api/auth/me` sin credenciales | 401 | 200 `{user:null}` |
| `GET /api/auth/me` con token inventado | 401 | 200 `{user:null}` |
| Cabecera `X-Content-Type-Options` | `nosniff` | ausente |
| 404 de API con `APP_DEBUG=true` | sin traza | expone `trace`, `file`, `line`, `exception` |
| Validación sin `Accept` | 422 JSON | 302 |
| DNI como arreglo | 401 o 422 | 500 |
| DNI como objeto | 401 o 422 | 500 |
| 12 logins fallidos | algún 429 | 12×401 |
| `/api/auth/me` tras logout | 401 | 200 `{user:null}` |

Tras logout, las rutas de **asistencia** sí pasan a 401. El hueco es `/me`.

Estos 9 fallos son la lista de trabajo de F1. La evidencia académica de la práctica (código base, 124 pruebas, 27 fallos de seguridad) permanece en `../01-guia-pruebas-calidad/automatizadas/`.
