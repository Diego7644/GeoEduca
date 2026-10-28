const COPY = {
  checking: 'Comprobando sesión…',
  offline: 'No se pudo comprobar la sesión. Revise la conexión e intente de nuevo.',
  error: 'No se pudo comprobar la sesión con el servidor.',
};

/** Pantalla de espera o de fallo al confirmar la sesión con /api/auth/me. */
export default function SessionStatus({ status, onRetry, onAbandon }) {
  return (
    <div className="min-h-screen flex items-center justify-center bg-surface p-6" data-testid="session-status">
      <div className="max-w-md text-center space-y-4">
        <p className="font-body-md text-body-md text-on-surface">{COPY[status] ?? COPY.checking}</p>
        {status !== 'checking' && (
          <div className="flex flex-col sm:flex-row gap-3 justify-center">
            {onRetry && (
              <button type="button" onClick={onRetry} className="h-11 px-4 rounded-lg bg-primary-container text-on-primary">
                Reintentar
              </button>
            )}
            {onAbandon && (
              <button type="button" onClick={onAbandon} className="h-11 px-4 rounded-lg bg-surface-container text-on-surface">
                Ingresar con otra cuenta
              </button>
            )}
          </div>
        )}
      </div>
    </div>
  );
}
