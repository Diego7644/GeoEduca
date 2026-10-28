import { useCallback, useState } from 'react';

/**
 * Validación en tiempo real: el mensaje aparece al escribir (dirty)
 * y también al perder el foco (touched), p. ej. si el campo queda vacío.
 */
export default function useValidatedField(validate, initial = '') {
  const [value, setValue] = useState(initial);
  const [touched, setTouched] = useState(false);
  const [dirty, setDirty] = useState(false);
  const [error, setError] = useState('');

  const show = dirty || touched;

  const syncError = useCallback(
    (nextValue, visible) => {
      const message = validate(nextValue);
      if (visible) setError(message);
      return message;
    },
    [validate]
  );

  const onChange = (event) => {
    const next = event.target.value;
    setValue(next);
    setDirty(true);
    syncError(next, true);
  };

  const onBlur = () => {
    setTouched(true);
    syncError(value, true);
  };

  return {
    value,
    setValue,
    error,
    setError,
    touched,
    dirty,
    show,
    onChange,
    onBlur,
    isValid: !validate(value),
  };
}
