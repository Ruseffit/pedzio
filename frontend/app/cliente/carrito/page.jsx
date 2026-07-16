'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import { useCart } from '@/components/CartContext';
import { useModal } from '@/components/ModalContext';
import { useToast } from '@/components/ToastContext';
import { api } from '@/lib/api';

export default function CarritoPage() {
  const { carrito, loading, cambiarQty, eliminarItem, vaciarCarrito, subtotal, total } = useCart();
  const { showModal } = useModal();
  const { toast } = useToast();
  const router = useRouter();

  const [direccionEntrega, setDireccionEntrega] = useState('');
  const [notas, setNotas] = useState('');
  const [enviando, setEnviando] = useState(false);
  const [errorPedido, setErrorPedido] = useState('');

  async function handlePedido() {
    setErrorPedido('');

    if (carrito.length === 0) {
      toast('Tu carrito está vacío', '⚠️');
      return;
    }
    if (!direccionEntrega.trim()) {
      setErrorPedido('La dirección de entrega es obligatoria.');
      return;
    }

    // El carrito no guarda el emprendimiento_id de cada item (el backend no lo
    // devuelve en /carrito.php), así que lo resolvemos cruzando contra el
    // catálogo. Un pedido solo puede pertenecer a un negocio: si el carrito
    // mezcla productos de varios, se lo indicamos al usuario antes de continuar.
    setEnviando(true);
    let emprendimientoId;
    try {
      const { productos } = await api.getProductos();
      const idsPorProducto = new Map(productos.map((p) => [p.id, p.emprendimiento_id]));
      const emprendimientosEnCarrito = new Set(
        carrito.map((item) => idsPorProducto.get(item.id)).filter(Boolean)
      );

      if (emprendimientosEnCarrito.size === 0) {
        setErrorPedido('No se pudo identificar el negocio de los productos en tu carrito.');
        return;
      }
      if (emprendimientosEnCarrito.size > 1) {
        setErrorPedido('Tu carrito tiene productos de más de un negocio. Por ahora solo puedes pedir a un negocio a la vez.');
        return;
      }
      [emprendimientoId] = emprendimientosEnCarrito;
    } catch (err) {
      setErrorPedido(err.message);
      return;
    } finally {
      setEnviando(false);
    }

    showModal(
      <div>
        <div className="text-4xl mb-3">🛵</div>
        <h3 className="text-lg font-bold mb-2">Confirmar pedido</h3>
        <p className="text-gray text-sm">
          Total: <strong className="text-orange">S/ {total.toFixed(2)}</strong>
        </p>
        <p className="text-gray text-[13px] mt-1.5">
          {carrito.length} producto(s) • Envío S/ 5.00
        </p>
        <p className="text-gray text-[13px] mt-1.5">
          Entrega en: <strong>{direccionEntrega}</strong>
        </p>
      </div>,
      async () => {
        setEnviando(true);
        setErrorPedido('');
        try {
          const { pedido_id } = await api.crearPedido(emprendimientoId, direccionEntrega.trim(), notas.trim() || null);
          await vaciarCarrito(); // sincroniza el estado local (el backend ya vació el carrito)
          toast('¡Pedido realizado! En preparación 🍳');
          router.push(`/cliente/pedidos/${pedido_id}`);
        } catch (err) {
          toast(err.message, '⚠️');
        } finally {
          setEnviando(false);
        }
      }
    );
  }

  return (
    <div className="card p-5">
      <div className="text-[15px] font-bold text-dark mb-4">Tu carrito</div>

      {loading && carrito.length === 0 ? (
        <CarritoSkeleton />
      ) : (
        <>
          <div className="grid grid-cols-[2fr_1fr_1fr_32px] gap-2 py-2 border-b border-gray-border text-[11px] font-semibold text-gray uppercase tracking-wide">
            <span>Producto</span>
            <span>Cantidad</span>
            <span>Precio</span>
            <span></span>
          </div>

          {carrito.length === 0 ? (
            <p className="p-5 text-gray text-center">Tu carrito está vacío</p>
          ) : (
            carrito.map((item) => (
              <div key={item.id} className="flex items-center py-3 border-b border-gray-border last:border-b-0">
                <div className="grid grid-cols-[2fr_1fr_1fr_32px] gap-2 items-center w-full">
                  <div className="flex items-center gap-3">
                    <div className="w-11 h-11 rounded-lg bg-[#f3f3f3] flex items-center justify-center text-xl flex-shrink-0">
                      {item.emoji}
                    </div>
                    <div>
                      <div className="text-sm font-semibold">{item.nombre}</div>
                      <div className="text-xs text-gray">{item.tienda}</div>
                    </div>
                  </div>
                  <div className="flex items-center gap-2">
                    <button className="qty-btn" onClick={() => cambiarQty(item.id, -1)}>−</button>
                    <span className="text-sm font-semibold">{item.qty}</span>
                    <button className="qty-btn" onClick={() => cambiarQty(item.id, 1)}>+</button>
                  </div>
                  <span className="text-sm font-bold">S/ {(item.precio * item.qty).toFixed(2)}</span>
                  <span
                    className="text-gray cursor-pointer text-base"
                    onClick={() => eliminarItem(item.id)}
                  >
                    🗑
                  </span>
                </div>
              </div>
            ))
          )}

          {carrito.length > 0 && (
            <div className="mt-4 space-y-3">
              <div>
                <label className="form-label">Dirección de entrega</label>
                <input
                  type="text"
                  className="form-input"
                  placeholder="Ej. Av. Los Pinos 320, Lima"
                  value={direccionEntrega}
                  onChange={(e) => setDireccionEntrega(e.target.value)}
                  maxLength={255}
                />
              </div>
              <div>
                <label className="form-label">Notas (opcional)</label>
                <input
                  type="text"
                  className="form-input"
                  placeholder="Ej. Dejar en recepción"
                  value={notas}
                  onChange={(e) => setNotas(e.target.value)}
                  maxLength={255}
                />
              </div>
            </div>
          )}

          {errorPedido && (
            <p className="text-red text-sm mt-3">⚠ {errorPedido}</p>
          )}

          <div className="mt-5 border-t border-gray-border pt-4">
            <div className="flex justify-between text-sm mb-2">
              <span className="text-gray">Subtotal</span>
              <span className="font-semibold">S/ {subtotal.toFixed(2)}</span>
            </div>
            <div className="flex justify-between text-sm mb-2">
              <span className="text-gray">Envío</span>
              <span className="font-semibold">S/ {carrito.length ? '5.00' : '0.00'}</span>
            </div>
            <div className="flex justify-between border-t border-gray-border pt-3 mt-2">
              <span className="text-[15px] font-bold">Total</span>
              <span className="text-lg font-extrabold text-orange">S/ {total.toFixed(2)}</span>
            </div>
          </div>

          <button
            onClick={handlePedido}
            disabled={enviando || carrito.length === 0}
            className="btn btn-primary btn-block mt-4 text-[15px] py-3.5 disabled:opacity-60 disabled:cursor-not-allowed"
          >
            {enviando ? 'Procesando…' : 'Realizar pedido'}
          </button>
        </>
      )}
    </div>
  );
}

function CarritoSkeleton() {
  return (
    <div className="space-y-3 py-3 animate-pulse">
      {[1, 2, 3].map((n) => (
        <div key={n} className="grid grid-cols-[2fr_1fr_1fr_32px] gap-2 items-center py-2">
          <div className="flex items-center gap-3">
            <div className="w-11 h-11 rounded-lg bg-gray-light" />
            <div className="space-y-1.5 flex-1">
              <div className="h-3 bg-gray-light rounded w-3/4" />
              <div className="h-2.5 bg-gray-light rounded w-1/2" />
            </div>
          </div>
          <div className="h-6 bg-gray-light rounded w-16" />
          <div className="h-3 bg-gray-light rounded w-12" />
          <div />
        </div>
      ))}
    </div>
  );
}
