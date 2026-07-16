'use client';

import { useState, useEffect } from 'react';

/**
 * Registra el Service Worker (una sola vez, para toda la app) y muestra
 * un banner discreto de "instalar app" cuando el navegador ofrece el
 * evento beforeinstallprompt (Chrome/Edge/Android; en iOS Safari no existe
 * ese evento y la instalación es manual vía "Compartir → Añadir a inicio").
 *
 * Se monta una sola vez en el layout raíz. No requiere props.
 */
export default function PWAInstall() {
  const [promptEvento, setPromptEvento] = useState(null);
  const [visible, setVisible] = useState(false);

  useEffect(() => {
    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.register('/sw.js').catch(() => {
        // Si el registro falla (ej. en http:// sin TLS en producción),
        // la app sigue funcionando normalmente, solo sin capacidades PWA.
      });
    }

    function alOfrecerInstalacion(evento) {
      evento.preventDefault();
      setPromptEvento(evento);

      const yaDescartado = sessionStorage.getItem('pedzio_pwa_descartado');
      if (!yaDescartado) setVisible(true);
    }

    window.addEventListener('beforeinstallprompt', alOfrecerInstalacion);
    return () => window.removeEventListener('beforeinstallprompt', alOfrecerInstalacion);
  }, []);

  async function instalar() {
    if (!promptEvento) return;
    promptEvento.prompt();
    await promptEvento.userChoice;
    setPromptEvento(null);
    setVisible(false);
  }

  function descartar() {
    setVisible(false);
    sessionStorage.setItem('pedzio_pwa_descartado', '1');
  }

  if (!visible) return null;

  return (
    <div className="fixed bottom-4 left-1/2 -translate-x-1/2 z-[9998] w-[92%] max-w-sm bg-dark text-white rounded-DEFAULT shadow-card-lg px-4 py-3 flex items-center gap-3 animate-slideIn">
      <span className="text-2xl flex-shrink-0">📲</span>
      <div className="flex-1 min-w-0">
        <p className="text-sm font-semibold">Instala Pedzio</p>
        <p className="text-xs text-gray-border">Acceso rápido desde tu pantalla de inicio.</p>
      </div>
      <button
        onClick={instalar}
        className="bg-orange text-white text-xs font-semibold rounded px-3 py-1.5 flex-shrink-0 hover:opacity-90"
      >
        Instalar
      </button>
      <button
        onClick={descartar}
        className="text-gray-border text-lg leading-none flex-shrink-0 px-1"
        aria-label="Cerrar"
      >
        ×
      </button>
    </div>
  );
}
