'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import Logo from '@/components/Logo';
import { CategoryGrid } from '@/components/Misc';
import { api } from '@/lib/api';
import { CATEGORIAS, inferirCategoria } from '@/lib/categorias';

// Cuántos emprendimientos reales se muestran en "Emprendimientos destacados".
const MAX_DESTACADOS = 4;

// Emoji genérico de respaldo cuando el emprendimiento no tiene logo propio
// (la tabla emprendimientos tiene logo_path, pero el endpoint público
// /api/emprendimientos.php todavía no lo expone). Es el mismo criterio ya
// usado en /cliente/catalogo (emoji fijo 🛍️ para tarjetas de producto): un
// placeholder visual, no un dato inventado sobre un negocio específico.
const EMOJI_POR_DEFECTO = '🏪';

export default function LandingPage() {
  const [negocios, setNegocios] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    let cancelado = false;

    async function cargarEmprendimientos() {
      setLoading(true);
      setError('');
      try {
        const data = await api.getEmprendimientos();
        if (!cancelado) {
          setNegocios((data.emprendimientos ?? []).slice(0, MAX_DESTACADOS));
        }
      } catch (err) {
        if (!cancelado) setError(err.message);
      } finally {
        if (!cancelado) setLoading(false);
      }
    }

    cargarEmprendimientos();
    return () => {
      cancelado = true;
    };
  }, []);

  return (
    <div className="max-w-[1200px] mx-auto my-8 bg-white rounded-2xl overflow-hidden shadow-card-lg">
      {/* Topbar */}
      <div className="flex items-center justify-between px-8 py-3.5 border-b border-gray-border bg-white">
        <Logo />
        <div className="hidden md:flex gap-6">
          <Link href="/" className="text-sm text-gray font-medium hover:text-orange">Inicio</Link>
          <Link href="/cliente/catalogo" className="text-sm text-gray font-medium hover:text-orange">Emprendimientos</Link>
          <a href="#categorias" className="text-sm text-gray font-medium hover:text-orange">Categorías</a>
          <Link href="/como-funciona" className="text-sm text-gray font-medium hover:text-orange">¿Cómo funciona?</Link>
        </div>
        <div className="flex gap-2.5 items-center">
          <Link href="/login" className="btn btn-outline btn-sm">Iniciar sesión</Link>
          <Link href="/register" className="btn btn-primary btn-sm">Crear cuenta</Link>
        </div>
      </div>

      {/* Hero */}
      <div className="bg-gradient-to-br from-[#1A1A2E] to-[#2D2D4A] px-10 py-14 flex items-center gap-10 relative overflow-hidden">
        <div className="flex-1 relative z-10">
          <h1 className="text-4xl font-extrabold text-white leading-tight mb-3">
            Apoya a los <span className="text-orange">emprendimientos</span> de tu ciudad
          </h1>
          <p className="text-sm text-[#9CA3AF] mb-6 leading-relaxed max-w-[360px]">
            Pide tus productos favoritos y recíbelos en la puerta de tu casa.
          </p>
          <div className="flex gap-3">
            <Link href="/cliente/catalogo" className="btn btn-primary">Explorar emprendimientos</Link>
            <Link href="/register" className="btn btn-outline text-white border-white/40">Registrarse</Link>
          </div>
        </div>
        <div className="text-[100px] z-10">🍔</div>
      </div>

      {/* Categories */}
      <div id="categorias" className="px-8 py-7">
        <div className="flex items-center justify-between mb-3.5">
          <span className="text-[15px] font-bold text-dark">Categorías</span>
          <a href="#" className="text-xs text-orange font-semibold">Ver todas</a>
        </div>
        <CategoryGrid categorias={CATEGORIAS} />

        {/* Featured businesses */}
        <div className="flex items-center justify-between mb-3.5 mt-8">
          <span className="text-[15px] font-bold text-dark">Emprendimientos destacados</span>
          <a href="#" className="text-xs text-orange font-semibold">Ver todos</a>
        </div>

        {error && (
          <p className="text-red-500 text-sm py-2">{error}</p>
        )}

        {loading ? (
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
            {[1, 2, 3, 4].map((n) => (
              <div key={n} className="rounded-[10px] overflow-hidden border border-gray-border bg-white animate-pulse">
                <div className="h-[100px] bg-gray-100" />
                <div className="px-3 py-2.5 space-y-1.5">
                  <div className="h-2.5 bg-gray-100 rounded w-1/2" />
                  <div className="h-3 bg-gray-100 rounded w-3/4" />
                </div>
              </div>
            ))}
          </div>
        ) : negocios.length === 0 ? (
          <p className="text-gray text-sm py-4">Todavía no hay emprendimientos para mostrar.</p>
        ) : (
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
            {negocios.map((e) => {
              const categoria = inferirCategoria(e);
              return (
                <Link
                  key={e.id}
                  href="/cliente/catalogo"
                  className="rounded-[10px] overflow-hidden border border-gray-border bg-white cursor-pointer hover:shadow-card transition-shadow"
                >
                  <div className="h-[100px] bg-[#f3f3f3] flex items-center justify-center text-4xl">{EMOJI_POR_DEFECTO}</div>
                  <div className="px-3 py-2.5">
                    <div className="text-[11px] text-gray mb-1">{categoria ?? e.direccion ?? '\u00A0'}</div>
                    <div className="text-[13px] font-bold text-dark">{e.nombre_negocio}</div>
                  </div>
                </Link>
              );
            })}
          </div>
        )}
      </div>
    </div>
  );
}
