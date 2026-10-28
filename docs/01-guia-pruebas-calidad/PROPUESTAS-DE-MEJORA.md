# Propuestas de mejora priorizadas – GEOEDUCA

Corresponde a la **lista priorizada de mejoras** de la Actividad 4 de la guía. **Son propuestas: ninguna se implementó** (la práctica prohíbe modificar el código de producción y corregir defectos).
Cada mejora se apoya en una incidencia de `REGISTRO-DE-INCIDENCIAS.md` y en una evidencia comprobada. El esfuerzo es una **estimación cualitativa** (Bajo, Medio, Alto), salvo donde
SonarQube informa minutos; no es un dato medido.

**Criterio de prioridad:** primero lo que expone datos de menores o permite acciones no autorizadas; después lo que debilita defensas o impide la funcionalidad central; después los errores visibles;
al final la higiene y la usabilidad. Dentro de cada nivel se prefiere lo que cuesta menos y desbloquea otras mejoras.

## 1. Lista priorizada

| N.º | Mejora propuesta | Incidencia | Por qué en este orden | Esfuerzo | Cómo se verificará |
|---:|---|---|---|---|---|
| 1 | Exigir autenticación real en `/api/auth/me` y en todas las rutas `/api/asistencia/*` (hoy `routes/api.php` solo tiene un `TODO` para `auth:sanctum`; Sanctum ya está instalado) | INC-01 | Cualquier cliente accede hoy a datos de menores. Todo lo demás de seguridad depende de esto | Medio (requiere base de datos o proveedor de usuarios) | 12 pruebas de `AutenticacionRequeridaTest` pasan a aprobadas; S-01 a S-04 |
| 2 | Autorizar por rol en el servidor (por ejemplo políticas o *gates*) y no confiar en el rol del navegador | INC-02 | Sin esto un estudiante accede a rutas y datos de otros perfiles; depende de la mejora 1 | Medio | 6 pruebas de `AutorizacionPorRolTest`; S-05; repetir UI-06 |
| 3 | Actualizar `laravel/framework` a una versión que corrija `PKSA-b41t-wbzk-9q51`, y evaluar la actualización de Vite (6 o superior) | INC-16 | Es una corrección de bajo riesgo funcional que cierra el aviso crítico y varias vulnerabilidades de `npm audit`. Debe hacerse antes de conectar autenticación real | Medio (revisar compatibilidad) | `composer audit` y `npm audit` sin avisos críticos ni altos; suite completa de PHPUnit |
| 4 | Invalidar el token al cerrar sesión (revocar el token en el servidor) | INC-03 | Un token copiado sigue vigente tras salir; se resuelve junto con la mejora 1 | Bajo (con Sanctum) | 3 pruebas de `SesionYLogoutTest`; S-06 |
| 5 | Limitar los intentos de inicio de sesión (`RateLimiter` o `throttle`) | INC-04 | Impide la fuerza bruta sobre DNI de 8 dígitos; bajo costo | Bajo | Prueba `LimiteIntentosLoginTest` (esperaba `429`); S-08 |
| 6 | Eliminar del paquete del frontend las cuentas de respaldo con contraseñas fijas (`VITE_USE_MOCK_AUTH`) o sacarlas de la compilación de producción | INC-12 | Las ve cualquiera que abra el paquete. Sonar las marca con 30 min de esfuerzo cada una | Bajo | Sonar sin `S2068`; seguridad A en el modelo MQR |
| 7 | Evitar el HTTP 500 con entradas no textuales: validar el tipo antes del `(string)` en `LoginRequest::prepareForValidation()` (línea 22) | INC-11 | Error de servidor provocable con una petición; corrección de una línea | Bajo | 2 pruebas de `EntradasHostilesTest` (esperan `422`) |
| 8 | Desplegar con `APP_DEBUG=false` y evitar trazas en la respuesta de errores | INC-07 | Es una decisión de configuración; evita revelar la estructura interna | Bajo | Prueba de S-11; 404 sin `trace` |
| 9 | Corregir los problemas de accesibilidad: asociar cada `<label>` con su control (`htmlFor`/`id`) y usar elementos nativos (`button`) o `role` y teclado en `Modal.jsx` y `Sidebar.jsx` | INC-15 | Son 9 incidencias de 5 min cada una (45 min en total según Sonar); mejora la fiabilidad de C a A y beneficia a usuarios con discapacidad | Bajo | Sonar sin `S6853` ni `S6848`; calificación de fiabilidad A en MQR |
| 10 | Mostrar fecha y hora reales en los paneles y retirar la marca «Sincronizado» mientras no haya sincronización | INC-08 | Hoy el padre y el auxiliar ven una hora falsa | Bajo | UI-07 |
| 11 | Implementar de verdad QR dinámico, geolocalización, notificaciones y persistencia de justificaciones | INC-05, INC-06 | Son la razón de ser del producto; es el mayor esfuerzo y depende de la base de datos y de las mejoras 1 y 2 | Alto | Actualizar B-01 a B-04, UI-08 y las pruebas de caracterización de la ausencia |
| 12 | Añadir cabeceras de seguridad (`X-Content-Type-Options`, ocultar `X-Powered-By` con `expose_php=Off`) y forzar `Accept: application/json` en la API | INC-09, INC-10 | Defensa en profundidad de bajo costo | Bajo | S-09, S-10 e I-05 |
| 13 | Traducir los errores de conexión a un mensaje claro («No se pudo conectar con el servidor») en `Login.jsx` | INC-14 | Evita consultas de soporte; bajo costo | Bajo | UI-12 |
| 14 | Corregir el recorte en 375 px (ancho máximo del selector y de las etiquetas, texto con saltos de línea) en `DashboardAuxiliar.jsx` | INC-13 | Mejora el uso desde el móvil; menor prioridad | Bajo | UI-11 |

