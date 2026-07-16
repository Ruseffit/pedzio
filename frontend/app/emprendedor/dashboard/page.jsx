'use client';

import { useState, useEffect, useCallback } from 'react';
import { apiExtra } from '@/lib/apiExtra';

const ETIQUETAS_ESTADO = {
  pendiente: { label: 'Pendiente', color: '#F59E0B' },
  confirmado: { label: 'Confirmado', color: '#3B82F6' },
  en_preparacion: { label: 'En preparación', color: '#8B5CF6' },
  en_camino: { label: 'En camino', color: '#F26A1B' },
  entregado: { label: 'Entregado', color: '#22C55E' },
  cancelado: { label: 'Cancelado', color: '#EF4444' },
};

function formatearSoles(valor) {
  return `S/ ${Number(valor).toFixed(2)}`;
}

function formatearFechaCorta(fechaISO) {
  const fecha = new Date(`${fechaISO}T00:00:00`);
  return fecha.toLocaleDateString('es-PE', { weekday: 'short' }).replace('.', '');
}

export default function DashboardPage() {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const cargar = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const respuesta = await apiExtra.getDashboard();
      setData(respuesta);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    cargar();
  }, [cargar]);

  if (loading) return <DashboardSkeleton />;

  if (error) {
    return (
      <div className="p-8">
        <div className="bg-white border border-red/20 rounded-DEFAULT shadow-card p-6 text-center max-w-md mx-auto">
          <p className="text-red font-medium mb-1">No se pudieron cargar las estadísticas</p>
          <p className="text-gray text-sm mb-4">{error}</p>
          <button
            onClick={cargar}
            className="bg-orange text-white text-sm font-medium rounded-DEFAULT px-4 py-2 hover:opacity-90 transition"
          >
            Reintentar
          </button>
        </div>
      </div>
    );
  }

  const { resumen, pedidos_por_dia, pedidos_por_estado, productos_top } = data;
  const maxPorDia = Math.max(1, ...pedidos_por_dia.map((d) => d.total));
  const totalPorEstado = pedidos_por_estado.reduce((acc, e) => acc + e.total, 0) || 1;

  return (
    <div className="p-8">
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-dark">Dashboard</h1>
        <button
          onClick={cargar}
          className="text-sm text-gray border border-gray-border rounded px-3 py-1.5 hover:bg-gray-light transition"
        >
          Actualizar
        </button>
      </div>

      {/* Tarjetas resumen */}
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <TarjetaStat label="Pedidos hoy" valor={resumen.pedidos_hoy} icono="🛒" />
        <TarjetaStat label="Por atender" valor={resumen.pedidos_pendientes} icono="⏳" color="text-yellow" />
        <TarjetaStat label="Clientes" valor={resumen.clientes_totales} icono="👥" />
        <TarjetaStat label="Productos activos" valor={resumen.productos_activos} icono="📦" />
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        {/* Ventas del mes con variación */}
        <div className="bg-white rounded-DEFAULT shadow-card p-5 lg:col-span-1">
          <p className="text-gray text-xs uppercase tracking-wide mb-1">Ventas este mes</p>
          <p className="text-dark text-2xl font-bold mb-1">{formatearSoles(resumen.ventas_mes_actual)}</p>
          <p className={`text-xs font-medium ${resumen.variacion_porcentual >= 0 ? 'text-green' : 'text-red'}`}>
            {resumen.variacion_porcentual >= 0 ? '▲' : '▼'} {Math.abs(resumen.variacion_porcentual)}% vs. mes anterior
          </p>
          <p className="text-gray text-xs mt-3">Ticket promedio</p>
          <p className="text-dark font-semibold">{formatearSoles(resumen.ticket_promedio)}</p>
        </div>

        {/* Pedidos últimos 7 días */}
        <div className="bg-white rounded-DEFAULT shadow-card p-5 lg:col-span-2">
          <p className="text-gray text-xs uppercase tracking-wide mb-4">Pedidos - últimos 7 días</p>
          <div className="flex items-end justify-between gap-2 h-32">
            {pedidos_por_dia.map((dia) => (
              <div key={dia.fecha} className="flex-1 flex flex-col items-center gap-2">
                <div className="w-full flex-1 flex items-end">
                  <div
                    className="w-full bg-orange rounded-t transition-all"
                    style={{ height: `${Math.max(4, (dia.total / maxPorDia) * 100)}%` }}
                    title={`${dia.total} pedido(s)`}
                  />
                </div>
                <span className="text-[10px] text-gray uppercase">{formatearFechaCorta(dia.fecha)}</span>
              </div>
            ))}
          </div>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {/* Distribución por estado */}
        <div className="bg-white rounded-DEFAULT shadow-card p-5">
          <p className="text-gray text-xs uppercase tracking-wide mb-4">Pedidos por estado</p>
          {pedidos_por_estado.length === 0 && (
            <p className="text-gray text-sm">Aún no tienes pedidos.</p>
          )}
          <div className="space-y-3">
            {pedidos_por_estado.map((e) => {
              const meta = ETIQUETAS_ESTADO[e.estado] || { label: e.estado, color: '#6B7280' };
              const porcentaje = Math.round((e.total / totalPorEstado) * 100);
              return (
                <div key={e.estado}>
                  <div className="flex justify-between text-xs mb-1">
                    <span className="text-dark font-medium">{meta.label}</span>
                    <span className="text-gray">{e.total}</span>
                  </div>
                  <div className="w-full h-2 bg-gray-light rounded-full overflow-hidden">
                    <div
                      className="h-full rounded-full"
                      style={{ width: `${porcentaje}%`, backgroundColor: meta.color }}
                    />
                  </div>
                </div>
              );
            })}
          </div>
        </div>

        {/* Top productos */}
        <div className="bg-white rounded-DEFAULT shadow-card p-5">
          <p className="text-gray text-xs uppercase tracking-wide mb-4">Productos más vendidos</p>
          {productos_top.length === 0 && (
            <p className="text-gray text-sm">Aún no tienes ventas registradas.</p>
          )}
          <div className="space-y-3">
            {productos_top.map((p, i) => (
              <div key={p.nombre} className="flex items-center justify-between gap-3">
                <div className="flex items-center gap-2 min-w-0">
                  <span className="w-5 h-5 flex items-center justify-center rounded-full bg-orange-light text-orange text-[11px] font-bold flex-shrink-0">
                    {i + 1}
                  </span>
                  <span className="text-sm text-dark truncate">{p.nombre}</span>
                </div>
                <div className="text-right flex-shrink-0">
                  <p className="text-sm font-semibold text-dark">{p.unidades} und.</p>
                  <p className="text-xs text-gray">{formatearSoles(p.ingresos)}</p>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}

function TarjetaStat({ label, valor, icono, color = 'text-dark' }) {
  return (
    <div className="bg-white rounded-DEFAULT shadow-card p-4">
      <div className="flex items-center justify-between mb-1">
        <p className="text-gray text-xs uppercase tracking-wide">{label}</p>
        <span className="text-base">{icono}</span>
      </div>
      <p className={`text-2xl font-bold ${color}`}>{valor}</p>
    </div>
  );
}

function DashboardSkeleton() {
  return (
    <div className="p-8 animate-pulse">
      <div className="h-8 bg-gray-light rounded w-40 mb-6" />
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        {Array.from({ length: 4 }).map((_, i) => (
          <div key={i} className="bg-white rounded-DEFAULT shadow-card h-20" />
        ))}
      </div>
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
        {Array.from({ length: 3 }).map((_, i) => (
          <div key={i} className="bg-white rounded-DEFAULT shadow-card h-40" />
        ))}
      </div>
    </div>
  );
}
