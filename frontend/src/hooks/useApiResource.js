import { useCallback, useEffect, useState } from 'react';

export default function useApiResource(loader) {
  const [data, setData] = useState(null);
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(true);

  const reload = useCallback(async () => {
    setError('');
    setLoading(true);
    try {
      setData(await loader());
    } catch (err) {
      setError(err.message || 'No se pudo cargar la información.');
    } finally {
      setLoading(false);
    }
  }, [loader]);

  useEffect(() => {
    reload();
  }, [reload]);

  return { data, setData, error, loading, reload };
}
