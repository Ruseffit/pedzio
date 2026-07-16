'use client';

import { useCart } from './CartContext';

export default function ProductCard({ producto, showRating = false }) {
  const { agregarAlCarrito } = useCart();

  return (
    <div className="card">
      <div className="w-full h-[130px] bg-[#f3f3f3] flex items-center justify-center text-4xl">
        {producto.emoji}
      </div>
      <div className="p-3">
        <div className="text-sm font-semibold text-dark">{producto.nombre}</div>
        <div className="text-[11px] text-gray mt-0.5">{producto.tienda}</div>
        {showRating && (
          <div className="flex items-center gap-1 mt-1 text-xs">
            <span className="text-yellow">★</span> {producto.rating}
          </div>
        )}
        <div className="flex items-center justify-between mt-2.5">
          <span className="text-[15px] font-bold text-dark">S/ {producto.precio.toFixed(2)}</span>
          <button onClick={() => agregarAlCarrito(producto.id, 1, producto.nombre)} className="btn btn-primary btn-sm">
            Agregar
          </button>
        </div>
      </div>
    </div>
  );
}
