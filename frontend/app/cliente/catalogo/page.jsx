'use client';

import { Suspense, useState, useEffect, useMemo } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import Link from 'next/link';
import { useCart } from '@/components/CartContext';
import { useToast } from '@/components/ToastContext';
import { api } from '@/lib/api';

// ── Filtro aproximado por categoría ──────────────────────────────────────────
// No existe una columna "categoria" en la tabla emprendimientos todavía, así
// que aproximamos comparando palabras clave contra nombre_negocio + descripcion.
// Si más adelante se agrega esa columna al backend, este mapa deja de hacer falta:
// bastaría con filtrar por e.categoria === categoriaParam directamente.
const PALABRAS_POR_CATEGORIA = {
  'Comida':     ['comida', 'restaurante', 'burger', 'hamburguesa', 'pollo', 'parrilla', 'parrillas', 'lomo', 'menú', 'menu', 'plato', 'sazón', 'sazon', 'sabores', 'cocina'],
  'Postres':    ['postre', 'dulce', 'dulces', 'torta', 'tortas', 'pastel', 'pasteles', 'brownie', 'cheesecake', 'chocolate', 'tentación', 'tentacion', 'repostería', 'reposteria'],
  'Cafetería':  ['café', 'cafe', 'cafetería', 'cafeteria', 'cappuccino', 'latte', 'espresso', 'barrio'],
  'Tiendas':    ['tienda', 'tiendas', 'bazar', 'market', 'abarrotes', 'boutique', 'minimarket'],
  'Regalos':    ['regalo', 'regalos', 'flor', 'flores', 'florería', 'floreria', 'bouquet', 'detalle', 'detalles'],
  'Bebidas':    ['bebida', 'bebidas', 'jugo', 'jugos', 'fruta', 'frutas', 'smoothie', 'refresco', 'zumo'],
};

function coincideConCategoria(emprendimiento, categoria) {
  const palabras = PALABRAS_POR_CATEGORIA[categoria];
  if (!palabras) return true; // categoría desconocida: no filtramos, mostramos todo

  const texto = `${emprendimiento.nombre_negocio ?? ''} ${emprendimiento.descripcion ?? ''}`.toLowerCase();
  return palabras.some((palabra) => texto.includes(palabra));
}

