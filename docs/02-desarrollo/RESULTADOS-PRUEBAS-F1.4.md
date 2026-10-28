# Pruebas de F1.4

Fecha: 2026-10-28. PHP 8.2.12. No se cambiaron expectativas para ocultar fallos. El contrato de login, `/me` y logout no se modificó.

## PHPUnit

`php vendor/bin/phpunit` en `backend/`: **173 pruebas, 618 aserciones, 0 fallos.** Igual que F1.3. Esta entrega no toca el backend.

## Vitest

`npm test` en `frontend/`: **13 pruebas, 0 fallos** (Vitest 2.1.9, jsdom).

| Caso | Resultado |
|---|---|
| Sesión válida: `/me` manda sobre el usuario guardado | Panel del rol confirmado |
| Sesión inválida (401) | Login y almacenamiento vacío |
| Token expirado (401 de `/me`) | Login |
| Logout | Revoca el Bearer y vuelve al login |
| Redirección sin token | Login |
| Rol distinto | Estudiante en `/auxiliar` acaba en `/estudiante` |
| Error de red | No abre el panel |
| Error de servidor | No abre el panel |
| `/me` tardío después de logout | No restaura la sesión |
| Aviso de recuperación | Sin formulario ni mención de envío |

## Navegador local

Laravel en `http://127.0.0.1:8000` (PHP con `pdo_pgsql`; el `artisan serve` del CLI no heredaba la extensión) y Vite en `http://127.0.0.1:5173`. PostgreSQL en el puerto 5433. Cuentas de desarrollo ya sembradas (`10000001` estudiante, `10000002` tutor). No se imprimen contraseñas.

| Paso | Resultado |
|---|---|
| Login de estudiante desde el formulario | 200, navega a `/estudiante`. El encabezado muestra el nombre confirmado por la API |
| Recarga de `/estudiante` | Primero «Comprobando sesión…», luego el mismo panel |
| Estudiante abre `/auxiliar` | Vuelve a `/estudiante` |
| Logout | Vuelve a `/login`. Entrar a `/estudiante` sin token sigue en el login |
| Token expirado y usuario guardado como `staff` | «Comprobando sesión…», luego `/login` y almacenamiento vacío. No abre el panel auxiliar |
| Token inventado con el mismo usuario guardado | Igual: `/login` y almacenamiento vacío |
| Tutor desde el formulario | Navega a `/padre` y carga el portal familiar |
| «¿Olvidaste tu contraseña?» | Aviso de que no hay correo ni SMS y de que no se indica si la cuenta existe |
| NFC en el login | Aviso de que el carnet no inicia sesión |
| `localStorage` | Solo `geoeduca_token` y `geoeduca_user`. No contiene la contraseña |
| Overlay de error de Vite | No apareció |

API comprobada aparte del navegador, con la misma base: `/me` 200, panel de otro rol 403, logout 200, `/me` después 401, token inventado 401. La respuesta de login no incluye `password`.

`artisan serve` sin `pdo_pgsql` en el proceso hijo respondía 500 (`could not find driver`). El navegador se probó contra `php -S` con la extensión cargada. No es un fallo de F1.4.
