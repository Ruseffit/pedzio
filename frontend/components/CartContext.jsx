'use client';

import { createContext, useContext, useEffect, useState, useCallback } from 'react';
import { useToast } from './ToastContext';
import { api } from '@/lib/api';

const CartContext = createContext(null);

export function CartProvider({ children }) {
  const [carrito, setCarrito] = useState([]);
  const [loading, setLoading] = useState(false);
  const [total, setTotal]     = useState(0);
  const { toast } = useToast();

  // ── Normaliza los items del backend al shape que usa la UI ─────────────────
  // Backend: { producto_id, nombre, precio_unitario, cantidad, subtotal }
  // UI:      { id, nombre, tienda, precio, qty, emoji }
  function normalizarItems(items = []) {
    return items.map((item) => ({
      id:     item.producto_id,
      nombre: item.nombre,
      tienda: item.emprendimiento_nombre ?? '',
      precio: item.precio_unitario,
      qty:    item.cantidad,
      emoji:  '🛍️',
    }));
  }

  // ── Recarga el carrito desde el backend ────────────────────────────────────
  const recargar = useCallback(async () => {
    setLoading(true);
    try {
      const data = await api.getCarrito();
      setCarrito(normalizarItems(data.carrito?.items));
      setTotal(data.carrito?.total ?? 0);
    } catch {
      // 401 sin sesión activa → carrito vacío, sin error visible
      setCarrito([]);
      setTotal(0);
    } finally {
      setLoading(false);
    }
  }, []);

  // Carga inicial al montar
  useEffect(() => { recargar(); }, [recargar]);

  // ── Agregar ────────────────────────────────────────────────────────────────
  const agregarAlCarrito = useCallback(async (productoId, cantidad = 1, nombreProducto = '') => {
    setLoading(true);
    try {
      await api.agregarAlCarrito(productoId, cantidad);
      await recargar();
      toast(`${nombreProducto || 'Producto'} agregado al carrito`, '🛒');
    } catch (err) {
      toast(err.message, '⚠️');
    } finally {
      setLoading(false);
    }
  }, [recargar, toast]);

  // ── Cambiar cantidad ───────────────────────────────────────────────────────
  // La UI llama cambiarQty(id, delta) con +1 / -1.
  // El backend espera cantidad absoluta → la calculamos desde el estado local.
  const cambiarQty = useCallback(async (productoId, delta) => {
    const item = carrito.find((i) => i.id === productoId);
    if (!item) return;

    const nuevaCantidad = item.qty + delta;

    // Optimistic update: refleja el cambio antes de esperar la red
    setCarrito((prev) =>
      nuevaCantidad <= 0
        ? prev.filter((i) => i.id !== productoId)
        : prev.map((i) => i.id === productoId ? { ...i, qty: nuevaCantidad } : i)
    );

    setLoading(true);
    try {
      await api.actualizarCarrito({ [productoId]: nuevaCantidad });
      await recargar(); // confirma el total real desde el backend
    } catch (err) {
      toast(err.message, '⚠️');
      await recargar(); // revierte si algo falló
    } finally {
      setLoading(false);
    }
  }, [carrito, recargar, toast]);

  // ── Eliminar ───────────────────────────────────────────────────────────────
  const eliminarItem = useCallback(async (productoId) => {
    const item = carrito.find((i) => i.id === productoId);

    // Optimistic update
    setCarrito((prev) => prev.filter((i) => i.id !== productoId));

    setLoading(true);
    try {
      await api.eliminarDelCarrito(productoId);
      await recargar();
      if (item) toast(`Eliminado: ${item.nombre}`, '🗑️');
    } catch (err) {
      toast(err.message, '⚠️');
      await recargar();
    } finally {
      setLoading(false);
    }
  }, [carrito, recargar, toast]);

  // ── Vaciar ─────────────────────────────────────────────────────────────────
  const vaciarCarrito = useCallback(async () => {
    setCarrito([]);
    setTotal(0);
    setLoading(true);
    try {
      await api.vaciarCarrito();
    } catch (err) {
      toast(err.message, '⚠️');
      await recargar();
    } finally {
      setLoading(false);
    }
  }, [recargar, toast]);

  // ── Derivados ──────────────────────────────────────────────────────────────
  const totalItems = carrito.reduce((s, i) => s + i.qty, 0);
  const subtotal   = carrito.reduce((s, i) => s + i.precio * i.qty, 0);
  const envio      = carrito.length ? 5 : 0;
  // El total real viene del backend (precios validados en servidor)
  const totalFinal = total > 0 ? total + envio : subtotal + envio;

  return (
    <CartContext.Provider
      value={{
        carrito,
        loading,
        agregarAlCarrito,
        cambiarQty,
        eliminarItem,
        vaciarCarrito,
        totalItems,
        subtotal,
        envio,
        total: totalFinal,
      }}
    >
      {children}
    </CartContext.Provider>
  );
}

export function useCart() {
  const ctx = useContext(CartContext);
  if (!ctx) throw new Error('useCart debe usarse dentro de CartProvider');
  return ctx;
}