function CatalogoContent() {
  const router          = useRouter();
  const searchParams    = useSearchParams();
  const { agregarAlCarrito, loading: cartLoading } = useCart();
  const { toast }       = useToast();

  const categoriaParam = searchParams.get('categoria'); // null si no hay filtro

  const [emprendimientos,        setEmprendimientos]        = useState([]);
  const [productos,              setProductos]              = useState([]);
  const [emprendimientoActivo,   setEmprendimientoActivo]   = useState(null); // null = todos
  const [query,                  setQuery]                  = useState('');
  const [loadingEmpren,          setLoadingEmpren]          = useState(true);
  const [loadingProductos,       setLoadingProductos]       = useState(false);
  const [error,                  setError]                  = useState('');
  const [agregando,              setAgregando]              = useState(null); // producto_id en vuelo

  // ── Carga inicial de emprendimientos ───────────────────────────────────────
  useEffect(() => {
    async function cargarEmprendimientos() {
      setLoadingEmpren(true);
      try {
        const data = await api.getEmprendimientos();
        setEmprendimientos(data.emprendimientos ?? []);
      } catch (err) {
        setError(err.message);
      } finally {
        setLoadingEmpren(false);
      }
    }
    cargarEmprendimientos();
  }, []);

  // ── Carga productos cuando cambia el emprendimiento seleccionado ──────────
  useEffect(() => {
    async function cargarProductos() {
      setLoadingProductos(true);
      setError('');
      try {
        const data = await api.getProductos(emprendimientoActivo);
        setProductos(data.productos ?? []);
      } catch (err) {
        setError(err.message);
        setProductos([]);
      } finally {
        setLoadingProductos(false);
      }
    }
    cargarProductos();
  }, [emprendimientoActivo]);

  // ── Emprendimientos que coinciden con la categoría de la URL ───────────────
  const emprendimientosFiltrados = useMemo(() => {
    if (!categoriaParam) return emprendimientos;
    return emprendimientos.filter((e) => coincideConCategoria(e, categoriaParam));
  }, [emprendimientos, categoriaParam]);

  const idsFiltrados = useMemo(
    () => new Set(emprendimientosFiltrados.map((e) => e.id)),
    [emprendimientosFiltrados]
  );

  // ── Filtro local por categoría + búsqueda ───────────────────────────────────
  const productosFiltrados = useMemo(() => {
    let base = productos;

    if (categoriaParam) {
      base = base.filter((p) => idsFiltrados.has(p.emprendimiento_id));
    }

    if (!query.trim()) return base;
    const q = query.toLowerCase();
    return base.filter(
      (p) =>
        p.nombre.toLowerCase().includes(q) ||
        (p.emprendimiento_nombre ?? '').toLowerCase().includes(q)
    );
  }, [productos, query, categoriaParam, idsFiltrados]);

  // ── Agregar al carrito ─────────────────────────────────────────────────────
  async function handleAgregar(producto) {
    setAgregando(producto.id);
    await agregarAlCarrito(producto.id, 1, producto.nombre);
    setAgregando(null);
  }

  // ── Seleccionar emprendimiento ─────────────────────────────────────────────
  function seleccionarEmprendimiento(id) {
    setEmprendimientoActivo((prev) => (prev === id ? null : id));
    setQuery('');
  }

  const sinCoincidenciasDeCategoria =
    !loadingEmpren && categoriaParam && emprendimientosFiltrados.length === 0;

  return (
    <div>
      {/* ── Topbar ── */}
      <div className="flex items-center gap-3 mb-4">
        <button onClick={() => router.back()} className="bg-transparent border-none text-lg cursor-pointer">
          ←
        </button>
        <div className="search-bar flex-1">
          <span>🔍</span>
          <input
            className="bg-transparent border-none outline-none text-sm flex-1 text-dark"
            placeholder="Buscar productos..."
            value={query}
            onChange={(e) => setQuery(e.target.value)}
          />
          {query && (
            <button
              className="text-gray text-xs"
              onClick={() => setQuery('')}
            >
              ✕
            </button>
          )}
        </div>
        <div className="topbar-icon" onClick={() => router.push('/cliente/carrito')}>🛒</div>
      </div>

      {/* ── Filtro de categoría activo ── */}
      {categoriaParam && (
        <div className="flex items-center gap-2 mb-3 text-sm">
          <span className="text-gray">
            Mostrando categoría: <span className="font-semibold text-dark">{categoriaParam}</span>
          </span>
          <Link href="/cliente/catalogo" className="text-orange font-semibold hover:underline">
            Ver todos
          </Link>
        </div>
      )}

      {/* ── Chips de emprendimientos ── */}
      <div className="flex gap-2 mb-4 flex-wrap">
        <span
          className={`tag ${emprendimientoActivo === null ? 'active' : ''}`}
          onClick={() => seleccionarEmprendimiento(null)}
        >
          Todos
        </span>

        {loadingEmpren ? (
          <>
            {[1, 2, 3].map((n) => (
              <span key={n} className="tag animate-pulse bg-gray-100 text-transparent select-none">
                Cargando
              </span>
            ))}
          </>
        ) : (
          emprendimientosFiltrados.map((e) => (
            <span
              key={e.id}
              className={`tag ${emprendimientoActivo === e.id ? 'active' : ''}`}
              onClick={() => seleccionarEmprendimiento(e.id)}
            >
              {e.nombre_negocio}
            </span>
          ))
        )}
      </div>

      {/* ── Error global ── */}
      {error && (
        <p className="text-red-500 text-sm text-center py-4">{error}</p>
      )}

      {/* ── Sin emprendimientos en esta categoría ── */}
      {sinCoincidenciasDeCategoria ? (
        <div className="text-center py-16">
          <p className="text-dark font-medium mb-1">
            No hay emprendimientos en la categoría &quot;{categoriaParam}&quot;
          </p>
          <p className="text-gray text-sm mb-4">Prueba con otra categoría o mira el catálogo completo.</p>
          <Link href="/cliente/catalogo" className="btn btn-primary btn-sm inline-block">
            Ver todos
          </Link>
        </div>
      ) : loadingProductos ? (
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
          {[1, 2, 3, 4, 5, 6, 7, 8].map((n) => (
            <div key={n} className="card animate-pulse">
              <div className="w-full h-[130px] bg-gray-100 rounded" />
              <div className="p-3 space-y-2">
                <div className="h-3 bg-gray-100 rounded w-3/4" />
                <div className="h-3 bg-gray-100 rounded w-1/2" />
                <div className="h-8 bg-gray-100 rounded mt-3" />
              </div>
            </div>
          ))}
        </div>
      ) : productosFiltrados.length === 0 ? (
        <p className="text-center text-gray py-16">
          {query
            ? `Sin resultados para "${query}".`
            : categoriaParam
            ? `No hay productos en la categoría "${categoriaParam}".`
            : 'No hay productos disponibles.'}
        </p>
      ) : (
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
          {productosFiltrados.map((p) => (
            <div key={p.id} className="card">
              <div className="w-full h-[130px] bg-[#f3f3f3] flex items-center justify-center text-4xl">
                🛍️
              </div>
              <div className="p-3">
                <div className="text-sm font-semibold text-dark leading-tight">{p.nombre}</div>
                <div className="text-[11px] text-gray mt-0.5 truncate">
                  {p.emprendimiento_nombre}
                </div>
                {p.descripcion && (
                  <div className="text-[11px] text-gray mt-1 line-clamp-2">{p.descripcion}</div>
                )}
                <div className="flex items-center justify-between mt-2.5">
                  <span className="text-[15px] font-bold text-dark">
                    S/ {Number(p.precio).toFixed(2)}
                  </span>
                  <button
                    onClick={() => handleAgregar(p)}
                    disabled={agregando === p.id || cartLoading}
                    className="btn btn-primary btn-sm disabled:opacity-60 disabled:cursor-not-allowed"
                  >
                    {agregando === p.id ? '...' : 'Agregar'}
                  </button>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}

// useSearchParams() exige un límite <Suspense> en el árbol (requisito de Next.js 14
// App Router); si no, el build avisa/falla al intentar prerenderizar esta ruta.
export default function CatalogoPage() {
  return (
    <Suspense fallback={<div className="text-center text-gray py-16">Cargando catálogo...</div>}>
      <CatalogoContent />
    </Suspense>
  );
}
