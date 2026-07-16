'use client';

import { useState, useEffect, useCallback } from 'react';
import { Badge } from '@/components/Misc';
import { useToast } from '@/components/ToastContext';
import { api } from '@/lib/api';

const ESTADOS = [
  { value: 'pendiente', label: 'Pendiente' },
  { value: 'confirmado', label: 'Confirmado' },
  { value: 'en_preparacion', label: 'En preparación' },
  { value: 'en_camino', label: 'En camino' },
  { value: 'entregado', label: 'Entregado' },
  { value: 'cancelado', label: 'Cancelado' },
];

function formatearFecha(fechaISO) {
  if (!fechaISO) return '—';
  const fecha = new Date(fechaISO.replace(' ', 'T'));
  if (Number.isNaN(fecha.getTime())) return fechaISO;
  return fecha.toLocaleDateString('es-PE', { day: '2-digit', month: 'short', year: 'numeric' });
}

export default function PedidosEmprendedorPage() {
  const { toast } = useToast();
  const [pedidos, setPedidos] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  // ids de pedidos con un cambio de estado en curso, para deshabilitar su
  // select mientras responde el backend (evita doble clic / doble push).
  const [actualizando, setActualizando] = useState({});

  const cargarPedidos = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const data = await api.getPedidos();
      setPedidos(data.pedidos || []);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    cargarPedidos();
  }, [cargarPedidos]);

  async function cambiarEstado(pedidoId, nuevoEstado) {
    setActualizando((prev) => ({ ...prev, [pedidoId]: true }));
    try {
      const data = await api.cambiarEstadoPedido(pedidoId, nuevoEstado);
      setPedidos((prev) =>
        prev.map((p) =>
          p.id === pedidoId
            ? { ...p, estado: data.pedido.estado, estado_legible: data.pedido.estado_legible }
            : p
        )
      );
      toast('Estado actualizado');
    } catch (err) {
      toast(err.message, '⚠️');
    } finally {
      setActualizando((prev) => ({ ...prev, [pedidoId]: false }));
    }
  }

  return (
    <div className="p-8">
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-dark">Pedidos</h1>
        {!loading && !error && (
          <button
            onClick={cargarPedidos}
            className="text-sm text-gray border border-gray-border rounded px-3 py-1.5 hover:bg-gray-light transition"
          >
            Actualizar
          </button>
        )}
      </div>

      {loading && <PedidosSkeleton />}

      {!loading && error && (
        <div className="bg-white border border-red/20 rounded-DEFAULT shadow-card p-6 text-center">
          <p className="text-red font-medium mb-1">No se pudieron cargar tus pedidos</p>
          <p className="text-gray text-sm mb-4">{error}</p>
          <button
            onClick={cargarPedidos}
            className="bg-orange text-white text-sm font-medium rounded-DEFAULT px-4 py-2 hover:opacity-90 transition"
          >
            Reintentar
          </button>
        </div>
      )}

      {!loading && !error && pedidos.length === 0 && (
        <div className="bg-white border border-gray-border rounded-DEFAULT shadow-card p-10 text-center">
          <p className="text-dark font-medium mb-1">Aún no tienes pedidos</p>
          <p className="text-gray text-sm">
            Cuando un cliente confirme un pedido de tu negocio, aparecerá aquí.
          </p>
        </div>
      )}

      {!loading && !error && pedidos.length > 0 && (
        <div className="bg-white rounded-DEFAULT shadow-card overflow-hidden">
          <table className="w-full text-sm">
            <thead>
              <tr className="bg-gray-light text-left text-gray text-xs uppercase tracking-wide">
                <th className="px-4 py-3">Pedido</th>
                <th className="px-4 py-3">Total</th>
                <th className="px-4 py-3">Dirección de entrega</th>
                <th className="px-4 py-3">Fecha</th>
                <th className="px-4 py-3">Estado</th>
                <th className="px-4 py-3">Cambiar a</th>
              </tr>
            </thead>
            <tbody>
              {pedidos.map((p) => (
                <tr key={p.id} className="border-t border-gray-border">
                  <td className="px-4 py-3 font-medium text-dark">#{p.id}</td>
                  <td className="px-4 py-3 text-dark">S/ {Number(p.total).toFixed(2)}</td>
                  <td className="px-4 py-3 text-gray">{p.direccion_entrega || '—'}</td>
                  <td className="px-4 py-3 text-xs text-gray">{formatearFecha(p.creado_en)}</td>
                  <td className="px-4 py-3">
                    <Badge estado={p.estado_legible} />
                  </td>
                  <td className="px-4 py-3">
                    <select
                      value={p.estado}
                      disabled={!!actualizando[p.id]}
                      onChange={(e) => cambiarEstado(p.id, e.target.value)}
                      className="text-xs border border-gray-border rounded px-2 py-1.5 bg-white disabled:opacity-50"
                    >
                      {ESTADOS.map((e) => (
                        <option key={e.value} value={e.value}>
                          {e.label}
                        </option>
                      ))}
                    </select>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}

function PedidosSkeleton() {
  return (
    <div className="animate-pulse bg-white rounded-DEFAULT shadow-card p-4 space-y-3">
      {Array.from({ length: 5 }).map((_, i) => (
        <div key={i} className="h-8 bg-gray-light rounded w-full" />
      ))}
    </div>
  );
}
