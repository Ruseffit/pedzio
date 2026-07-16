'use client';

import { useState, useEffect, useCallback } from 'react';
import { api } from '@/lib/api';

export default function ProductosPage() {
  const [productos, setProductos] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const cargarProductos = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      // 1. Usuario autenticado
      await api.me();

      // 2. Emprendimiento asociado al emprendedor autenticado
      const { emprendimiento } = await api.getNegocio();

      // 3. Productos filtrados solo por ese emprendimiento
      const data = await api.getProductos(emprendimiento.id);
      setProductos(data.productos || []);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    cargarProductos();
  }, [cargarProductos]);

  return (
    <div className="p-8">
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-dark">Mis Productos</h1>
        {!loading && !error && (
          <button
            onClick={cargarProductos}
            className="text-sm text-gray border border-gray-border rounded px-3 py-1.5 hover:bg-gray-light transition"
          >
            Actualizar
          </button>
        )}
      </div>

      {loading && <ProductosSkeleton />}

      {!loading && error && (
        <div className="bg-white border border-red/20 rounded-DEFAULT shadow-card p-6 text-center">
          <p className="text-red font-medium mb-1">No se pudo cargar tus productos</p>
          <p className="text-gray text-sm mb-4">{error}</p>
          <button
            onClick={cargarProductos}
            className="bg-orange text-white text-sm font-medium rounded-DEFAULT px-4 py-2 hover:opacity-90 transition"
          >
            Reintentar
          </button>
        </div>
      )}

      {!loading && !error && productos.length === 0 && (
        <div className="bg-white border border-gray-border rounded-DEFAULT shadow-card p-10 text-center">
          <p className="text-dark font-medium mb-1">No tienes productos registrados</p>
          <p className="text-gray text-sm">
            Los productos que agregues a tu negocio aparecerán aquí.
          </p>
        </div>
      )}

      {!loading && !error && productos.length > 0 && (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {productos.map((producto) => (
            <div
              key={producto.id}
              className="bg-white rounded-DEFAULT shadow-card p-4 animate-slideIn"
            >
              <h3 className="font-semibold text-lg text-dark">{producto.nombre}</h3>
              <p className="text-gray text-sm mb-2 line-clamp-2">{producto.descripcion}</p>
              <p className="text-orange font-bold text-xl">
                S/ {Number(producto.precio).toFixed(2)}
              </p>
              <span
                className={`inline-block px-2 py-1 rounded text-xs mt-2 ${
                  producto.disponible
                    ? 'bg-green/10 text-green'
                    : 'bg-red/10 text-red'
                }`}
              >
                {producto.disponible ? 'Disponible' : 'No disponible'}
              </span>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}

function ProductosSkeleton() {
  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      {Array.from({ length: 6 }).map((_, i) => (
        <div key={i} className="bg-white rounded-DEFAULT shadow-card p-4 animate-pulse">
          <div className="h-5 bg-gray-light rounded w-3/4 mb-3" />
          <div className="h-3 bg-gray-light rounded w-full mb-2" />
          <div className="h-3 bg-gray-light rounded w-5/6 mb-3" />
          <div className="h-6 bg-gray-light rounded w-1/3" />
        </div>
      ))}
    </div>
  );
}
