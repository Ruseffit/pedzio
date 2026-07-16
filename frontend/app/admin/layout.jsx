import Sidebar from '@/components/Sidebar';

const items = [
  { icon: '📊', label: 'Dashboard', href: '/admin', active: true },
  { icon: '👥', label: 'Usuarios', href: '/admin', active: false },
  { icon: '🏪', label: 'Emprendimientos', href: '/admin', active: false },
  { icon: '📦', label: 'Productos', href: '/admin', active: false },
  { icon: '🛒', label: 'Pedidos', href: '/admin', active: false },
  { icon: '📈', label: 'Reportes', href: '/admin', active: false },
  { icon: '⚙️', label: 'Configuración', href: '/admin', active: false },
  { icon: '🚪', label: 'Cerrar sesión', action: 'logout' },
];

export default function AdminLayout({ children }) {
  return (
    <div className="max-w-[1200px] mx-auto my-8 bg-white rounded-2xl overflow-hidden shadow-card-lg">
      <div className="flex min-h-[600px]">
        <Sidebar items={items} />
        <div className="flex-1 bg-gray-light p-6 overflow-auto">{children}</div>
      </div>
    </div>
  );
}
