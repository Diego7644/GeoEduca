import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import QrCamera from './QrCamera';

const scanner = vi.hoisted(() => ({
  stop: vi.fn(() => Promise.resolve()),
  clear: vi.fn(),
  onScan: null,
}));

vi.mock('html5-qrcode', () => ({
  Html5Qrcode: class {
    start(_camera, _config, onScan) {
      scanner.onScan = onScan;
      return Promise.resolve();
    }

    stop() {
      return scanner.stop();
    }

    clear() {
      scanner.clear();
    }
  },
}));

describe('cámara QR', () => {
  it('abre la cámara trasera, entrega un código y la libera al cerrar', async () => {
    Object.defineProperty(window, 'isSecureContext', { value: true, configurable: true });
    Object.defineProperty(navigator, 'mediaDevices', {
      value: { getUserMedia: vi.fn() },
      configurable: true,
    });
    const onCode = vi.fn();
    const user = userEvent.setup();
    const view = render(<QrCamera onCode={onCode} />);

    await user.click(screen.getByRole('button', { name: 'Abrir cámara' }));
    expect(await screen.findByRole('button', { name: 'Cerrar cámara' })).toBeInTheDocument();

    scanner.onScan('abc123');
    await screen.findByRole('button', { name: 'Abrir cámara' });
    expect(onCode).toHaveBeenCalledWith('abc123');
    expect(scanner.stop).toHaveBeenCalled();

    view.unmount();
    expect(scanner.clear).toHaveBeenCalled();
  });
});
