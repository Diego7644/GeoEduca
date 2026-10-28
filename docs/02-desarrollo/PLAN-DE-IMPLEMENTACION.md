# Plan de implementación

Se basa en `DIAGNOSTICO-TECNICO.md`. **Es un plan: no se implementó nada de M1 en adelante.** Tamaños: S (días), M (1–2 semanas), L (más de 2 semanas); orden de magnitud, no compromiso.

## Principios

1. Primero el cimiento (datos y usuarios), después QR, GPS y avisos.
2. Seguridad antes de funciones nuevas que dependan de datos de menores.
3. Cada módulo se entrega con pruebas. No se cambia una expectativa para ocultar un fallo.
4. Pull Requests pequeños sobre la rama de integración, nunca un cambio masivo sin revisar.

## Mapa

```
M0 Integración ─┬─► M1 Persistencia ─► M2 AuthZ/AuthN ─┬─► M3 Personas ─► M4 Asistencia ─┬─► M5 QR ─┐
                │                                      │                                 ├─► M6 GPS ─┼─► M8 Reportes
                │                                      └─► M9 Frontend (en paralelo)      └─► M7 Avisos┘
                └─► M10 Calidad continua (transversal)
```

| Orden | Módulo | Tamaño | Riesgo | Bloquea a |
|---:|---|:---:|:---:|---|
| 1 | M0 Integración y base | S | Medio | Todo |
| 2 | M1 Persistencia | M | Medio | M2 a M8 |
| 3 | M2 Autenticación y autorización | M | Alto | M3 a M8 |
| 4 | M3 Personas y vínculos | M | Bajo | M4 |
| 5 | M4 Registro de asistencia | L | Alto | M5, M6, M7, M8 |
| 6 | M5 QR dinámico | M | Alto | — |
| 7 | M6 Geolocalización | M | Medio | — |
| 8 | M7 Notificaciones | M | Medio | — |
| 9 | M8 Reportes y configuración | M | Bajo | — |
| — | M9 Frontend | M | Bajo | Paralelo a M2–M8 |
| — | M10 Calidad continua | M | Bajo | Transversal |

M0 está en `main` (`4992279`). F1.1 (esquema, modelos, seeders, pruebas) está en `feature/f1-persistencia`, sin fusionar. La API sigue en JSON.

## Primera funcionalidad: F1 = M1 + M2

Por qué primero: todo lo demás la necesita; cierra los defectos críticos de seguridad con riesgo funcional bajo; ya tiene criterios en `RequisitosSeguridad`; no depende de QR ni de notificaciones.

Pull Requests previstos:

1. **PR-F1.1** Migraciones y modelos (users, personal, estudiantes, tutores, asistencia, etc.) y datos de arranque solo para desarrollo. Implementado en local; falta autorizar push y PR.
2. **PR-F1.2** Login contra la base de datos, contraseñas con hash, tokens Sanctum con expiración, `logout` que revoca.
3. **PR-F1.3** Middleware `auth:sanctum` salvo `health` y `login`; middleware de rol y políticas tutor→hijo. Retirar la sesión del JSON.
4. **PR-F1.4** `throttle`, `LoginRequest` (DNI como arreglo → 422), `APP_DEBUG=false` en la plantilla, cabeceras, recuperación con código hasheado y sin devolverlo.
5. **PR-F1.5** Frontend: retirar cuentas de respaldo, validar sesión con `GET /auth/me`, errores claros.

Detalle por módulo (alcance, archivos, pruebas) se mantiene alineado con el diagnóstico: no se copian versiones antiguas sobre archivos de Diego. `AsistenciaStore` se divide cuando toque M4, no en F1 más de lo necesario para dejar de usarlo como sesión.

## Fuera de F1 (recordatorio)

M3 altas de personas; M4 registro real de entrada/salida; M5 QR firmado de un solo uso; M6 perímetro en el servidor; M7 correo + avisos persistentes; M8 reportes sobre datos reales; M9 accesibilidad y recorte móvil; M10 cobertura e integración continua.
