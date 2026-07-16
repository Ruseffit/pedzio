'use client';

import { useState, useEffect, useCallback } from 'react';
import { api } from '@/lib/api';

function formatearFecha(fechaISO) {
  if (!fechaISO) return '—';
  const fecha = new Date(`${fechaISO}T00:00:00`);
  if (Number.isNaN(fecha.getTime())) return fechaISO;
  return fecha.toLocaleDateString('es-PE', { day: '2-digit', month: 'short', year: 'numeric' });
}

export default function VentasPage() {
  const [ventas, setVentas] = useState([]);
  const [resumen, setResumen] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const cargarVentas = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const data = await api.getVentas();
      setVentas(data.ventas || []);
      setResumen(data.resumen || null);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    cargarVentas();
  }, [cargarVentas]);

  return (
    <div className="p-8">
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-dark">Ventas</h1>
        {!loading && !error && (
          <button
            onClick={cargarVentas}
            className="text-sm text-gray border border-gray-border rounded px-3 py-1.5 hover:bg-gray-light transition"
          >
            Actualizar
          </button>
        )}
      </div>

      {loading && <VentasSkeleton />}

      {!loading && error && (
        <div className="bg-white border border-red/20 rounded-DEFAULT shadow-card p-6 text-center">
          <p className="text-red font-medium mb-1">No se pudieron cargar tus ventas</p>
          <p className="text-gray text-sm mb-4">{error}</p>
          <button
            onClick={cargarVentas}
            className="bg-orange text-white text-sm font-medium rounded-DEFAULT px-4 py-2 hover:opacity-90 transition"
          >
            Reintentar
          </button>
        </div>
      )}

      {!loading && !error && (
        <>
          {resumen && (
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
              <div className="bg-white rounded-DEFAULT shadow-card p-4">
                <p className="text-gray text-xs uppercase tracking-wide mb-1">Ingresos</p>
                <p className="text-green text-2xl font-bold">
                  S/ {Number(resumen.total_ingresos).toFixed(2)}
                </p>
              </div>
              <div className="bg-white rounded-DEFAULT shadow-card p-4">
                <p className="text-gray text-xs uppercase tracking-wide mb-1">Gastos</p>
                <p className="text-red text-2xl font-bold">
                  S/ {Number(resumen.total_gastos).toFixed(2)}
                </p>
              </div>
              <div className="bg-white rounded-DEFAULT shadow-card p-4">
                <p className="text-gray text-xs uppercase tracking-wide mb-1">Balance</p>
                <p className="text-orange text-2xl font-bold">
                  S/ {Number(resumen.balance).toFixed(2)}
                </p>
              </div>
            </div>
          )}

          {ventas.length === 0 && (
            <div className="bg-white border border-gray-border rounded-DEFAULT shadow-card p-10 text-center">
              <p className="text-dark font-medium mb-1">Aún no tienes movimientos financieros</p>
              <p className="text-gray text-sm">
                Los ingresos por pedidos y otros movimientos aparecerán aquí.
              </p>
            </div>
          )}

          {ventas.length > 0 && (
            <div className="bg-white rounded-DEFAULT shadow-card overflow-hidden">
              <table className="w-full text-sm">
                <thead>
                  <tr className="bg-gray-light text-left text-gray text-xs uppercase tracking-wide">
                    <th className="px-4 py-3">Fecha</th>
                    <th className="px-4 py-3">Categoría</th>
                    <th className="px-4 py-3">Descripción</th>
                    <th className="px-4 py-3">Tipo</th>
                    <th className="px-4 py-3 text-right">Monto</th>
                  </tr>
                </thead>
                <tbody>
                  {ventas.map((mov) => (
                    <tr key={mov.id} className="border-t border-gray-border">
                      <td className="px-4 py-3 text-gray">{formatearFecha(mov.fecha)}</td>
                      <td className="px-4 py-3 text-dark">{mov.categoria}</td>
                      <td className="px-4 py-3 text-gray">{mov.descripcion || '—'}</td>
                      <td className="px-4 py-3">
                        <span className={`inline-block px-2 py-1 rounded text-xs font-medium ${
                          mov.tipo === 'ingreso' ? 'bg-green/10 text-green' : 'bg-red/10 text-red'
                        }`}>
                          {mov.tipo === 'ingreso' ? 'Ingreso' : 'Gasto'}
                        </span>
                      </td>
                      <td className={`px-4 py-3 text-right font-semibold ${
                        mov.tipo === 'ingreso' ? 'text-green' : 'text-red'
                      }`}>
                        {mov.tipo === 'ingreso' ? '+' : '-'} S/ {Number(mov.monto).toFixed(2)}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </>
      )}
    </div>
  );
}

function VentasSkeleton() {
  return (
    <div className="animate-pulse">
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        {Array.from({ length: 3 }).map((_, i) => (
          <div key={i} className="bg-white rounded-DEFAULT shadow-card p-4 h-20" />
        ))}
      </div>
      <div className="bg-white rounded-DEFAULT shadow-card p-4 space-y-3">
        {Array.from({ length: 5 }).map((_, i) => (
          <div key={i} className="h-8 bg-gray-light rounded w-full" />
        ))}
      </div>
    </div>
  );
}
