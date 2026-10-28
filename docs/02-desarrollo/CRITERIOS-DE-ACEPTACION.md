# Criterios de aceptación

Un módulo se da por hecho cuando se puede **demostrar** con pruebas o con un flujo reproducible. Una pantalla no basta.

## F1 – Persistencia, autenticación y autorización

- Ninguna ruta, salvo `health` y `login`, responde sin token válido (`401`).
- Un rol incorrecto recibe `403`. Un tutor solo ve a sus hijos.
- Las contraseñas están con hash. No hay texto plano en la base ni en archivos versionados.
- Un token revocado o expirado deja de funcionar. `logout` lo revoca de verdad: `/api/auth/me` pasa a `401`.
- Más de N intentos fallidos de login por DNI/IP devuelven `429`.
- Un DNI enviado como arreglo u objeto JSON devuelve `422`, no `500`.
- No quedan contraseñas de demostración en el código. Los usuarios de prueba salen de un seeder de desarrollo.
- Cabecera `X-Content-Type-Options: nosniff` presente. `APP_DEBUG=false` en la plantilla.
- Las pruebas de `RequisitosSeguridad` correspondientes (S-01 a S-06, S-08, AUTO-01) **pasan**.
- El JSON de estado deja de usarse para sesiones y contraseñas.

## M0 – Integración (esta entrega, cuando se publique)

- El PR parte de `800e5e2` y no pierde archivos de producción de Diego (`git diff` vacío en `backend/app`, `routes`, `config`, `bootstrap`, `frontend/src`).
- `php vendor/bin/phpunit` corre en un clon nuevo.
- Caracterización aprueba. Seguridad falla solo en defectos abiertos, documentados.
- La guía pública no contiene secretos ni el DOCX del profesor.

## M3 a M8 (después de F1)

Resumen; el detalle de cada uno está en `PLAN-DE-IMPLEMENTACION.md`:

| Módulo | Se acepta cuando |
|---|---|
| M3 | Un administrador crea estudiante y tutor, los vincula, y el tutor ve solo a su hijo |
| M4 | Entrada puntual/tardanza/falta según horario real; sin duplicar el mismo día; la aprobación manual aparece en el historial |
| M5 | El token escaneado crea el registro; no sirve dos veces ni caducado; uno ajeno o alterado se rechaza |
| M6 | Dentro del perímetro se acepta; fuera se rechaza o crea caso; la coordenada solo la ven auxiliar y tutor |
| M7 | Al registrar el ingreso, el tutor recibe aviso persistente y un correo; un fallo de envío no rompe el registro |
| M8 | Totales del reporte coinciden con la base; un estudiante no ve reportes ajenos |
| M9 | Sin recorte a 375 px; mensaje comprensible si el backend cae; etiquetas asociadas a controles |
| M10 | Un PR con prueba fallida no se fusiona; cobertura medida; cero vulnerabilidades críticas |
