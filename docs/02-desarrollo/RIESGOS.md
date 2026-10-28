# Riesgos

## Integración (esta rama)

| ID | Riesgo | Gravedad | Estado |
|---|---|---|---|
| R1 | Fusionar historiales Git no relacionados | Alta | **Evitado.** No se hizo `merge --allow-unrelated-histories`. Infraestructura copiada sobre el commit de Diego |
| R2 | Las pruebas del código base no valen contra Diego | Alta | **Tratado.** Caracterización adaptada; seguridad intacta. Ver registro de adaptación |
| R3 | Correo institucional en metadatos de commit públicos | Alta | **Pendiente de autorización.** Identidad local noreply configurada; no hay commits nuevos |
| R4 | Publicar material académico con PII | Alta | **Tratado en la copia pública.** Originales fuera. Ver auditoría de privacidad |
| R5 | Destino del PR: `main` no tiene el trabajo de Diego | Media | **Resuelto.** Base = `mejoras-interfaces-dinamicas` |
| R6 | Permisos de escritura en el repositorio principal | Media | **Pendiente.** Publicar desde fork o con acceso que conceda Diego |
| R7 | Trabajo concurrente en `AsistenciaStore.php` | Media | Coordinar módulos; PRs pequeños |
| R8 | `composer.lock` | Baja | Producción 73/73 idéntica; solo se añadieron paquetes de desarrollo |
| R9 | Dependencias vulnerables | Alta al conectar auth real | M0.2: actualizar Laravel y evaluar Vite en PRs aparte |
| R10 | JSON de estado con sesiones y contraseñas | Alta si se despliega | El archivo está ignorado; se elimina en M1/M2 |
| R11 | README de `main` habla de MySQL y `migrate` | Baja | Corregir al implementar M1 |
| R12 | Saltos de línea en scripts | Baja | `.gitattributes` añadido |

## Producto

| Riesgo | Mitigación |
|---|---|
| Reescribir horarios y puntualidad de Diego sin perder comportamiento | Capturar el comportamiento actual con pruebas **antes** de migrar a la base de datos |
| Datos de menores | Mínimos necesarios, cifrado en tránsito, acceso por rol y propiedad, sin datos reales en desarrollo |
| Dependencias nuevas (QR, PDF) y actualización de Laravel/Vite | `composer audit` y `npm audit` en cada PR |
| Componentes React de más de 400 líneas | Dividirlos al tocarlos, no en un cambio masivo |
| Token en `localStorage` | Decisión D7: cookie HttpOnly de Sanctum cuando frontend y backend compartan dominio |

## Operación de esta entrega

No se hizo push, PR, merge a `main` ni force push. El clon de integración tiene el push deshabilitado. El repositorio original de la práctica no se alteró. Si un paso arriesgara PII o pérdida de trabajo, se detuvo ese paso y se conservaron los originales.
