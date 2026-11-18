# Cámara del iPhone y HTTPS privado

GeoEduca sigue siendo la I.E. Mariscal Castilla, El Tambo, Huancayo. Esta guía cubre el escaneo QR entre la laptop y un iPhone.

## Por qué Safari decía «No se pudo abrir la cámara»

El iPhone abría `http://192.168.1.161:5173`. Esa dirección es HTTP en una IP de la red local. Safari solo considera contexto seguro a HTTPS y a `localhost` del propio teléfono. En ese estado `window.isSecureContext` es falso y `navigator.mediaDevices` no existe, así que `getUserMedia` no puede ejecutarse.

`html5-qrcode` 2.3.8 responde con el texto `navigator.mediaDevices not supported`. Es un string, no un `Error`, y el componente leía solo `.message`. Por eso la pantalla mostraba el texto genérico y no la causa.

No es una cámara ocupada ni un permiso denegado. Esos casos ahora tienen su propio mensaje. El ingreso manual del token se conserva.

## Qué quedó preparado

- La cámara trasera se pide primero (`facingMode: environment`). Si el dispositivo no la ofrece, se prueba la frontal.
- Al cerrar el escáner, o al salir del panel, se detienen las pistas de video.
- El video lleva `playsinline`, necesario para que iOS no lo abra a pantalla completa.
- El frontend llama a `/api`. Si alguna variable apuntara a `127.0.0.1` o `localhost`, un teléfono deja de usarla y sigue el proxy de Vite hacia Laravel en `127.0.0.1:8000`.
- Vite acepta el sufijo `.ts.net` y sigue aceptando las IPv4 de esta máquina. No se abre el servidor a cualquier nombre.
- Los scripts usan `tailscale serve`, que permanece dentro de la tailnet. No usan `tailscale funnel` y no desactivan la validación TLS.

Tailscale no estaba instalado en esta laptop (`tailscale.exe` no existe). No se instaló: hace falta autorización.

## Iniciar el sistema en Windows

En PowerShell, desde el repositorio:

```powershell
cd D:\geoeduca-integracion
.\scripts\desarrollo\iniciar.ps1
```

Eso levanta el contenedor `geoeduca-postgres` si estaba detenido, Laravel en `127.0.0.1:8000` y Vite en el puerto `5173`. La laptop entra por:

```text
http://127.0.0.1:5173
```

La primera vez el script avisará que Tailscale no está instalado y no lo instalará.

## Activar la URL HTTPS

1. Autoriza la instalación y usa el instalador oficial: https://tailscale.com/download/windows
2. Inicia sesión en Tailscale en esta laptop.
3. En la consola de Tailscale activa MagicDNS y los certificados HTTPS. Sin eso, `tailscale serve` no puede entregar un certificado válido.
4. Instala la app Tailscale en el iPhone e inicia sesión con la misma cuenta. El teléfono y la laptop deben verse en la tailnet.
5. Con Laravel y Vite ya en marcha, publica solo el puerto de Vite:

```powershell
cd D:\geoeduca-integracion
.\scripts\desarrollo\publicar-https.ps1
```

6. Consulta la dirección:

```powershell
.\scripts\desarrollo\direccion-https.ps1
```

La salida tiene la forma `https://nombre-de-esta-laptop.tu-tailnet.ts.net`. Ábrela en Safari. No uses `http://192.168.1.161:5173` para escanear: esa ruta sigue sin cámara.

No ejecutes `tailscale funnel`. Funnel publicaría el puerto fuera de la tailnet.

## Primer escaneo real

Estas cuentas de demostración no se modificaron en este cambio.

1. En la laptop, entra como estudiante `10000001` en `http://127.0.0.1:5173` y deja visible el QR grande. Caduca en 60 segundos y se renueva solo.
2. En el iPhone, abre la URL `https://…ts.net` que imprimió el script. Entra como auxiliar `10000003`.
3. Pulsa **Abrir cámara**. Safari debe preguntar el permiso. Si lo bloqueó antes: Ajustes > Safari > Cámara > Preguntar, luego el icono AA de la barra y permite la cámara de este sitio.
4. Apunta la cámara trasera al QR de la laptop. El panel envía el token a `POST /api/asistencia/qr/validar`.
5. Laravel comprueba que el token existe, que no expiró, que no se usó y que quien escanea es auxiliar. PostgreSQL guarda la asistencia y el panel muestra el resultado en la bitácora.
6. Un segundo intento del mismo QR debe decir que ya fue utilizado. Otro código, o el mismo después de marcar hoy, no debe duplicar el ingreso del día.

Si el estudiante ya tiene asistencia de hoy, el primer escaneo responderá que el registro de hoy ya existe. Eso no borra datos: espera al día siguiente o usa un estudiante sin marcación.

## Lo que no se pudo probar desde esta máquina

No hay un iPhone conectado a este entorno, así que no se ejecutó el permiso real de Safari ni la lectura óptica. PHPUnit cubre el token usado, vencido y el rol. La prueba física pendiente es la de la lista anterior, con la URL HTTPS ya impresa por `direccion-https.ps1`.
