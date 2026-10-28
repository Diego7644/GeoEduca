import Icon from './Icon';

export default function PageState({ loading, error, onRetry, children }) {
  if (loading) {
    return (
      <div className="flex flex-col items-center justify-center py-24 text-on-surface-variant">
        <Icon name="progress_activity" className="text-[32px] animate-spin text-primary" />
        <p className="mt-3 font-body-md">Cargando información del portal...</p>
      </div>
    );
  }

  if (error) {
    return (
      <div className="bg-surface-container-lowest rounded-xl p-8 text-center shadow-sm max-w-lg mx-auto">
        <Icon name="cloud_off" className="text-error text-[32px]" />
        <p className="mt-2 font-title-lg text-on-surface">{error}</p>
        {onRetry && (
          <button
            type="button"
            onClick={onRetry}
            className="mt-4 px-4 py-2 rounded-lg bg-primary text-on-primary font-label-md"
          >
            Reintentar
          </button>
        )}
      </div>
    );
  }

  return children;
}
