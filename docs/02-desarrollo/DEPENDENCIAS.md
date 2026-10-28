# Dependencias y decisiones

## Orden técnico

- M1 no arranca en serio hasta que M0 esté en el repositorio principal (esta rama, autorizada).
- M2 necesita las tablas de M1 (`users`, tokens).
- M3 necesita usuarios reales (M2) para no seguir con tres cuentas fijas.
- M4 necesita personas (M3) y auth (M2); si se registra asistencia antes, se vuelve a grabar en el JSON.
- M5 y M6 necesitan un registro de asistencia real (M4).
- M7 necesita eventos de M4 y destinatarios de M2/M3.
- M8 necesita datos reales de M4 (y de M6/M7 si se reportan).
- M9 avanza en paralelo, pero la sesión del frontend espera M2.
- M10 empieza en M0 (la suite ya corre) y crece con cada módulo.

## Dependencias de software actuales

| Pieza | Versión en esta rama | Nota |
|---|---|---|
| PHP | 8.2.12 (entorno local de la práctica) | |
| Laravel | 11.57.0 | Aviso crítico `PKSA-b41t-wbzk-9q51`; actualizar en PR aparte |
| Sanctum | 4.x instalado, no usado | Se activa en F1 |
| PHPUnit | 11.5.57 (require-dev añadido) | No cambia paquetes de producción |
| Mockery | 1.6.15 | Igual |
| React | 18.3.1 | |
| Vite | 5.4.21 | `npm audit` reportó vulnerabilidades; actualizar en PR aparte |
| Axios | 1.20.0 | |

Producción `composer.lock`: 73 paquetes iguales a Diego. Frontend `package-lock.json` no se tocó.

## Decisiones

| ID | Decisión | Afecta | Recomendación | Estado |
|---|---|---|---|---|
| D1 | Rama destino del PR | M0 | `mejoras-interfaces-dinamicas` | **Tomada** |
| D2 | Fork o acceso de escritura | M0 | Fork si no hay escritura | Pendiente |
| D3 | Correo de autor público | M0 | noreply de GitHub | Configurado en el clon; falta autorizar commit |
| D5 | Motor de BD de producción | M1 | PostgreSQL o MySQL; SQLite solo en desarrollo y pruebas | Pendiente |
| D6 | Rol administrador | M3, M8 | Añadirlo: hoy nadie da de alta usuarios | Pendiente |
| D7 | Sesión: cookie HttpOnly o bearer | M2, M9 | Cookie HttpOnly si hay mismo dominio | Pendiente |
| D8 | Canal de notificaciones | M7 | Correo + avisos en la app primero | Pendiente |
| D9 | Lectura del QR | M5 | Lector USB tipo teclado (compatible con F2) | Pendiente |
| D10 | Privacidad de ubicación y menores | M6, M1 | Definirla antes de guardar coordenadas | Pendiente |

D4 no se usa (hueco histórico). Las pendientes no bloquean publicar M0. Sí bloquean empezar M1 en serio: D5.
