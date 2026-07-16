'use client';

import { useState, useEffect } from 'react';
import { useRouter, useParams } from 'next/navigation';
import { Badge } from '@/components/Misc';
import { api } from '@/lib/api';

function formatearFecha(fechaISO) {
  if (!fechaISO) return '—';
  const fecha = new Date(`${fechaISO}T00:00:00`);
  if (Number.isNaN(fecha.getTime())) return fechaISO;
  return fecha.toLocaleDateString('es-PE', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

export default function DetallePedidoPage() {
  const router = useRouter();
  const { id } = useParams();

  const [pedido, setPedido] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    if (!id) return;
    setLoading(true);
    setError('');
    api.getPedidoDetalle(id)
      .then((data) => setPedido(data.pedido))
      .catch((err) => setError(err.message))
      .finally(() => setLoading(false));
  }, [id]);

  return (
    <div>
      {/* Header */}
      <div className="flex items-center gap-3 mb-6">
        <button
          onClick={() => router.push('/cliente/pedidos')}
          className="topbar-icon text-lg"
          aria-label="Volver"
        >
          ←
        </button>
        <span className="text-[15px] font-bold text-dark">
          {pedido ? `Pedido #${pedido.id}` : 'Detalle del pedido'}
        </span>
      </div>

      {/* Loading */}
      {loading && <DetalleSkeleton />}

      {/* Error */}
      {!loading && error && (
        <div className="card p-6 text-center">
          <p className="text-red font-medium mb-1">No se pudo cargar el pedido</p>
          <p className="text-gray text-sm mb-4">{error}</p>
          <button
            onClick={() => router.push('/cliente/pedidos')}
            className="bg-orange text-white text-sm font-medium rounded-DEFAULT px-4 py-2 hover:opacity-90 transition"
          >
            Volver a mis pedidos
          </button>
        </div>
      )}

      {/* Contenido */}
      {!loading && !error && pedido && (
        <div className="flex flex-col gap-4">

          {/* Resumen del pedido */}
          <div className="card p-5">
            <div className="flex items-center justify-between mb-4">
              <h2 className="text-sm font-bold text-dark">Resumen</h2>
              <Badge estado={pedido.estado_legible} />
            </div>

            <div className="grid grid-cols-2 gap-y-3 text-sm">
              <span className="text-gray">Número de pedido</span>
              <span className="font-semibold text-dark text-right">#{pedido.id}</span>

              <span className="text-gray">Emprendimiento</span>
              <span className="font-semibold text-dark text-right">
                {pedido.emprendimiento?.nombre_negocio ?? '—'}
              </span>

              <span className="text-gray">Fecha</span>
              <span className="font-semibold text-dark text-right">
                {formatearFecha(pedido.creado_en)}
              </span>

              <span className="text-gray">Dirección de entrega</span>
              <span className="font-semibold text-dark text-right">
                {pedido.direccion_entrega || '—'}
              </span>

              {pedido.notas && (
                <>
                  <span className="text-gray">Notas</span>
                  <span className="font-semibold text-dark text-right">{pedido.notas}</span>
                </>
              )}
            </div>
          </div>

          {/* Productos */}
          <div className="card p-5">
            <h2 className="text-sm font-bold text-dark mb-3">Productos</h2>
            <table className="tbl">
              <thead>
                <tr>
                  <th>Producto</th>
                  <th className="text-center">Cant.</th>
                  <th className="text-right">Precio unit.</th>
                  <th className="text-right">Subtotal</th>
                </tr>
              </thead>
              <tbody>
                {pedido.productos.map((item, i) => (
                  <tr key={i}>
                    <td>{item.nombre}</td>
                    <td className="text-center">{item.cantidad}</td>
                    <td className="text-right">S/ {Number(item.precio_unitario).toFixed(2)}</td>
                    <td className="text-right">S/ {Number(item.subtotal).toFixed(2)}</td>
                  </tr>
                ))}
              </tbody>
              <tfoot>
                <tr>
                  <td colSpan={3} className="text-right font-bold text-dark pt-3">Total</td>
                  <td className="text-right font-bold text-orange pt-3">
                    S/ {Number(pedido.total).toFixed(2)}
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>

          {/* Botón volver */}
          <button
            onClick={() => router.push('/cliente/pedidos')}
            className="text-sm text-gray border border-gray-border rounded-DEFAULT px-4 py-2 hover:bg-gray-light transition self-start"
          >
            ← Volver a mis pedidos
          </button>

        </div>
      )}
    </div>
  );
}

function DetalleSkeleton() {
  return (
    <div className="flex flex-col gap-4 animate-pulse">
      <div className="card p-5 space-y-3">
        <div className="h-4 bg-gray-light rounded w-1/4 mb-2" />
        <div className="h-4 bg-gray-light rounded w-2/3" />
        <div className="h-4 bg-gray-light rounded w-1/2" />
        <div className="h-4 bg-gray-light rounded w-3/4" />
      </div>
      <div className="card p-5 space-y-2">
        <div className="h-4 bg-gray-light rounded w-1/4 mb-2" />
        <div className="h-8 bg-gray-light rounded w-full" />
        <div className="h-8 bg-gray-light rounded w-full" />
      </div>
    </div>
  );
}
