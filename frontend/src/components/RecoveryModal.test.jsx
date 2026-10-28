import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import RecoveryModal from './RecoveryModal';

vi.mock('../services/authService', () => ({
  requestRecovery: vi.fn(),
  confirmRecovery: vi.fn(),
}));

describe('aviso de recuperación', () => {
  it('no presenta un envío de código ni un formulario operativo', () => {
    render(<RecoveryModal open onClose={() => {}} />);

    expect(screen.getByText(/no envía códigos por correo ni SMS/i)).toBeInTheDocument();
    expect(screen.getByText(/no indica si una cuenta existe/i)).toBeInTheDocument();
    expect(screen.queryByLabelText(/código/i)).not.toBeInTheDocument();
    expect(screen.queryByLabelText(/nueva contraseña/i)).not.toBeInTheDocument();
  });
});
