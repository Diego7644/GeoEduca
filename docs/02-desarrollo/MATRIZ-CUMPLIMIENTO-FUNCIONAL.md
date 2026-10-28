# Matriz de cumplimiento funcional

El repositorio no contiene el informe original de requisitos del producto. `docs/01-guia-pruebas-calidad/` es la guía académica de pruebas y no se usa como especificación. Esta matriz toma como fuente el encargo de finalización y los módulos ya existentes. No atribuye requisitos a un documento que no está en el árbol.

Criterio del porcentaje: entra en el numerador solo un requisito **completamente funcional** con evidencia de esta rama (navegador sobre PostgreSQL local, o PHPUnit cuando el caso no depende de la interfaz). Un requisito parcial, no implementado o bloqueado por una dependencia externa no suma.

Fecha de la evidencia: 2026-10-28. Rama `feature/geoeduca-version-final`.

| ID | Descripción | Fuente | Implementación | Estado | Evidencia | Limitación |
|---|---|---|---|---|---|---|
| A1 | Registrar estudiante | Encargo, fase 2 | `POST /api/admin/estudiantes` y formulario de administración | Completamente funcional | Navegador: alta de DNI 88001122, mensaje «Estudiante registrado.», cuenta 7 distinta del estudiante | Datos ficticios locales |
| A2 | Editar estudiante | Encargo, fase 2 | `PUT /api/admin/estudiantes/{student}` y botón Editar | Parcialmente funcional | El formulario aparece en el directorio | No se pulsó Guardar en el navegador |
| A3 | Consultar estudiantes | Encargo, fase 2 | `GET /api/admin/directorio` | Completamente funcional | El directorio mostró el alta y los apoderados | — |
| A4 | Registrar apoderado | Encargo, fase 2 | `POST /api/admin/cuentas` con rol `parent` | Completamente funcional | Navegador: DNI 88001133, mensaje «Cuenta registrada.» | — |
| A5 | Registrar docente o auxiliar | Encargo, fase 2 | El mismo alta con `staff_kind` | Completamente funcional | Navegador: auxiliar 88001144 listado como AUXILIAR. La opción docente usa el mismo endpoint | No se creó un docente nuevo en esta pasada |
| A6 | Vincular apoderado y estudiante | Encargo, fase 2 | `POST /api/admin/apoderados` | Completamente funcional | El directorio pasó a «Apoderados: Apoderado Demostracion Final» | El apoderado no puede vincularse solo |
| A7 | Desvincular | Encargo, fase 2 | `DELETE /api/admin/apoderados` y botón Desvincular | Parcialmente funcional | Botón visible y endpoint publicado | No se ejecutó la desvinculación para no romper el flujo de prueba |
| A8 | Consultar asistencias | Encargo, fase 2 | Reportes y bitácora | Completamente funcional | Fila `2026-10-28 / Alumno Demostracion Final / late / qr / 16:38:37` | — |
| A9 | Revisar justificaciones | Encargo, fase 6 | `POST /api/asistencia/justificaciones/{id}/revisar` | Completamente funcional | El auxiliar aprobó en pantalla; el estado quedó `approved` | Administración no tiene lista propia; usa el mismo endpoint si entra por un cliente autorizado. La pantalla del auxiliar y la del docente sí revisan |
| A10 | Consultar reportes y CSV | Encargo, fase 7 | `GET /api/asistencia/reportes` y botón CSV | Completamente funcional | CSV capturado: `2026-10-28,Alumno Demostracion Final,late,qr,16:38:37` tras filtrar fecha y DNI | La exportación de esta pasada la hizo el auxiliar creado en el flujo. La pantalla es la misma de administración |
| A11 | Horarios y tolerancias | Encargo, fase 2 y 3 | `PUT /api/asistencia/configuracion` | Parcialmente funcional | Pantalla de configuración ya conectada | Esta pasada no guardó un horario nuevo |
| A12 | Restablecer contraseña | Encargo, fase 2 | `POST /api/admin/usuarios/{user}/clave` | Parcialmente funcional | El formulario está en administración. El controlador hashea y revoca tokens, sin devolver la clave | No se envió el formulario en el navegador |
| D1 | Docente ve su tutoría | Encargo, fase 2 | `GET /api/asistencia/docente` filtrado por `homeroom_staff_id` | Parcialmente funcional | Pantalla y filtro de fecha escritos. PHPUnit cubre el acceso denegado del docente a la puerta | Esta pasada no abrió `/docente` |
| D2 | Historial del docente | Encargo, fase 2 | Estado del día elegido | Parcialmente funcional | La pantalla muestra el estado de esa fecha | No hay un historial largo distinto del día filtrado |
| X1 | Auxiliar consulta estudiantes | Encargo, fase 2 | Directorio del panel auxiliar | Completamente funcional | El panel cargó con el operador nuevo | — |
| X2 | Leer QR con la cámara | Encargo, fase 4 | `QrCamera` y `html5-qrcode` | Bloqueado por dependencia externa | El botón «Abrir cámara» está en el panel | El navegador de prueba no entregó video. No se declara la lectura por cámara |
| X3 | Código QR manual | Encargo, fase 4 | `POST /api/asistencia/qr/validar` | Completamente funcional | Mensaje «Ingreso registrado con QR.» y fila en PostgreSQL | El token se obtuvo del mismo generador que muestra el estudiante y se pegó en el formulario |
| X4 | Entrada y salida | Encargo, fase 3 | Validar QR y `POST /api/asistencia/salida` | Completamente funcional | Bitácora: ingreso 16:38:37 y salida 16:38:52. Un segundo intento dijo «La salida de hoy ya fue registrada.» | — |
| X5 | Registro manual autorizado | Encargo, fase 3 | `POST /api/asistencia/auxiliar/aprobar` | Parcialmente funcional | El modal «Registro forzado por DNI» sigue en la pantalla. La suite funcional ya cubría el alta manual | No se confirmó un ingreso manual nuevo en esta pasada |
| X6 | Registros del día | Encargo, fase 2 | Bitácora del auxiliar | Completamente funcional | La tabla mostró los dos registros del día | El nombre de operador de la fila es el auxiliar de la sesión, no un historial de operadores anteriores |
| X7 | Incidencias sencillas | Encargo, fase 2 | Cola presencial vacía y registro manual | Parcialmente funcional | La cola dice «No hay casos pendientes» en lugar de inventar casos | No hay un módulo de tickets aparte |
| E1 | Estudiante consulta sus datos | Encargo, fase 2 | `GET /api/asistencia/estudiante` | Completamente funcional | Pantalla «Alumno Demostracion Final», código FIN-8800 | — |
| E2 | QR estándar | Encargo, fase 4 | `qrcode` sobre el token de Laravel | Completamente funcional | Imagen `data:image/png` y token de 32 caracteres en pantalla | La cámara no lo leyó; ver X2 |
| E3 | Renovar QR | Encargo, fase 4 | `POST /api/asistencia/estudiante/token` | Parcialmente funcional | El botón «Generar nuevo código QR» está visible | No se pulsó en esta pasada |
| E4 | Asistencia, tardanza y ausencia del estudiante | Encargo, fase 2 y 3 | Panel del estudiante | Completamente funcional para tardanza | «1 tardanza(s)» e «Ingreso marcado: 16:38» | No se cerró la jornada, así que no apareció una ausencia. El cierre anticipado responde 422 en PHPUnit |
| P1 | Apoderado solo ve vinculados | Encargo, fase 2 y 9 | `wards()` | Completamente funcional | El portal mostró únicamente a 88001122. El alta propia del apoderado quedó reemplazada por un aviso | — |
| P2 | Historial del apoderado | Encargo, fase 2 | Tabla del portal | Completamente funcional | Fila QR 16:38, tardanza 518 min, salida 16:38 | — |
| P3 | Notificación interna | Encargo, fase 5 | Campana y `in_app_notifications` | Completamente funcional | Badge «1» y texto «Tardanza registrada · Alumno Demostracion Final • 16:38» | No se volvió a contar el badge después de abrir la lista |
| P4 | Enviar justificación | Encargo, fase 6 | `POST /api/asistencia/justificacion` | Completamente funcional | «Solicitud enviada a Coordinación Académica.» y estado `submitted` en la pantalla | El archivo adjunto guarda el nombre, no el binario |
| P5 | Consultar el resultado | Encargo, fase 6 | `GET /api/asistencia/justificaciones` del apoderado | Completamente funcional | Después de la aprobación, la lista de esa cuenta devolvió `approved` | La pantalla del apoderado se vio en `submitted` antes de la revisión; el resultado aprobado se comprobó con la misma sesión de API |
| S1 | No marcar ausencia antes de tiempo | Encargo, fase 3 | `cerrarJornada` | Completamente funcional | PHPUnit `test_alta_y_cierre_de_jornada` espera 422 si aún no es la hora de salida | No se ejecutó el cierre en el navegador para no ausentar al resto de la matrícula |
| S2 | Auditoría de cambios | Encargo, fase 3 y 9 | `audit_logs` | Parcialmente funcional | Alta, vínculo, salida y revisión escriben auditoría | `GET /api/admin/actividad` no se abrió en el navegador |
| Q1 | Expiración, un solo uso y validación en servidor | Encargo, fase 4 | `qr_tokens` | Completamente funcional | El token del alumno quedó consumido en PostgreSQL. PHPUnit cubre reutilización y expiración | El mensaje de QR expirado no se repitió en esta pasada de navegador |
| N1 | Aislamiento de notificaciones | Encargo, fase 5 y 9 | Filtro por `user_id` | Completamente funcional | La campana del apoderado mostró solo su aviso | — |
| R1 | Estados vacíos y validación | Encargo, fase 7 | Tablas y validación Laravel | Completamente funcional | Cola vacía con mensaje. El reporte filtrado no mezcló otros estudiantes | — |

## Porcentaje

- Completamente funcional: 23
- Parcialmente funcional: 10
- No implementado: 0 en esta lista
- Bloqueado por dependencia externa: 1 (cámara)

Denominador: 34 requisitos de la tabla. Numerador: 23. Porcentaje de cumplimiento completo: **23/34 = 67,6 %**. Los parciales no se cuentan como cumplidos.

Queda fuera del denominador, porque el encargo lo excluye: NFC, molinetes y envío por correo, SMS o WhatsApp.