## 2. Mejoras de proceso y de pruebas

| N.º | Mejora | Motivo | Esfuerzo |
|---:|---|---|---|
| P1 | Medir la cobertura (instalar PCOV o Xdebug, generar Clover e importarlo en SonarQube) | Sustituir «no medida» por un valor real; la guía no lo exige, pero habilita la condición de cobertura del gate | Medio |
| P2 | Pruebas de frontend con Vitest tras la actualización de Vite, y pruebas de extremo a extremo con Playwright | Automatizar UI-01 a UI-12 | Medio |
| P3 | Integración continua que ejecute PHPUnit, SonarQube, `composer audit` y `npm audit` | Detectar regresiones y vulnerabilidades en cada cambio | Medio |
| P4 | Revisar el Quality Gate: añadir condiciones sobre el **código global** (por ejemplo, vulnerabilidades = 0) | El gate actual solo mira el código nuevo y no habría detectado ninguno de los defectos existentes | Bajo |
| P5 | Considerar la edición de SonarQube con análisis de flujo de datos para PHP | Community Build no detecta la falta de autenticación ni la inyección | Alto (licencia) |
| P6 | Marcar los 27 requisitos como *esperados* mientras se corrigen (por ejemplo con un grupo de PHPUnit), sin ocultarlos | Mantener la integración continua en verde sin perder el listado de lo que falta | Bajo |

## 3. Resumen por prioridad

| Prioridad | Mejoras | Incidencias |
|---|---|---|
| Crítica | 1, 2 | INC-01, INC-02 |
| Alta | 3, 4, 5, 11 | INC-16, INC-03, INC-04, INC-05, INC-06 |
| Media | 6, 7, 8, 9, 10 | INC-12, INC-11, INC-07, INC-15, INC-08 |
| Baja | 12, 13, 14 | INC-09, INC-10, INC-14, INC-13 |

*Nota:* la mejora 11 figura como prioridad Alta por la gravedad de sus incidencias, pero se ejecuta al final por su dependencia de las demás.
