# Estado del desarrollo integral

Rama local: `feature/desarrollo-integral`. No está publicada. Base: `main` en `325aac8` (PR #6, F1.4).

## Hecho en esta ejecución

- Identidad: `students.user_id` y `guardian_student` son el vínculo. `users.id` no se usa como `students.id`. El personal se distingue con `staff_members.kind` (`auxiliar` o `docente`). El rol `admin` inicia sesión y administra vínculos.
- Asistencia del día en `attendance_records`, con origen `qr` o `manual`, sin duplicar la fecha.
- QR de 32 bytes hex, 60 segundos, un solo uso, validado por el auxiliar. El intento queda en `attendance_attempts`.
- El GPS se guarda como intento. No marca asistencia. Si la precisión es peor que el radio, el resultado es `uncertain`.
- Notificaciones internas al apoderado y al auxiliar, con `dedupe_key`. No hay correo, SMS ni push.
- Comando `php artisan geoeduca:import-json` (`--dry-run`). No copia contraseñas del JSON ni borra el archivo. El historial calculado del prototipo no se importa.
- Paneles: estudiante y auxiliar siguen su diseño y leen la API real. Docente y administración son pantallas nuevas y acotadas.
- Cada petición API olvida el guard de Sanctum para no reutilizar el usuario de la petición anterior.

## No terminado

- El panel del apoderado y el del auxiliar todavía muestran piezas visuales del prototipo (casos de contingencia vacíos, horario de clases vacío). No se rellenan con datos inventados.
- No hay lector NFC, cámara de torniquete ni proveedor de correo.
- La cookie HttpOnly no se activó: el frontend y la API siguen en orígenes distintos.
- PHPUnit de esta rama: 178 pruebas, 654 aserciones, 0 fallos. Vitest: 13 pruebas, 0 fallos.
- No se volvió a recorrer el navegador después de estos cambios. La verificación de pantalla de F1.4 sigue siendo la de `main`.
- Falta sembrar en PostgreSQL local el catálogo con `geoeduca:import-json` si se quiere ver más estudiantes que el grafo de prueba.

## Cómo retomar

1. `git checkout feature/desarrollo-integral` en `D:\geoeduca-integracion`.
2. `php vendor/bin/phpunit` y `npm test`.
3. Siguiente trabajo útil: simplificar el panel del apoderado para que solo muestre registros reales, y probar el login de auxiliar y el QR en el navegador con `php -d extension=pdo_pgsql -S` desde `backend/public`.
