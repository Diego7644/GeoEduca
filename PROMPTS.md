# Prompts por rama

Este archivo documenta el pedido que corresponde a cada rama del repositorio.

## Rama `principal`

Es la versión inicial. No agrega paneles dinámicos ni reportes operativos.

Prompt de esa rama:

```text
Crea la estructura inicial de GEOEDUCA, una plataforma web de control de asistencia escolar.
Divídela en un backend con Laravel y un frontend con React y Vite.
Incluye inicio de sesión por DNI, contraseña y perfil, y redirige a un panel según el rol:
estudiante, padre o tutor, y auxiliar.
Muestra en cada panel la interfaz de asistencia, pero los datos pueden quedar fijos
y los módulos de reportes y configuración pueden quedar pendientes.
```

## Rama `mejoras-interfaces-dinamicas`

Esta rama parte de `principal` y concentra todas las mejoras. Si aparece dos veces en GitHub, en "Tus sucursales" y en "Sucursales activas", es la misma rama.

Prompt general de la rama:

```text
Actualmente GEOEDUCA tiene el inicio de sesión, pero los paneles siguen con datos fijos.
Quiero interfaces dinámicas según el rol de la sesión y que agregues las funcionalidades
que cada pantalla ya muestra. No cambies la rama principal: deja estas mejoras en
mejoras-interfaces-dinamicas.
El estudiante, el padre o tutor y el auxiliar deben cargar su propia información.
Reportes y Configuración del colegio deben dejar de estar desactivados.
```

Prompt del panel de estudiante:

```text
En el panel del estudiante, reemplaza los datos fijos por información de su sesión.
El código QR debe renovarse cada 60 segundos y cambiar al generar uno nuevo.
Compara el GPS del dispositivo con el perímetro del colegio y muestra si está dentro o fuera.
El horario debe depender del día y la hora: marca la clase en curso, la próxima y las que ya terminaron.
Calcula puntualidad, asistencias, tardanzas y la racha con el registro real.
Agrega el horario semanal y la ayuda para usar el carnet RFID si no hay batería o señal.
```

Prompt del panel de padre o tutor:

```text
En el portal del padre o tutor, al cambiar de hijo deben cambiar la asistencia, las alertas y el resumen.
Los filtros por fecha y estado, y la paginación, deben recorrer el historial de ese hijo.
Descargar reporte debe generar un CSV que se abra en Excel y una vista para guardar PDF.
La solicitud de justificación o permiso debe guardarse y verse en las alertas.
Permite vincular otro estudiante con su DNI cuando esté a cargo de ese tutor.
```

Prompt del panel de auxiliar:

```text
En el monitor del auxiliar, el buscador debe encontrar estudiantes por DNI, código, apellido o aula.
Aprobar un caso de la cola, aprobar los casos sin riesgo y el registro forzado por DNI
deben anotar el ingreso en la bitácora y actualizar los contadores.
Exportar acta debe descargar el historial de aprobaciones.
Si el DNI o el código no existe, informa que no se encontró al estudiante.
```

Prompt de reportes y configuración:

```text
Activa Reportes y estadísticas y Configuración del colegio en el menú.
Reportes debe mostrar un resumen distinto para estudiante, padre y auxiliar, con opción de exportar.
Configuración debe guardar el nombre del colegio, la sede, el límite de ingreso, la salida,
la tolerancia, el perímetro GPS y la meta de promoción.
Solo el auxiliar puede editar esos valores. Estudiante y padre pueden consultarlos,
y los paneles deben usar esos parámetros al calcular puntualidad y jornada.
```
