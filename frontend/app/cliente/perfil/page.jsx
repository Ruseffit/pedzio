'use client';

import { useState, useEffect, useCallback, useRef } from 'react';
import { api } from '@/lib/api';
import { useToast } from '@/components/ToastContext';
import Logo from '@/components/Logo';
import { useNotifications } from '@/hooks/useNotifications';

export default function PerfilPage() {
  const { toast } = useToast();
  const fileInputRef = useRef(null);

  const [usuario, setUsuario] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [subiendoFoto, setSubiendoFoto] = useState(false);
  const [fotoError, setFotoError] = useState('');

  const cargarUsuario = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const { user } = await api.me();
      setUsuario(user);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { cargarUsuario(); }, [cargarUsuario]);

  async function manejarArchivo(e) {
    const archivo = e.target.files?.[0];
    if (!archivo) return;

    const tiposPermitidos = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!tiposPermitidos.includes(archivo.type)) {
      setFotoError('Solo se aceptan imágenes JPEG, PNG, WEBP o GIF.');
      return;
    }
    if (archivo.size > 2 * 1024 * 1024) {
      setFotoError('La imagen no puede superar 2 MB.');
      return;
    }

    setFotoError('');
    setSubiendoFoto(true);
    try {
      const data = await api.subirFotoPerfil(archivo);
      setUsuario((u) => ({ ...u, foto_url: data.foto_url }));
      toast('Foto de perfil actualizada', '✅');
    } catch (err) {
      setFotoError(err.message);
    } finally {
      setSubiendoFoto(false);
      if (fileInputRef.current) fileInputRef.current.value = '';
    }
  }

  return (
    <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
      {/* Info del perfil */}
      <div className="card p-5">
        <div className="text-[15px] font-bold text-dark mb-4">Mi perfil</div>

        {loading && <PerfilSkeleton />}

        {!loading && error && (
          <div className="text-center py-6">
            <p className="text-red font-medium mb-1">No se pudo cargar tu perfil</p>
            <p className="text-gray text-sm mb-4">{error}</p>
            <button onClick={cargarUsuario} className="btn btn-primary btn-sm">
              Reintentar
            </button>
          </div>
        )}

        {!loading && !error && usuario && (
          <>
            <div className="flex flex-col items-center mb-5">
              <div className="w-[72px] h-[72px] rounded-full bg-orange flex items-center justify-center text-3xl text-white font-bold mb-2.5 overflow-hidden">
                {usuario.foto_url ? (
                  <img src={usuario.foto_url} alt={usuario.nombre} className="w-full h-full object-cover" />
                ) : (
                  usuario.nombre?.charAt(0)?.toUpperCase() || '?'
                )}
              </div>
              <button
                type="button"
                onClick={() => fileInputRef.current?.click()}
                disabled={subiendoFoto}
                className="btn btn-outline btn-sm disabled:opacity-60 disabled:cursor-not-allowed"
              >
                {subiendoFoto ? 'Subiendo…' : 'Cambiar foto'}
              </button>
              <input
                ref={fileInputRef}
                type="file"
                accept="image/jpeg,image/png,image/webp,image/gif"
                className="hidden"
                onChange={manejarArchivo}
              />
              {fotoError && <p className="text-red text-[11px] mt-2">{fotoError}</p>}
            </div>

            <dl className="space-y-3">
              <Campo etiqueta="Nombre completo" valor={usuario.nombre} />
              <Campo
                etiqueta="Rol"
                valor={
                  <span className="inline-block bg-orange-light text-orange text-xs font-medium px-2 py-1 rounded capitalize">
                    {usuario.rol}
                  </span>
                }
              />
            </dl>
          </>
        )}
      </div>

      {/* Sobre Pedzio */}
      <div className="card p-5">
        <div className="bg-gradient-to-br from-[#1A1A2E] to-[#2D2D4A] rounded-[10px] p-5 text-center">
          <Logo dark className="justify-center mb-2.5" />
          <div className="text-[13px] text-white font-semibold mb-1">Más que una app de delivery,</div>
          <div className="text-[13px] text-white font-semibold mb-3">somos una comunidad de emprendedores.</div>
          <div className="text-3xl mb-2">🛵</div>
          <div className="text-[11px] text-[#9CA3AF]">Descarga la app y pide desde donde estés</div>
        </div>
      </div>

      {/* Notificaciones push */}
      <div className="card p-5">
        <NotificacionesCard />
      </div>
    </div>
  );
}

function NotificacionesCard() {
  const { toast } = useToast();
  const { soportado, permiso, suscrito, cargando, error, activar, desactivar } = useNotifications();

  async function manejarClick() {
    const ok = suscrito ? await desactivar() : await activar();
    if (ok) {
      toast(suscrito ? 'Notificaciones desactivadas' : 'Notificaciones activadas', suscrito ? '🔕' : '🔔');
    }
  }

  if (!soportado) {
    return (
      <>
        <div className="text-[15px] font-bold text-dark mb-2">Notificaciones</div>
        <p className="text-gray text-sm">Tu navegador no soporta notificaciones push.</p>
      </>
    );
  }

  return (
    <>
      <div className="text-[15px] font-bold text-dark mb-2">Notificaciones</div>
      <p className="text-gray text-sm mb-4">
        Recibe un aviso al instante cuando el estado de tu pedido cambie (confirmado, en camino, entregado…).
      </p>

      <button
        type="button"
        onClick={manejarClick}
        disabled={cargando || permiso === 'denied'}
        className={`btn btn-sm disabled:opacity-60 disabled:cursor-not-allowed ${
          suscrito ? 'btn-outline' : 'btn-primary'
        }`}
      >
        {cargando ? 'Procesando…' : suscrito ? 'Desactivar notificaciones' : 'Activar notificaciones'}
      </button>

      {permiso === 'denied' && (
        <p className="text-red text-[11px] mt-2">
          Bloqueaste las notificaciones en el navegador. Actívalas desde la configuración del sitio para poder usarlas.
        </p>
      )}
      {error && <p className="text-red text-[11px] mt-2">{error}</p>}
    </>
  );
}

function Campo({ etiqueta, valor }) {
  return (
    <div className="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-4">
      <dt className="text-gray text-sm w-full sm:w-32 shrink-0">{etiqueta}</dt>
      <dd className="text-dark text-sm font-medium">{valor}</dd>
    </div>
  );
}

function PerfilSkeleton() {
  return (
    <div className="animate-pulse">
      <div className="flex flex-col items-center mb-5">
        <div className="w-[72px] h-[72px] rounded-full bg-gray-light mb-2.5" />
        <div className="h-7 w-24 bg-gray-light rounded" />
      </div>
      <div className="space-y-3">
        <div className="h-4 bg-gray-light rounded w-2/3" />
        <div className="h-4 bg-gray-light rounded w-1/3" />
      </div>
    </div>
  );
}
