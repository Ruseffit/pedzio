'use client';

import { useState, useEffect } from 'react';
import { api } from '@/lib/api';

export default function ProductosPage() {
  const [productos, setProductos] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    cargarProductos();
  }, []);

  async function cargarProductos() {
    try {
      const data = await api.getProductos();
      // Filtrar solo productos del emprendimiento del usuario logueado
      // Por ahora mostramos todos
      setProductos(data.productos || []);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }

  if (loading) return <div className="p-8">Cargando productos...</div>;
  if (error) return <div className="p-8 text-red-500">Error: {error}</div>;

  return (
    <div className="p-8">
      <h1 className="text-2xl font-bold mb-6">Mis Productos</h1>
      
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        {productos.map((producto) => (
          <div key={producto.id} className="bg-white rounded-lg shadow-card p-4">
            <h3 className="font-semibold text-lg">{producto.nombre}</h3>
            <p className="text-gray-500 text-sm mb-2">{producto.descripcion}</p>
            <p className="text-orange font-bold text-xl">S/ {producto.precio}</p>
            <span className={`inline-block px-2 py-1 rounded text-xs mt-2 ${
              producto.disponible 
                ? 'bg-green-100 text-green-700' 
                : 'bg-red-100 text-red-700'
            }`}>
              {producto.disponible ? 'Disponible' : 'No disponible'}
            </span>
          </div>
        ))}
      </div>

      {productos.length === 0 && (
        <p className="text-gray-500 mt-4">No tienes productos registrados.</p>
      )}
    </div>
  );
}