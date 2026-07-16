'use client';

import { useState, useEffect, useCallback, useMemo } from 'react';
import { useRouter } from 'next/navigation';
import { Badge } from '@/components/Misc';
import { api } from '@/lib/api';

const tabs = ['Todos', 'Pendientes', 'En preparación', 'En camino', 'Entregados', 'Cancelados'];
// Compara contra el valor real que guarda la BD (snake_case), no contra el
// texto en español que solo es para mostrar — antes comparaban contra
// 'Pendiente'/'En camino'/etc. y como la API nunca devolvió el estado con
// esa capitalización, ninguna pestaña aparte de "Todos" mostraba resultados.
const tabToEstado = {
  Pendientes: 'pendiente',
  'En preparación': 'en_preparacion',
  'En camino': 'en_camino',
  Entregados: 'entregado',
  Cancelados: 'cancelado',
};

function formatearFecha(fechaISO) {
  if (!fechaISO) return '—';
  const fecha = new Date(`${fechaISO}T00:00:00`);
  if (Number.isNaN(fecha.getTime())) return fechaISO;
  return fecha.toLocaleDateString('es-PE', { day: '2-digit', month: 'short', year: 'numeric' });
}

export default function PedidosPage() {
  const router = useRouter();
  const [activeTab, setActiveTab] = useState('Todos');
  const [pedidos, setPedidos] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

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

  const filtrados = useMemo(() => {
    if (activeTab === 'Todos') return pedidos;
    return pedidos.filter((p) => p.estado === tabToEstado[activeTab]);
  }, [activeTab, pedidos]);

  return (
    <div>
      <div className="flex justify-between items-center mb-4">
        <span className="text-[15px] font-bold text-dark">Mis pedidos</span>
        <div className="flex gap-2">
          <div className="topbar-icon">🔍</div>
          <div className="topbar-icon" onClick={() => router.push('/cliente/carrito')}>🛒</div>
        </div>
      </div>

      <div className="flex gap-2 mb-4 flex-wrap">
        {tabs.map((t) => (
          <span
            key={t}
            className={`tag ${activeTab === t ? 'active' : ''}`}
            onClick={() => setActiveTab(t)}
          >
            {t}
          </span>
        ))}
      </div>

      {loading && (
        <div className="card p-8 text-center text-gray text-sm">Cargando pedidos…</div>
      )}

      {!loading && error && (
        <div className="card p-6 text-center">
          <p className="text-red font-medium mb-1">No se pudieron cargar los pedidos</p>
          <p className="text-gray text-sm mb-4">{error}</p>
          <button
            onClick={cargarPedidos}
            className="bg-orange text-white text-sm font-medium rounded-DEFAULT px-4 py-2 hover:opacity-90 transition"
          >
            Reintentar
          </button>
        </div>
      )}

      {!loading && !error && (
        <div className="card">
          <table className="tbl">
            <thead>
              <tr>
                <th>Pedido</th>
                <th>Emprendimiento</th>
                <th>Total</th>
                <th>Estado</th>
                <th>Fecha</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              {filtrados.map((p) => (
                <tr key={p.id}>
                  <td>#{p.id}</td>
                  <td>{p.nombre_negocio ?? '—'}</td>
                  <td>S/ {Number(p.total).toFixed(2)}</td>
                  <td><Badge estado={p.estado_legible} /></td>
                  <td className="text-xs text-gray">{formatearFecha(p.creado_en)}</td>
                  <td>
                    <button
                      onClick={() => router.push(`/cliente/pedidos/${p.id}`)}
                      className="text-xs text-orange font-semibold hover:underline"
                    >
                      Ver detalle
                    </button>
                  </td>
                </tr>
              ))}
              {filtrados.length === 0 && (
                <tr>
                  <td colSpan={6} className="text-center text-gray py-8">
                    Sin pedidos en esta categoría.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
