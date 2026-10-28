# Registro consolidado de incidencias – GEOEDUCA

Reúne los defectos encontrados por las **cuatro fuentes** de la práctica: pruebas manuales y de API, pruebas de interfaz, pruebas automatizadas (PHPUnit) y análisis estático
(SonarQube), más la auditoría de dependencias. Corresponde al apartado «documentar los defectos con comportamiento observado y consecuencias» de la **Actividad 2.3** de la guía.

**Ninguna incidencia está corregida**: la práctica prohíbe modificar el código de producción. Todas se encuentran en estado *Abierta*.
Las rutas de evidencia son relativas a `docs/01-guia-pruebas-calidad/`.

**Escala.** *Crítica*: permite acceso o acciones no autorizados sobre datos de menores. *Alta*: debilita una defensa o impide una funcionalidad central. *Media*: error
visible o riesgo que depende de una condición. *Baja*: mejora de robustez, higiene o usabilidad.

## 1. Resumen

| Prioridad | Cantidad | Incidencias |
|---|---:|---|
| Crítica | 2 | INC-01, INC-02 |
| Alta | 5 | INC-03, INC-04, INC-05, INC-06, INC-16 |
| Media | 5 | INC-07, INC-08, INC-11, INC-12, INC-15 |
| Baja | 4 | INC-09, INC-10, INC-13, INC-14 |
| **Total** | **16** | |

| Fuente | Incidencias |
|---|---|
| Pruebas manuales y de API | INC-01, 02, 03, 04, 06, 07, 09, 10 |
| Pruebas de interfaz | INC-02 (UI-06), 05 (UI-08), 08 (UI-07), 13 (UI-11), 14 (UI-12) |
| PHPUnit | INC-01, 02, 03, 04, 07, 09, 10 (reproducidas) e INC-11 (hallazgo nuevo) |
| SonarQube | INC-12, INC-15 |
| Auditoría de dependencias | INC-16 |

## 2. Incidencias

