# GeoEduca inclusivo

La preferencia se guarda en el navegador con la clave `geoeduca-a11y`. No se envía al servidor.

Temas: claro, oscuro y automático. El panel flotante también cambia contraste, tamaño de texto, espaciado, enlaces, animaciones, lectura fácil y lectura guiada. La voz usa `speechSynthesis` cuando el navegador lo ofrece y omite DNI de ocho dígitos y tokens de 32 caracteres hexadecimales.

## Verificado en esta entrega

- Persistencia y aplicación de atributos, con Vitest.
- Contraste 4.5:1 de los pares principales de texto claro, oscuro y alto contraste.
- Foco visible, enlace para saltar al contenido, diálogo del panel y del modal con Escape y ciclo de tabulador.
- Aviso en vivo de los toasts.

## Pendiente de una auditoría WCAG 2.2 AA

No se declara cumplimiento total. Falta revisión con lector de pantalla real, zoom al 200 % y al 400 % en cada rol, y una pasada con axe sobre todas las pantallas. Algunas tablas antiguas pueden no tener `scope` en cada encabezado. El texto de la fecha del encabezado sigue siendo fijo y no forma parte de esta entrega.
