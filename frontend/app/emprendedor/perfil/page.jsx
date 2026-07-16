'use client';

import { useState, useEffect, useCallback } from 'react';
import { api } from '@/lib/api';

export default function PerfilPage() {
  const [perfil, setPerfil] = useState(null);
  const [emprendimiento, setEmprendimiento] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const cargarPerfil = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const data = await api.getPerfil();
      setPerfil(data.perfil || null);
      setEmprendimiento(data.emprendimiento || null);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    cargarPerfil();
  }, [cargarPerfil]);

  return (
    <div className="p-8 max-w-2xl">
      <h1 className="text-2xl font-bold text-dark mb-6">Mi Perfil</h1>

      {loading && <PerfilSkeleton />}

      {!loading && error && (
        <div className="bg-white border border-red/20 rounded-DEFAULT shadow-card p-6 text-center">
          <p className="text-red font-medium mb-1">No se pudo cargar tu perfil</p>
          <p className="text-gray text-sm mb-4">{error}</p>
          <button
            onClick={cargarPerfil}
            className="bg-orange text-white text-sm font-medium rounded-DEFAULT px-4 py-2 hover:opacity-90 transition"
          >
            Reintentar
          </button>
        </div>
      )}

      {!loading && !error && perfil && (
        <div className="space-y-4">
          <section className="bg-white rounded-DEFAULT shadow-card p-6">
            <h2 className="text-sm font-semibold text-gray uppercase tracking-wide mb-4">
              Datos personales
            </h2>
            <dl className="space-y-3">
              <Campo etiqueta="Nombre" valor={perfil.nombre} />
              <Campo etiqueta="Email" valor={perfil.email} />
              <Campo etiqueta="Teléfono" valor={perfil.telefono || '—'} />
              <Campo
                etiqueta="Rol"
                valor={
                  <span className="inline-block bg-orange-light text-orange text-xs font-medium px-2 py-1 rounded capitalize">
                    {perfil.rol}
                  </span>
                }
              />
            </dl>
          </section>

          <section className="bg-white rounded-DEFAULT shadow-card p-6">
            <h2 className="text-sm font-semibold text-gray uppercase tracking-wide mb-4">
              Mi negocio
            </h2>

            {emprendimiento ? (
              <dl className="space-y-3">
                <Campo etiqueta="Nombre del negocio" valor={emprendimiento.nombre_negocio} />
                <Campo etiqueta="Dirección" valor={emprendimiento.direccion || '—'} />
                <Campo etiqueta="Teléfono de contacto" valor={emprendimiento.telefono_contacto || '—'} />
                {emprendimiento.descripcion && (
                  <Campo etiqueta="Descripción" valor={emprendimiento.descripcion} />
                )}
              </dl>
            ) : (
              <p className="text-gray text-sm">
                Todavía no has registrado los datos de tu negocio.
              </p>
            )}
          </section>
        </div>
      )}
    </div>
  );
}

function Campo({ etiqueta, valor }) {
  return (
    <div className="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-4">
      <dt className="text-gray text-sm w-full sm:w-40 shrink-0">{etiqueta}</dt>
      <dd className="text-dark text-sm font-medium">{valor}</dd>
    </div>
  );
}

function PerfilSkeleton() {
  return (
    <div className="space-y-4 animate-pulse">
      {Array.from({ length: 2 }).map((_, i) => (
        <div key={i} className="bg-white rounded-DEFAULT shadow-card p-6 space-y-3">
          <div className="h-4 bg-gray-light rounded w-1/4 mb-2" />
          <div className="h-4 bg-gray-light rounded w-1/2" />
          <div className="h-4 bg-gray-light rounded w-2/3" />
          <div className="h-4 bg-gray-light rounded w-1/3" />
        </div>
      ))}
    </div>
  );
}
