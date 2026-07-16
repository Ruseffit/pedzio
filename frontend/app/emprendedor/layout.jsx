import Sidebar from '@/components/Sidebar';
import NotificationBell from '@/components/NotificationBell';

const items = [
  { icon: '📊', label: 'Dashboard',     href: '/emprendedor/dashboard' },
  { icon: '🏪', label: 'Mi negocio',    href: '/emprendedor/mi-negocio' },
  { icon: '📦', label: 'Productos',     href: '/emprendedor/productos' },
  { icon: '🛒', label: 'Pedidos',       href: '/emprendedor/pedidos' },
  { icon: '📈', label: 'Ventas',        href: '/emprendedor/ventas' },
  { icon: '👥', label: 'Clientes',      href: '/emprendedor/clientes' },
  { icon: '⚙️', label: 'Configuración', href: '/emprendedor/configuracion' },
  { icon: '👤', label: 'Perfil',        href: '/emprendedor/perfil' },
  { icon: '🚪', label: 'Cerrar sesión', action: 'logout' },
];

export default function EmprendedorLayout({ children }) {
  return (
    <div className="max-w-[1200px] mx-auto my-8 bg-white rounded-2xl overflow-hidden shadow-card-lg">
      <div className="flex min-h-[600px]">
        <Sidebar items={items} />
        <div className="flex-1 bg-gray-light overflow-auto">
          <div className="flex justify-end px-6 pt-4">
            <NotificationBell />
          </div>
          <div className="px-6 pb-6">{children}</div>
        </div>
      </div>
    </div>
  );
}
