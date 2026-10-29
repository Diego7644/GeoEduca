# Versión local de GEOEDUCA

Rama de trabajo: `feature/geoeduca-version-final`, creada desde `main` en `354190f`. No está publicada ni fusionada.

## Entorno

- PHP 8.2 con `pdo_pgsql` activo en el `php.ini` que usa `artisan serve`.
- Composer y Node.js 18+.
- PostgreSQL 16. En esta máquina el puerto local es 5433 porque 5432 ya estaba ocupado. El valor vive en `backend/.env`.
- No hace falta correo, SMS, NFC ni un molinete.

## Arranque

Desde la raíz, en dos terminales:

```bash
cd backend
php artisan migrate
php artisan serve
```

```bash
cd frontend
npm install
npm run dev
```

También se puede usar `scripts/iniciar-local.ps1`. Vite reenvía `/api` a `http://127.0.0.1:8000`.

## Cuentas

`php artisan db:seed` solo crea las cuentas de desarrollo si `APP_ENV=local` y `GEO_SEED_PROTOTYPE=true`. La contraseña es `GEO_DEV_PASSWORD` y no se copia a la documentación.

La identidad es el par DNI y rol. El id del usuario no es el id del estudiante. El docente y el auxiliar se distinguen por `staff_members.kind`.

## Funciones por rol

- Administración: alta y edición de estudiantes, alta de apoderados y personal, vínculo, desvínculo, restablecimiento de clave, reportes y configuración.
- Auxiliar: QR manual o con cámara, salida, cierre de jornada, registro manual, bitácora y revisión de justificaciones.
- Docente: tutoría del día, filtro por fecha y justificaciones de sus estudiantes.
- Estudiante: sus datos, QR, asistencia del día, tardanzas y ausencias.
- Apoderado: solo sus estudiantes vinculados, historial, campana y justificaciones.

## Pruebas

En esta rama, el 2026-10-29:

- PHPUnit: 183 pruebas, 707 aserciones, OK.
- Vitest: 13 pruebas, OK.
- `npm run build`: OK. El paquete JS supera 500 kB por las librerías de QR.

La puerta, la salida, el cierre y la configuración exigen auxiliar o administración en la ruta. El aula exige docente. La revisión de justificaciones exige personal o administración, y el servicio sigue comprobando la tutoría.

## Pendiente

- La cámara abre el video cuando el navegador lo permite. En la prueba no se decodificó un QR desde ese video; el código manual sí registró la entrada.
- El cierre de jornada se probó antes de la hora de salida y respondió que aún no corresponde. No se generaron ausencias masivas.
- `npm audit` deja avisos en Vite, Vitest y React Router. Corregirlos exige versiones mayores. No se forzó esa actualización.
