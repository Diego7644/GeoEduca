/** Mensaje de validación en rojo, bajo el input. */
export default function FieldError({ id, message }) {
  if (!message) return null;

  return (
    <p id={id} role="alert" className="text-error font-body-sm text-body-sm mt-1.5">
      {message}
    </p>
  );
}
