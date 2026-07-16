'use client';

import Link from 'next/link';
import { usePathname, useRouter } from 'next/navigation';
import Logo from './Logo';
import { api } from '@/lib/api';

export default function Sidebar({ items }) {
  const pathname = usePathname();
  const router = useRouter();

  async function handleLogout() {
    try {
      await api.logout();
    } catch (err) {
      // Si falla la petición (sesión ya expirada, red caída, etc.) igual
      // sacamos al usuario del panel: no tiene sentido dejarlo atrapado.
    } finally {
      router.push('/login');
    }
  }

  return (
    <div className="w-[200px] bg-dark-nav flex-shrink-0 flex flex-col py-5">
      <div className="px-5 pb-6 pt-4">
        <Logo dark />
      </div>
      {items.map((item) => {
        const isLogout = item.action === 'logout';
        // Si el item define `active` explícitamente, se respeta (útil cuando varios
        // items de un dashboard de una sola ruta apuntan al mismo href).
        const active = !isLogout && (item.active !== undefined ? item.active : item.href && pathname === item.href);
        const content = (
          <>
            <span className="w-5 text-center text-base">{item.icon}</span>
            {item.label}
          </>
        );
        if (isLogout) {
          return (
            <div
              key={item.label}
              className="sidebar-item mt-auto"
              onClick={handleLogout}
            >
              {content}
            </div>
          );
        }
        return (
          <Link
            key={item.label}
            href={item.href}
            className={`sidebar-item ${active ? 'active' : ''}`}
          >
            {content}
          </Link>
        );
      })}
    </div>
  );
}
