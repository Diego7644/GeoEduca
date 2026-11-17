# Instalación de demostración

GeoEduca se instala con Docker, las migraciones y `db:seed`. No hace falta importar una base existente.

El repositorio público no incluye nombres ni DNI identificables. Los tres estudiantes de una instalación nueva son sintéticos y cubren 1.º A, 3.º B y 5.º C. La contraseña de todas las cuentas de esta plantilla es `prueba123`. La base Docker usa la clave local `geoeduca-demo`, distinta de cualquier `.env` personal.

## Windows

Hacen falta PHP 8.2 con `pdo_pgsql`, Composer, Node.js 18, npm y Docker Desktop.

```powershell
cd D:\geoeduca-integracion
.\scripts\instalar-demo.ps1
```

El script copia `backend/.env.demo` a `backend/.env` solo cuando ese archivo no existe. Si el puerto 5432 está ocupado, usa 5433. Después:

```powershell
cd backend
php artisan serve
```

```powershell
cd frontend
npm install
npm run dev
```

La laptop abre `http://127.0.0.1:5173`. El iPhone necesita la URL HTTPS de Tailscale, descrita en [`CAMARA-IOS.md`](CAMARA-IOS.md).

## Cuentas

| DNI | Perfil | Grado y sección |
|---|---|---|
| 10000001 | Estudiante | 4.º B |
| 10000002 | Padre / tutor | — |
| 10000003 | Auxiliar | — |
| 10000007 | Administrador | — |
| 10000021 | Estudiante | 1.º A |
| 10000022 | Estudiante | 3.º B |
| 10000023 | Estudiante | 5.º C |

La contraseña de las siete es `prueba123`.

Cada estudiante entra con el rol Estudiante, ve su grado y genera un QR distinto. El auxiliar `10000003` lo escanea. Un QR usado no vuelve a marcar. En Reportes, el campo «Grado o sección» acepta `1.º A`, `3.º B` o `5.º C`. En el directorio de administración se busca con el mismo texto.

Volver a ejecutar `db:seed` no cambia una ficha cuyo DNI ya pertenece a otro nombre y no borra asistencias.
