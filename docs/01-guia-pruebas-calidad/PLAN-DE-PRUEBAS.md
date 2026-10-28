# Plan de pruebas – GEOEDUCA

Corresponde a la **Actividad 1** (tablas 2 y 3) de la *Guía de Práctica de Ejecución de Pruebas*. La descripción del proyecto (tabla 1) está en
`INFORME-TECNICO-FINAL.md`, sección 2. Los resultados de cada caso se detallan en las matrices de origen
(`api/matriz-resultados-api.md` y `matriz-resultados-ui.md`); esta página reúne la planificación y enlaza con la ejecución.

## 1. Alcance

Se evalúa el **estado actual** del prototipo de GEOEDUCA: backend Laravel 11 con autenticación simulada y frontend React 18 con datos fijos.
**No se implementa ninguna funcionalidad ni se corrige ningún defecto** (restricción de la práctica). El criterio de resultado es el del
`README.md`: *Aprobado* si el comportamiento real coincide con el esperado, *Fallido* si la funcionalidad existe y difiere (en seguridad, el
esperado es el de un sistema seguro) y *Bloqueado* si la funcionalidad no existe y no se puede evaluar.

## 2. Tipos de prueba seleccionados y justificación

La justificación completa está en `sonarqube/RESUMEN-ACADEMICO.md` (sección 4) y en `INFORME-TECNICO-FINAL.md` (sección 3). Reparto de los 50 casos manuales:

| Tipo de prueba (guía) | Casos | Tipos literales de la matriz |
|---|---:|---|
| Funcional | 19 | Funcional (11), Funcional negativo (3), Funcional / Integración (1), Funcional no implementado (4) |
| Validación de datos | 10 | Validación de datos (10) |
| Seguridad | 15 | Seguridad (14), Seguridad del cliente (1) |
| Integración | 4 | Integración (4) |
| Usabilidad | 1 | Usabilidad (1) |
| Resiliencia | 1 | Resiliencia (1) |
| **Total** | **50** | |

Además se planificaron pruebas **automatizadas** (PHPUnit: caracterización, unitarias y requisitos de seguridad) y **análisis estático** (SonarQube); su planificación
y resultados están en `automatizadas/README.md` y `sonarqube/`. No se planificaron pruebas de rendimiento ni de carga: el prototipo no tiene
persistencia ni lógica de negocio sobre la que medirlas.

## 3. Matriz de planificación

La columna final es la **referencia a la ejecución**: el estado vigente y la evidencia (JSON de la petición o captura). Las precondiciones, los datos de
entrada ficticios y el resultado real de cada caso están en las matrices de origen. Esta tabla se generó por programa a partir de ellas, sin transcripción manual.

### Casos de API (38)

