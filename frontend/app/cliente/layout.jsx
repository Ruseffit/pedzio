import Sidebar from '@/components/Sidebar';

const items = [
  { icon: '🏠', label: 'Inicio', href: '/cliente' },
  { icon: '🔍', label: 'Explorar', href: '/cliente/catalogo' },
  { icon: '📦', label: 'Pedidos', href: '/cliente/pedidos' },
  { icon: '❤️', label: 'Favoritos', href: '/cliente' },
  { icon: '📍', label: 'Direcciones', href: '/cliente' },
  { icon: '👤', label: 'Perfil', href: '/cliente/perfil' },
  { icon: '🚪', label: 'Cerrar sesión', action: 'logout' },
];

export default function ClienteLayout({ children }) {
  return (
    <div className="max-w-[1200px] mx-auto my-8 bg-white rounded-2xl overflow-hidden shadow-card-lg">
      <div className="flex min-h-[600px]">
        <Sidebar items={items} />
        <div className="flex-1 bg-gray-light p-6 overflow-auto">{children}</div>
      </div>
    </div>
  );
}
