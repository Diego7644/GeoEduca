# Matriz de cumplimiento funcional

El repositorio no contiene el informe original de requisitos del producto. `docs/01-guia-pruebas-calidad/` es la guía académica de pruebas y no se usa como especificación. Esta matriz toma como fuente el encargo de finalización y los módulos ya existentes. No atribuye requisitos a un documento que no está en el árbol.

Criterio del porcentaje: entra en el numerador solo un requisito **completamente funcional** con evidencia de esta rama (navegador sobre PostgreSQL local, o PHPUnit cuando el caso no depende de la interfaz). Un requisito parcial, no implementado o bloqueado por una dependencia externa no suma.

Fecha de la evidencia: 2026-10-29. Rama `feature/geoeduca-version-final`.

| ID | Descripción | Fuente | Implementación | Estado | Evidencia | Limitación |
|---|---|---|---|---|---|---|
| A1 | Registrar estudiante | Encargo, fase 2 | `POST /api/admin/estudiantes` y formulario de administración | Completamente funcional | Navegador: alta de DNI 88001122, mensaje «Estudiante registrado.», cuenta 7 distinta del estudiante | Datos ficticios locales |
| A2 | Editar estudiante | Encargo, fase 2 | `PUT /api/admin/estudiantes/{student}` y botón Editar | Completamente funcional | Navegador: el grado quedó `1 Secundaria A` y la recarga del directorio lo devolvió | — |
| A3 | Consultar estudiantes | Encargo, fase 2 | `GET /api/admin/directorio` | Completamente funcional | El directorio mostró el alta y los apoderados | — |
| A4 | Registrar apoderado | Encargo, fase 2 | `POST /api/admin/cuentas` con rol `parent` | Completamente funcional | Navegador: DNI 88001133, mensaje «Cuenta registrada.» | — |
| A5 | Registrar docente o auxiliar | Encargo, fase 2 | El mismo alta con `staff_kind` | Completamente funcional | Navegador: auxiliar 88001144 listado como AUXILIAR. La opción docente usa el mismo endpoint | No se creó un docente nuevo en esta pasada |
| A6 | Vincular apoderado y estudiante | Encargo, fase 2 | `POST /api/admin/apoderados` | Completamente funcional | El directorio pasó a «Apoderados: Apoderado Demostracion Final» | El apoderado no puede vincularse solo |
| A7 | Desvincular | Encargo, fase 2 | `DELETE /api/admin/apoderados` y botón Desvincular | Completamente funcional | Se vinculó a Lucía y el botón dejó «Apoderados: ninguno». El vínculo del alumno de demostración se conservó | — |
| A8 | Consultar asistencias | Encargo, fase 2 | Reportes y bitácora | Completamente funcional | Fila `2026-10-28 / Alumno Demostracion Final / late / qr / 16:38:37` | — |
| A9 | Revisar justificaciones | Encargo, fase 6 | `POST /api/asistencia/justificaciones/{id}/revisar` | Completamente funcional | El auxiliar aprobó en pantalla; el estado quedó `approved` | Administración no tiene lista propia; usa el mismo endpoint si entra por un cliente autorizado. La pantalla del auxiliar y la del docente sí revisan |
| A10 | Consultar reportes y CSV | Encargo, fase 7 | `GET /api/asistencia/reportes` y botón CSV | Completamente funcional | CSV capturado: `2026-10-28,Alumno Demostracion Final,late,qr,16:38:37` tras filtrar fecha y DNI | La exportación de esta pasada la hizo el auxiliar creado en el flujo. La pantalla es la misma de administración |
| A11 | Horarios y tolerancias | Encargo, fase 2 y 3 | `PUT /api/asistencia/configuracion`, solo auxiliar o administración | Completamente funcional | La tolerancia pasó de 10 a 11, sobrevivió la recarga y se restauró a 10. El estudiante recibe 403 | — |
| A12 | Restablecer contraseña | Encargo, fase 2 | `POST /api/admin/usuarios/{user}/clave` | Completamente funcional | El formulario cambió la clave del docente, el login nuevo respondió 200 y se restauró la clave local. La respuesta no incluye la contraseña | — |
| D1 | Docente ve su tutoría | Encargo, fase 2 | `GET /api/asistencia/docente` filtrado por `homeroom_staff_id` | Completamente funcional | El panel mostró a Alumno Demostracion Final. Validar un QR con esa sesión respondió 403 y el menú no ofrece la puerta | — |
| D2 | Historial del docente | Encargo, fase 2 | Estado del día y lista de marcaciones de la tutoría | Completamente funcional | Filtro 2026-10-28 en tardanza e historial con 2026-10-28 y 2026-10-29 | — |
| X1 | Auxiliar consulta estudiantes | Encargo, fase 2 | Directorio del panel auxiliar | Completamente funcional | El panel cargó con el operador nuevo | — |
| X2 | Leer QR con la cámara | Encargo, fase 4 | `QrCamera` y `html5-qrcode` | Parcialmente funcional | El botón abrió un elemento `video`. Si la cámara falla, el panel sigue con el código manual | No se acercó un QR al lente, así que no hay una lectura decodificada |
| X3 | Código QR manual | Encargo, fase 4 | `POST /api/asistencia/qr/validar` | Completamente funcional | Mensaje «Ingreso registrado con QR.» y fila en PostgreSQL | El token se obtuvo del mismo generador que muestra el estudiante y se pegó en el formulario |
| X4 | Entrada y salida | Encargo, fase 3 | Validar QR y `POST /api/asistencia/salida` | Completamente funcional | Bitácora: ingreso 16:38:37 y salida 16:38:52. Un segundo intento dijo «La salida de hoy ya fue registrada.» | — |
| X5 | Registro manual autorizado | Encargo, fase 3 | `POST /api/asistencia/auxiliar/aprobar` | Completamente funcional | Diego Valenzuela quedó en la bitácora a las 10:03:48 por falla de batería | — |
| X6 | Registros del día | Encargo, fase 2 | Bitácora del auxiliar | Completamente funcional | La tabla mostró los dos registros del día | El nombre de operador de la fila es el auxiliar de la sesión, no un historial de operadores anteriores |
| X7 | Incidencias sencillas | Encargo, fase 2 | Lista de intentos inválidos, vencidos o reutilizados | Completamente funcional | El panel dijo «Ese código QR ya fue utilizado.» PHPUnit comprueba que el intento queda guardado fuera de la transacción revertida | No hay un módulo de tickets aparte |
| E1 | Estudiante consulta sus datos | Encargo, fase 2 | `GET /api/asistencia/estudiante` | Completamente funcional | Pantalla «Alumno Demostracion Final», código FIN-8800 | — |
| E2 | QR estándar | Encargo, fase 4 | `qrcode` sobre el token de Laravel | Completamente funcional | Imagen `data:image/png` y token de 32 caracteres en pantalla | La cámara no lo leyó; ver X2 |
| E3 | Renovar QR | Encargo, fase 4 | `POST /api/asistencia/estudiante/token` | Completamente funcional | El botón cambió el token y la pantalla mostró 60s | — |
| E4 | Asistencia, tardanza y ausencia del estudiante | Encargo, fase 2 y 3 | Panel del estudiante | Completamente funcional | Historial `2026-10-28 tardanza 16:38 16:38 qr`. El cierre antes de la hora de salida respondió que la jornada no ha terminado | La ausencia masiva no se ejecutó para no marcar al resto de la matrícula |
| P1 | Apoderado solo ve vinculados | Encargo, fase 2 y 9 | `wards()` | Completamente funcional | El portal mostró únicamente a 88001122. El alta propia del apoderado quedó reemplazada por un aviso | — |
| P2 | Historial del apoderado | Encargo, fase 2 | Tabla del portal | Completamente funcional | Fila QR 16:38, tardanza 518 min, salida 16:38 | — |
| P3 | Notificación interna | Encargo, fase 5 | Campana y `in_app_notifications` | Completamente funcional | Badge «1» y texto «Tardanza registrada · Alumno Demostracion Final • 16:38» | No se volvió a contar el badge después de abrir la lista |
| P4 | Enviar justificación | Encargo, fase 6 | `POST /api/asistencia/justificacion` | Completamente funcional | «Solicitud enviada a Coordinación Académica.» y estado `submitted` en la pantalla | No se ofrece adjunto: el formulario dice que no admite archivos |
| P5 | Consultar el resultado | Encargo, fase 6 | `GET /api/asistencia/justificaciones` del apoderado | Completamente funcional | La pantalla del apoderado mostró `2026-10-28: approved` | — |
| S1 | No marcar ausencia antes de tiempo | Encargo, fase 3 | `cerrarJornada` | Completamente funcional | PHPUnit `test_alta_y_cierre_de_jornada` espera 422 si aún no es la hora de salida | No se ejecutó el cierre en el navegador para no ausentar al resto de la matrícula |
| S2 | Auditoría de cambios | Encargo, fase 3 y 9 | `audit_logs` y la lista de administración | Completamente funcional | La pantalla mostró `student_created`, `account_created` y la vinculación | — |
| Q1 | Expiración, un solo uso y validación en servidor | Encargo, fase 4 | `qr_tokens` | Completamente funcional | El token del alumno quedó consumido en PostgreSQL. PHPUnit cubre reutilización y expiración | El mensaje de QR expirado no se repitió en esta pasada de navegador |
| N1 | Aislamiento de notificaciones | Encargo, fase 5 y 9 | Filtro por `user_id` | Completamente funcional | La campana del apoderado mostró solo su aviso | — |
| R1 | Estados vacíos y validación | Encargo, fase 7 | Tablas y validación Laravel | Completamente funcional | Cola vacía con mensaje. El reporte filtrado no mezcló otros estudiantes | — |

## Porcentaje

- Completamente funcional: 33
- Parcialmente funcional: 1 (la cámara abre video, pero no se decodificó un QR)
- No implementado: 0 en esta lista
- Bloqueado por dependencia externa: 0

Denominador: 34 requisitos de la tabla. Numerador: 33. Porcentaje de cumplimiento completo: **33/34 = 97,1 %**. El parcial no se cuenta como cumplido.

Queda fuera del denominador, porque el encargo lo excluye: NFC, molinetes y envío por correo, SMS o WhatsApp.
