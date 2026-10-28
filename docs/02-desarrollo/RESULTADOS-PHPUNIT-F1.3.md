# PHPUnit en F1.3

Fecha: 2026-10-28. Comando: `php vendor/bin/phpunit` en `backend/`. PHPUnit 11.5.57, PHP 8.2.12. **173 pruebas, 618 aserciones, 0 fallos.**

No se modificaron las expectativas de `RequisitosSeguridad` para ocultar defectos. Se añadieron casos de acceso por carnet, recuperación e IDOR.

## Totales

| Suite | Resultado |
|---|---|
| Caracterizacion | OK |
| Persistencia | OK (incluye columna `attempts` y recuperación fuera del JSON) |
| Autenticacion | OK |
| RequisitosSeguridad | OK (incluye F1.3) |
| **Total** | **173 OK** (148 en F1.2 + 25 de F1.3) |

## Cobertura de F1.3

| Caso | Resultado |
|---|---|
| Acceso por DNI sin autenticación | **401**, sin token |
| Acceso con token inválido | **401** |
| Acceso a recursos de otros usuarios | **403** / 422 de propiedad |
| Recuperación con código correcto | **200**, hash nuevo, tokens revocados, sin reutilización |
| Código incorrecto | **422** genérico |
| Código expirado | **422** genérico |
| Código reutilizado | **422** genérico |
| Límite de intentos del código | **422** tras 5 fallos |
| Cuenta inexistente | mismo **200** que una cuenta real, sin código |
| Validaciones de entrada | **422** (DNI arreglo, rol inválido, código corto) |
| Ausencia de secretos | sin `dev_code` / `demo_code` / `code` en testing y production |
| Compatibilidad frontend | login sin cambios; carnet no persiste sesión; modal de recuperación |

Pendiente de producto (no es un fallo de estas pruebas): asistencia e historial siguen en JSON; no hay canal real de entrega del código.
