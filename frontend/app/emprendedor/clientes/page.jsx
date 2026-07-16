'use client';

import { useState, useEffect, useCallback } from 'react';
import { api } from '@/lib/api';

export default function ClientesPage() {
  const [clientes, setClientes] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const cargarClientes = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const data = await api.getClientes();
      setClientes(data.clientes || []);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    cargarClientes();
  }, [cargarClientes]);

  return (
    <div className="p-8">
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-dark">Clientes</h1>
        {!loading && !error && (
          <button
            onClick={cargarClientes}
            className="text-sm text-gray border border-gray-border rounded px-3 py-1.5 hover:bg-gray-light transition"
          >
            Actualizar
          </button>
        )}
      </div>

      {loading && <ClientesSkeleton />}

      {!loading && error && (
        <div className="bg-white border border-red/20 rounded-DEFAULT shadow-card p-6 text-center">
          <p className="text-red font-medium mb-1">No se pudieron cargar tus clientes</p>
          <p className="text-gray text-sm mb-4">{error}</p>
          <button
            onClick={cargarClientes}
            className="bg-orange text-white text-sm font-medium rounded-DEFAULT px-4 py-2 hover:opacity-90 transition"
          >
            Reintentar
          </button>
        </div>
      )}

      {!loading && !error && clientes.length === 0 && (
        <div className="bg-white border border-gray-border rounded-DEFAULT shadow-card p-10 text-center">
          <p className="text-dark font-medium mb-1">Aún no tienes clientes</p>
          <p className="text-gray text-sm">
            Cuando alguien te haga un pedido, aparecerá aquí como cliente.
          </p>
        </div>
      )}

      {!loading && !error && clientes.length > 0 && (
        <>
          {/* Tabla en pantallas medianas y grandes */}
          <div className="hidden md:block bg-white rounded-DEFAULT shadow-card overflow-hidden">
            <table className="w-full text-sm">
              <thead>
                <tr className="bg-gray-light text-left text-gray text-xs uppercase tracking-wide">
                  <th className="px-4 py-3">Cliente</th>
                  <th className="px-4 py-3">Email</th>
                  <th className="px-4 py-3">Teléfono</th>
                  <th className="px-4 py-3 text-right">Pedidos realizados</th>
                </tr>
              </thead>
              <tbody>
                {clientes.map((cliente) => (
                  <tr key={cliente.id} className="border-t border-gray-border">
                    <td className="px-4 py-3 font-medium text-dark">{cliente.nombre}</td>
                    <td className="px-4 py-3 text-gray">{cliente.email}</td>
                    <td className="px-4 py-3 text-gray">{cliente.telefono || '—'}</td>
                    <td className="px-4 py-3 text-right">
                      <span className="inline-block bg-orange-light text-orange font-semibold px-2 py-1 rounded text-xs">
                        {cliente.total_pedidos}
                      </span>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          {/* Cards en móvil */}
          <div className="md:hidden space-y-3">
            {clientes.map((cliente) => (
              <div key={cliente.id} className="bg-white rounded-DEFAULT shadow-card p-4">
                <div className="flex items-center justify-between mb-1">
                  <span className="font-semibold text-dark">{cliente.nombre}</span>
                  <span className="inline-block bg-orange-light text-orange font-semibold px-2 py-1 rounded text-xs">
                    {cliente.total_pedidos} pedido{cliente.total_pedidos === 1 ? '' : 's'}
                  </span>
                </div>
                <p className="text-gray text-sm">{cliente.email}</p>
                <p className="text-gray text-sm">{cliente.telefono || '—'}</p>
              </div>
            ))}
          </div>
        </>
      )}
    </div>
  );
}

function ClientesSkeleton() {
  return (
    <div className="bg-white rounded-DEFAULT shadow-card p-4 space-y-3 animate-pulse">
      {Array.from({ length: 5 }).map((_, i) => (
        <div key={i} className="h-10 bg-gray-light rounded w-full" />
      ))}
    </div>
  );
}
