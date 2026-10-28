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

En esta rama, el 2026-10-28:

- PHPUnit: 179 pruebas, 668 aserciones, OK.
- Vitest: 13 pruebas, OK.
- `npm run build`: OK. El paquete JS supera 500 kB por las librerías de QR.

## Pendiente

- La cámara no se pudo leer en el navegador de prueba.
- Editar estudiante, desvincular, renovar QR, restablecer clave y guardar un horario nuevo no se pulsaron de punta a punta en esta pasada.
- El docente no se recorrió en el navegador.
- El cierre de jornada no se lanzó sobre la matrícula real para no crear ausencias masivas. PHPUnit cubre el rechazo antes de la hora de salida.
- El adjunto de una justificación guarda el nombre del archivo, no su contenido.
- `npm audit` reportó avisos en dependencias del frontend. No se aplicó una actualización forzada.