| ID | Funcionalidad | Tipo de prueba | Procedimiento | Resultado esperado | Referencia a la ejecución |
|---|---|---|---|---|---|
| F-01 | Endpoint de salud | Funcional | GET /api/health con Accept: application/json | 200 y cuerpo {"status":"ok"} | **Aprobado**. `api/TC-F-01.json` |
| F-02 | Inicio de sesión de estudiante | Funcional | POST /api/auth/login | 200, rol student, token presente y sin campo password en la respuesta | **Aprobado**. `api/TC-F-02.json` |
| F-03 | Inicio de sesión de padre/tutor | Funcional | POST /api/auth/login | 200, rol parent, token presente | **Aprobado**. `api/TC-F-03.json` |
| F-04 | Inicio de sesión de personal (docente/auxiliar) | Funcional | POST /api/auth/login | 200, rol staff, token presente | **Aprobado**. `api/TC-F-04.json` |
| F-05 | Envío de justificación (contrato de la API) | Funcional | POST /api/asistencia/justificacion con Authorization Bearer | 200 con mensaje de envío y eco de los datos validados (la persistencia se evalúa en B-04) | **Aprobado**. `api/TC-F-05.json` |
| F-06 | Aprobación manual de ingreso (contrato de la API) | Funcional | POST /api/asistencia/auxiliar/aprobar con Authorization Bearer | 200 con mensaje y eco de los datos validados (la persistencia se evalúa en B-04) | **Aprobado**. `api/TC-F-06.json` |
| V-01 | Validación de DNI corto | Validación de datos | POST /api/auth/login | 422 con error en el campo identifier | **Aprobado**. `api/TC-V-01.json` |
| V-02 | Validación de contraseña corta | Validación de datos | POST /api/auth/login | 422 con error en el campo password (mínimo 8) | **Aprobado**. `api/TC-V-02.json` |
| V-03 | Validación de perfil inexistente | Validación de datos | POST /api/auth/login | 422 con error en el campo role | **Aprobado**. `api/TC-V-03.json` |
| V-04 | Validación de formulario vacío | Validación de datos | POST /api/auth/login | 422 con errores en identifier, password y role | **Aprobado**. `api/TC-V-04.json` |
| V-05 | Validación de DNI con letras | Validación de datos | POST /api/auth/login | 422 con error en identifier | **Aprobado**. `api/TC-V-05.json` |
| V-06 | Validación de contraseña demasiado larga | Validación de datos | POST /api/auth/login | 422 con error en password (máximo 128) | **Aprobado**. `api/TC-V-06.json` |
| V-07 | Validación de motivo de justificación corto | Validación de datos | POST /api/asistencia/justificacion | 422 con error en reason (mínimo 10) | **Aprobado**. `api/TC-V-07.json` |
| V-08 | Validación de tipo de justificación inexistente | Validación de datos | POST /api/asistencia/justificacion | 422 con error en type | **Aprobado**. `api/TC-V-08.json` |
| V-09 | Validación de aprobación manual sin motivo | Validación de datos | POST /api/asistencia/auxiliar/aprobar | 422 con error en reason | **Aprobado**. `api/TC-V-09.json` |
| N-01 | Rechazo de contraseña incorrecta | Funcional (negativo) | POST /api/auth/login | 401 con mensaje genérico y sin token | **Aprobado**. `api/TC-N-01.json` |
| N-02 | Rechazo de credencial de otro perfil | Funcional (negativo) | POST /api/auth/login | 401 y sin token (no se puede entrar a un perfil con la credencial de otro) | **Aprobado**. `api/TC-N-02.json` |
| N-03 | No enumeración de usuarios en el login | Seguridad | POST /api/auth/login y comparar el cuerpo con el de N-01 | 401 con respuesta idéntica a la de contraseña incorrecta (no revela si el DNI existe) | **Aprobado**. `api/TC-N-03.json` |
| I-01 | Proxy de Vite hacia la API | Integración | GET http://localhost:5173/api/health | 200 {"status":"ok"} (Vite reenvía a Laravel) | **Aprobado**. `api/TC-I-01.json` |
| I-02 | Login a través del proxy (frontend a backend) | Integración | POST http://localhost:5173/api/auth/login | 200 con token y usuario, igual que contra Laravel directo | **Aprobado**. `api/TC-I-02.json` |
| I-03 | CORS: origen permitido | Integración | OPTIONS /api/auth/login (preflight) | 204 y Access-Control-Allow-Origin igual al origen | **Aprobado**. `api/TC-I-03.json` |
| I-04 | CORS: origen no autorizado | Seguridad | OPTIONS /api/auth/login (preflight) | Sin cabecera Access-Control-Allow-Origin (el navegador bloquea al origen ajeno) | **Aprobado**. `api/TC-I-04.json` |
| I-05 | Robustez: cliente de API sin cabecera Accept | Integración | POST /api/auth/login sin Accept (sin seguir redirecciones) | 422 con JSON de errores: una ruta de API no debe responder con redirección HTML | **Fallido**. `api/TC-I-05.json` |
| S-01 | Perfil autenticado sin credenciales | Seguridad | GET /api/auth/me | Sistema seguro: 401 Unauthorized | **Fallido**. `api/TC-S-01.json` |
| S-02 | Casos del auxiliar sin autenticación | Seguridad | GET /api/asistencia/auxiliar/casos | Sistema seguro: 401 Unauthorized | **Fallido**. `api/TC-S-02.json` |
| S-03 | Aprobación manual sin autenticación | Seguridad | POST /api/asistencia/auxiliar/aprobar | Sistema seguro: 401 Unauthorized | **Fallido**. `api/TC-S-03.json` |
| S-04 | Rechazo de token falso | Seguridad | GET /api/asistencia/estudiante | Sistema seguro: 401 Unauthorized | **Fallido**. `api/TC-S-04.json` |
| S-05 | Autorización por rol: estudiante en endpoint de personal | Seguridad | GET /api/asistencia/auxiliar/casos con token de estudiante | Sistema seguro: 403 Forbidden | **Fallido**. `api/TC-S-05.json` |
| S-06 | Invalidación del token al cerrar sesión | Seguridad | 1) POST /api/auth/login  2) POST /api/auth/logout con el token  3) GET /api/auth/me con el mismo token | Sistema seguro: tras el logout, /me responde 401 | **Fallido**. `api/TC-S-06.json` |
| S-07 | Resistencia a inyección SQL en el login | Seguridad | POST /api/auth/login (dos peticiones) | 401 o 422, sin token, sin error de base de datos ni 500 | **Aprobado**. `api/TC-S-07.json` |
| S-08 | Limitación de intentos de login (fuerza bruta) | Seguridad | 12 × POST /api/auth/login | Sistema seguro: algún intento recibe 429 Too Many Requests | **Fallido**. `api/TC-S-08.json` |
| S-09 | No divulgación de versión de tecnología | Seguridad | GET /api/health y revisar cabeceras de respuesta | Sin cabecera X-Powered-By | **Fallido**. `api/TC-S-09.json` |
| S-10 | Cabeceras de seguridad HTTP básicas | Seguridad | GET /api/health y revisar cabeceras de respuesta | Presente X-Content-Type-Options: nosniff | **Fallido**. `api/TC-S-10.json` |
| S-11 | Los errores no filtran detalles internos | Seguridad | GET /api/ruta-que-no-existe-geoeduca | 404 con mensaje genérico, sin traza, rutas de archivo ni nombres de clases | **Fallido**. `api/TC-S-11.json` |
| B-01 | Emisión y validación de QR dinámico | Funcional (no implementado) | Buscar rutas de emisión/validación de QR en route:list | Existen endpoints para emitir un QR con expiración y validarlo una sola vez | **Bloqueado**. `api/TC-B-01.json` |
| B-02 | Validación de geolocalización y geocerca | Funcional (no implementado) | Buscar rutas de geolocalización en route:list | Existe un endpoint que valide coordenadas contra una geocerca en el servidor | **Bloqueado**. `api/TC-B-02.json` |
| B-03 | Notificaciones a apoderados | Funcional (no implementado) | Buscar rutas de notificaciones en route:list | Existen endpoints y mecanismo de envío con registro de intentos | **Bloqueado**. `api/TC-B-03.json` |
| B-04 | Persistencia y consulta de justificaciones | Funcional (no implementado) | 1) POST /api/asistencia/justificacion  2) GET /api/asistencia/padre | La justificación enviada aparece en la consulta del padre | **Bloqueado**. `api/TC-B-04.json` |

