'use client';

import { createContext, useCallback, useContext, useState } from 'react';

const ModalContext = createContext(null);

export function ModalProvider({ children }) {
  const [modal, setModal] = useState(null); // { content, onOk }

  const showModal = useCallback((content, onOk) => {
    setModal({ content, onOk });
  }, []);
  const closeModal = useCallback(() => setModal(null), []);

  return (
    <ModalContext.Provider value={{ showModal, closeModal }}>
      {children}
      {modal && (
        <div
          className="fixed inset-0 bg-black/50 z-[2000] flex items-center justify-center"
          onClick={(e) => {
            if (e.target === e.currentTarget) closeModal();
          }}
        >
          <div className="bg-white rounded-2xl p-8 max-w-[420px] w-[90%] shadow-[0_20px_60px_rgba(0,0,0,0.3)] text-center">
            {modal.content}
            <div className="flex gap-2.5 justify-center mt-5">
              <button onClick={closeModal} className="btn btn-outline">
                Cancelar
              </button>
              <button
                onClick={() => {
                  closeModal();
                  modal.onOk && modal.onOk();
                }}
                className="btn btn-primary"
              >
                Confirmar
              </button>
            </div>
          </div>
        </div>
      )}
    </ModalContext.Provider>
  );
}

export function useModal() {
  const ctx = useContext(ModalContext);
  if (!ctx) throw new Error('useModal debe usarse dentro de ModalProvider');
  return ctx;
}
