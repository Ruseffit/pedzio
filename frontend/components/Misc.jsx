'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import { useCart } from './CartContext';

export function StatCard({ label, value, delta, up }) {
  return (
    <div className="stat-card">
      <div className="text-[11px] text-gray font-medium mb-1">{label}</div>
      <div className="text-xl font-bold text-dark">{value}</div>
      {delta && (
        <div className={`text-[11px] font-semibold mt-0.5 ${up ? 'text-green' : 'text-red'}`}>
          {up ? '↑' : '↓'} {delta}
        </div>
      )}
    </div>
  );
}

export function CategoryGrid({ categorias }) {
  return (
    <div className="flex gap-5 flex-wrap">
      {categorias.map((c) => (
        <div key={c.name} className="cat-item flex flex-col items-center gap-1.5 cursor-pointer">
          <div className="cat-icon">{c.icon}</div>
          <span className="text-[11px] text-gray font-medium">{c.name}</span>
        </div>
      ))}
    </div>
  );
}

export function ChartBars({ values, color = 'bg-orange', labels }) {
  return (
    <div>
      <div className="flex items-end gap-1.5 h-20 py-2">
        {values.map((v, i) => (
          <div key={i} className={`flex-1 ${color} rounded-t opacity-80`} style={{ height: `${v}%` }} />
        ))}
      </div>
      {labels && (
        <div className="flex justify-between text-[10px] text-gray mt-1">
          {labels.map((l) => (
            <span key={l}>{l}</span>
          ))}
        </div>
      )}
    </div>
  );
}

export function Badge({ estado, className }) {
  const map = {
    Entregado: 'badge-green',
    'En camino': 'badge-orange',
    'En preparación': 'badge-yellow',
    Confirmado: 'badge-blue',
    Cancelado: 'badge-red',
    Pendiente: 'badge-yellow',
  };
  return <span className={`badge ${map[estado] || 'badge-blue'} ${className || ''}`}>{estado}</span>;
}

export function DonutChart() {
  return (
    <div className="flex items-center gap-5">
      <div className="relative w-[100px] h-[100px] flex-shrink-0">
        <svg viewBox="0 0 36 36" width="100" height="100">
          <circle cx="18" cy="18" r="15.9" fill="none" stroke="#E5E7EB" strokeWidth="3" />
          <circle cx="18" cy="18" r="15.9" fill="none" stroke="#22C55E" strokeWidth="3" strokeDasharray="35 65" strokeDashoffset="25" />
          <circle cx="18" cy="18" r="15.9" fill="none" stroke="#3B82F6" strokeWidth="3" strokeDasharray="25 75" strokeDashoffset="-10" />
          <circle cx="18" cy="18" r="15.9" fill="none" stroke="#F26A1B" strokeWidth="3" strokeDasharray="20 80" strokeDashoffset="-35" />
          <circle cx="18" cy="18" r="15.9" fill="none" stroke="#EF4444" strokeWidth="3" strokeDasharray="10 90" strokeDashoffset="-55" />
        </svg>
        <div className="absolute inset-0 flex flex-col items-center justify-center text-sm font-extrabold text-dark">
          2,350
          <span className="text-[9px] font-medium text-gray">Total</span>
        </div>
      </div>
      <div className="flex flex-col gap-2 text-xs">
        <div className="flex items-center gap-2"><div className="w-2.5 h-2.5 rounded-full bg-blue" /> Pendientes</div>
        <div className="flex items-center gap-2"><div className="w-2.5 h-2.5 rounded-full bg-yellow" /> Confirmados</div>
        <div className="flex items-center gap-2"><div className="w-2.5 h-2.5 rounded-full bg-orange" /> En camino</div>
        <div className="flex items-center gap-2"><div className="w-2.5 h-2.5 rounded-full bg-green" /> Entregados</div>
        <div className="flex items-center gap-2"><div className="w-2.5 h-2.5 rounded-full bg-red" /> Cancelados</div>
      </div>
    </div>
  );
}

export function ClienteTopbar({ userInitial = 'J' }) {
  const router = useRouter();
  const { totalItems } = useCart();
  const [query, setQuery] = useState('');

  return (
    <div className="flex items-center gap-3 -m-6 mb-5 px-6 py-3 bg-white border-b border-gray-border">
      <div className="flex-1">
        <div className="search-bar">
          <span className="text-gray">🔍</span>
          <input
            className="bg-transparent border-none outline-none text-sm flex-1 text-dark"
            placeholder="Buscar productos, emprendimientos..."
            value={query}
            onChange={(e) => setQuery(e.target.value)}
          />
        </div>
      </div>
      <div className="flex gap-3 items-center">
        <div
          className="topbar-icon relative"
          onClick={() => router.push('/cliente/carrito')}
        >
          🛒
          <span className="absolute -top-1 -right-1 bg-orange text-white text-[9px] font-bold rounded-full w-4 h-4 flex items-center justify-center">
            {totalItems}
          </span>
        </div>
        <div className="topbar-icon">🔔</div>
        <div className="avatar">{userInitial}</div>
      </div>
    </div>
  );
}