### Casos de interfaz (12)

| ID | Funcionalidad | Tipo de prueba | Procedimiento | Resultado esperado | Referencia a la ejecución |
|---|---|---|---|---|---|
| UI-01 | Inicio de sesión de estudiante en la interfaz | Funcional / Integración | Abrir `/login`, escribir datos, pulsar «Ingresar» | Redirige a `/estudiante`, guarda usuario con rol student y token, y la petición llega a `/api/auth/login` | **Aprobado**. `ui/UI-01_login-estudiante-panel-y-fecha-fija.png`, `ui/mediciones-dom.json` |
| UI-02 | Validación en vivo del formulario | Validación de datos | Escribir ambos valores sin enviar | Mensajes de error bajo cada campo y botón «Ingresar» deshabilitado | **Aprobado**. `ui/UI-02_validacion-en-vivo-dni-y-contrasena.png` |
| UI-03 | Redirección de ruta protegida sin sesión | Seguridad (cliente) | Abrir `http://localhost:5173/auxiliar` | Redirige a `/login` | **Aprobado**. `ui/UI-03_ruta-protegida-sin-sesion-redirige-a-login.png` |
| UI-04 | Rechazo de credencial incorrecta | Funcional (negativo) | Pulsar «Ingresar» | Mensaje de error visible, sin sesión ni token guardados | **Aprobado**. `ui/UI-04_credencial-incorrecta-mensaje-de-error.png`, `ui/mediciones-dom.json` |
| UI-05 | Cierre de sesión | Funcional | Pulsar «Cerrar sesión» | Vuelve a `/login` y se borra la sesión local | **Aprobado**. `ui/mediciones-dom.json` |
| UI-06 | Autorización por rol (manipulación del navegador) | Seguridad | Editar el valor y abrir `/auxiliar` | Sistema seguro: acceso denegado por el servidor, sin mostrar datos de otros alumnos | **Fallido**. `ui/UI-06_rol-manipulado-abre-panel-auxiliar.png`, `ui/mediciones-dom.json` |
| UI-07 | Fecha y hora del panel | Funcional | Comparar la fecha mostrada con la del navegador | La fecha mostrada coincide con la fecha real | **Fallido**. `ui/UI-01_login-estudiante-panel-y-fecha-fija.png`, `ui/mediciones-dom.json` |
| UI-08 | QR dinámico: el QR cambia al renovar | Funcional | Calcular la huella del SVG del QR, pulsar «Generar Nuevo Código QR», recalcular | El contenido del QR cambia al renovarse | **Fallido**. `ui/mediciones-dom.json` |
| UI-09 | Inicio de sesión de padre/tutor en la interfaz | Funcional | Abrir `/login`, elegir «Padre / Tutor», escribir los datos y pulsar «Ingresar» | Redirige a `/padre` y guarda usuario con rol parent y token | **Aprobado**. `ui/UI-09_login-padre-redirige-a-panel-padre.png`, `ui/mediciones-dom-ui09-ui12.json` |
| UI-10 | Inicio de sesión de docente/auxiliar en la interfaz | Funcional | Abrir `/login`, elegir «Docente», escribir los datos y pulsar «Ingresar» | Redirige a `/auxiliar` y guarda usuario con rol staff y token | **Aprobado**. `ui/UI-10_login-docente-redirige-a-panel-auxiliar.png`, `ui/mediciones-dom-ui09-ui12.json` |
| UI-11 | Diseño adaptable a móvil | Usabilidad | Emular el viewport, medir el desborde de la página y de cada elemento, y revisar la captura | Menú adaptado y sin desbordes ni contenido cortado | **Fallido**. `ui/UI-11_vista-movil-375px-panel-auxiliar.png`, `ui/UI-11_contenido-recortado-en-375px.png`, `ui/mediciones-dom-ui09-ui12.json` |
| UI-12 | Mensaje si el backend no responde | Resiliencia | Detener Laravel, abrir `/login`, enviar el formulario | Mensaje claro de que no hay conexión con el servidor | **Fallido**. `ui/UI-12_backend-caido-mensaje-tecnico-500.png`, `ui/mediciones-dom-ui09-ui12.json` |


## 4. Resumen de la ejecución

| Planificados | Ejecutados | Aprobados | Fallidos | Bloqueados | No ejecutados | Aprobación sobre ejecutados |
|---:|---:|---:|---:|---:|---:|---:|
| 50 | 46 | 30 | 16 | 4 | 0 | **65,2 %** (30 / 46) |

Los 4 casos bloqueados (B-01 a B-04) se informan aparte y no entran en el denominador, como indica la guía. Detalle y cálculos en `README.md` y `INFORME-TECNICO-FINAL.md` (sección 4).
