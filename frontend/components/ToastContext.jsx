'use client';

import { createContext, useCallback, useContext, useRef, useState } from 'react';

const ToastContext = createContext(null);

export function ToastProvider({ children }) {
  const [msg, setMsg] = useState(null);
  const timerRef = useRef(null);

  const toast = useCallback((text, icon = '✅') => {
    setMsg({ text, icon });
    clearTimeout(timerRef.current);
    timerRef.current = setTimeout(() => setMsg(null), 2800);
  }, []);

  return (
    <ToastContext.Provider value={{ toast }}>
      {children}
      <div
        className={`fixed bottom-7 right-7 z-[9999] bg-dark text-white px-5 py-3 rounded-[10px] text-[13px] font-semibold items-center gap-2 shadow-[0_4px_20px_rgba(0,0,0,0.25)] animate-slideIn ${
          msg ? 'flex' : 'hidden'
        }`}
      >
        {msg ? `${msg.icon} ${msg.text}` : ''}
      </div>
    </ToastContext.Provider>
  );
}

export function useToast() {
  const ctx = useContext(ToastContext);
  if (!ctx) throw new Error('useToast debe usarse dentro de ToastProvider');
  return ctx;
}