| ID | Descripción | Comportamiento observado | Evidencia | Impacto | Prioridad |
|---|---|---|---|---|---|
| INC-01 | Las rutas `/api/auth/me` y `/api/asistencia/*` no exigen autenticación | Con **y** sin token (incluso con un token inventado) responden `200`. Se esperaba `401`. 12 pruebas de `AutenticacionRequeridaTest` fallan por este motivo | `api/TC-S-01.json` a `api/TC-S-04.json`; `automatizadas/salida-seguridad.txt` | Cualquier cliente puede consultar y ejecutar operaciones de asistencia (justificaciones, aprobaciones de ingreso) sin identificarse; los datos son de menores | **Crítica** |
| INC-02 | No hay autorización por rol en el servidor | Un token de estudiante o padre accede a rutas de personal (`200` en vez de `403`; 6 pruebas fallan). En el navegador, cambiar `geoeduca_user.role` de `student` a `staff` en `localStorage` abre el «Panel Auxiliar» con 4 casos de alumnos y el botón de aprobación | `api/TC-S-05.json`; `ui/UI-06_rol-manipulado-abre-panel-auxiliar.png`; `ui/mediciones-dom.json` | Escalada de privilegios: un estudiante puede ver datos de otros alumnos y aprobar ingresos | **Crítica** |
| INC-03 | El token no se invalida al cerrar sesión | Tras el logout el mismo token sigue aceptado (`200`); con un token válido el servidor devuelve `user = null`. 3 pruebas fallan | `api/TC-S-06.json`; `automatizadas/salida-seguridad.txt` | Un token robado o copiado sigue siendo útil después de cerrar sesión; el cierre de sesión solo es visual | **Alta** |
| INC-04 | Sin límite de intentos de inicio de sesión | 12 intentos fallidos seguidos devuelven `401` cada vez; nunca `429`. 1 prueba falla | `api/TC-S-08.json`; `automatizadas/salida-seguridad.txt` | Permite ataques de fuerza bruta o de relleno de credenciales contra los DNI | **Alta** |
| INC-05 | El QR no es dinámico | El gráfico QR tiene la misma huella antes y después de pulsar «Generar Nuevo Código QR» (`744b6508`, 40 elementos); solo se reinicia el contador (34 s → 58 s). No existen endpoints de QR | `ui/mediciones-dom.json`; `api/TC-B-01.json` | La funcionalidad central del producto (control de ingreso con QR renovable) es una simulación; un QR estático puede copiarse y reutilizarse | **Alta** |
| INC-06 | Geolocalización, notificaciones y persistencia de justificaciones no están implementadas | No hay rutas ni código para esas funciones; las pantallas muestran datos fijos y banners simulados («Geo-Verificación Activa»). Los contratos de F-05 y F-06 responden pero no guardan nada | `api/TC-B-02.json`, `api/TC-B-03.json`, `api/TC-B-04.json`; `api/route-list.json` | Alcance: el prototipo es una interfaz, no un sistema funcional. Un usuario podría creer que un aviso o una justificación se registró | **Alta** (alcance) |
| INC-07 | Con `APP_DEBUG=true` un error 404 devuelve detalles internos | La respuesta incluye `trace`, `file`, `line` y `exception` (rutas de archivo y nombres de clases). 1 prueba falla | `api/TC-S-11.json`; `automatizadas/salida-seguridad.txt` | Revela la estructura interna a un atacante si se despliega con depuración activa | **Media** (Alta si se despliega con debug) |
| INC-08 | La interfaz muestra una fecha y hora fijas marcadas como «Sincronizado» | Los paneles de estudiante, padre y auxiliar muestran «Lunes, 24 Octubre 2025 · 07:42 AM (Sincronizado)» cuando la fecha real es el 27 de octubre de 2026 | `ui/UI-01_login-estudiante-panel-y-fecha-fija.png`, `ui/UI-09_login-padre-redirige-a-panel-padre.png`, `ui/UI-10_login-docente-redirige-a-panel-auxiliar.png`; `ui/mediciones-dom.json` | Información falsa: el padre o el auxiliar ven una hora de ingreso que no corresponde con la realidad | **Media** |
| INC-09 | Cabecera `X-Powered-By: PHP/8.2.12` y ausencia de cabeceras de seguridad básicas | La respuesta expone la versión de PHP y no incluye `X-Content-Type-Options: nosniff`. 1 prueba falla (S-10); S-09 solo es manual | `api/TC-S-09.json`, `api/TC-S-10.json` | Facilita el reconocimiento del servidor y debilita las defensas del navegador | **Baja** |
| INC-10 | Sin `Accept: application/json`, un error de validación responde `302` | Se esperaba `422` con JSON; se recibe una redirección. 1 prueba falla | `api/TC-I-05.json`; `automatizadas/salida-seguridad.txt` | Clientes que no declaran `Accept` reciben una respuesta inutilizable en lugar del error de validación | **Baja** |
| INC-11 | Un DNI enviado como arreglo u objeto JSON provoca HTTP 500 (**hallazgo nuevo de la automatización**) | `LoginRequest::prepareForValidation()` (`LoginRequest.php`, línea 22) convierte la entrada con `(string)`; con un arreglo se produce `Array to string conversion` y la API responde `500` en vez de `422`. 2 pruebas fallan | `automatizadas/salida-seguridad.txt`; `automatizadas/resultados-por-prueba.csv` (`EntradasHostilesTest`) | Cualquier cliente puede provocar un error del servidor con una sola petición; no filtra datos | **Media** |
| INC-12 | SonarQube: posible contraseña fija en el frontend (`javascript:S2068`, 2 incidencias) | `frontend/src/services/authService.js`, líneas 44 y 46: las cuentas de respaldo sin conexión (`VITE_USE_MOCK_AUTH`) incluyen contraseñas de demostración en el código. Sonar no señaló la línea 45 (padre) ni `backend/app/Auth/DemoAccounts.php` | `sonarqube/incidencias.json`; `sonarqube/capturas/SQ-08_incidencias.png`, `sonarqube/capturas/SQ-06_seguridad-por-archivo.png` | Esas cadenas viajan en el paquete del navegador y las ve cualquiera. Son de demostración, pero si se reutilizaran como reales quedarían expuestas | **Media** |
| INC-13 | La vista móvil de 375 px recorta contenido | El menú lateral se oculta bien y la página no se desborda, pero el selector de motivo llega a 414 px y la etiqueta «Tardanza Médica Pre-Justificada» a 382 px (ambos recortados), y los nombres de los alumnos se cortan por la izquierda. Solo se midió el panel auxiliar | `ui/UI-11_vista-movil-375px-panel-auxiliar.png`, `ui/UI-11_contenido-recortado-en-375px.png`; `ui/mediciones-dom-ui09-ui12.json` | El auxiliar que use el panel desde el móvil no ve completo el motivo de registro ni el nombre de algunos alumnos | **Baja** |
| INC-14 | Con el backend caído el login muestra un mensaje técnico | Se muestra «Request failed with status code 500». El proxy de Vite responde `500` por `ECONNREFUSED 127.0.0.1:8000` y `Login.jsx` muestra `err.message` sin traducirlo. No se probó un despliegue sin proxy | `ui/UI-12_backend-caido-mensaje-tecnico-500.png`; `ui/mediciones-dom-ui09-ui12.json` | El usuario no sabe si se equivocó o si el servicio está caído, lo que genera consultas innecesarias de soporte | **Baja** |
| INC-15 | SonarQube: 9 problemas de accesibilidad en el frontend | `javascript:S6853` ×6 (etiquetas de formulario sin control asociado o sin texto accesible: `DashboardPadre.jsx` 483, 691, 722, 750; `DashboardAuxiliar.jsx` 589, 602) y `javascript:S6848` ×3 (elementos interactivos no nativos: `Modal.jsx` 15 y 19, `Sidebar.jsx` 22). Sonar los clasifica como *code smell* con impacto de fiabilidad **medio**; calificación MQR de fiabilidad: **C** | `sonarqube/incidencias.json`; `sonarqube/metricas-mqr.json`; `sonarqube/capturas/SQ-05_fiabilidad-por-archivo.png` | Los lectores de pantalla y la navegación por teclado no pueden usar bien esos formularios y componentes; afecta a usuarios con discapacidad | **Media** |
| INC-16 | Dependencias con vulnerabilidades conocidas | `composer audit`: 1 aviso **crítico** en `laravel/framework` 11.57.0 (`PKSA-b41t-wbzk-9q51`, evasión de autenticación en `TokenGuard` con credenciales no textuales) y 8 paquetes abandonados. `npm audit`: 7 vulnerabilidades (4 moderadas y 3 altas, 0 críticas) en `vite`, `react-router-dom`, `react-router`, `esbuild`, `follow-redirects`, `source-map-js` y `postcss-selector-parser`. No se evaluó si GEOEDUCA ejercita la ruta afectada de `TokenGuard`, porque la autenticación actual es simulada | `dependencias/composer-audit.json`, `dependencias/npm-audit.json` (ejecutados el 2026-10-28 a las 00:09, hora local) | Riesgo latente que se vuelve real al conectar autenticación y al desplegar; SonarQube no analiza dependencias en esta edición | **Alta** |

## 3. Relación con las pruebas

| Incidencia | Casos manuales | Pruebas PHPUnit (fallos) |
|---|---|---|
| INC-01 | S-01, S-02, S-03, S-04 | 12 |
| INC-02 | S-05, UI-06 | 6 |
| INC-03 | S-06 | 3 |
| INC-04 | S-08 | 1 |
| INC-07 | S-11 | 1 |
| INC-09 | S-09, S-10 | 1 (S-10) |
| INC-10 | I-05 | 1 |
| INC-11 | — (no cubierto manualmente) | 2 |
| **Total de fallos PHPUnit** | | **27** |

Los 16 casos manuales fallidos son los 11 de API (I-05, S-01 a S-06, S-08, S-09, S-10, S-11) y los 5 de interfaz (UI-06, UI-07, UI-08, UI-11, UI-12).
INC-05, INC-06, INC-12, INC-15 y INC-16 no tienen prueba PHPUnit asociada: se detectaron por pruebas de interfaz, por casos bloqueados, por el análisis estático o por la auditoría.

## 4. Mejoras propuestas

Las acciones sugeridas para cada incidencia, ordenadas por prioridad, están en `PROPUESTAS-DE-MEJORA.md`. **No se implementaron.**
