import Link from 'next/link';
import Logo from '@/components/Logo';

const PASOS = [
  {
    numero: '1',
    emoji: '🔎',
    titulo: 'Explora emprendimientos',
    descripcion:
      'Recorre el catálogo y descubre los negocios locales disponibles, organizados por categoría.',
  },
  {
    numero: '2',
    emoji: '🛒',
    titulo: 'Agrega productos al carrito',
    descripcion:
      'Elige los productos que quieras de un emprendimiento y súmalos a tu carrito de compra.',
  },
  {
    numero: '3',
    emoji: '📦',
    titulo: 'Haz tu pedido',
    descripcion:
      'Confirma tu carrito, indica la dirección de entrega y envía el pedido al emprendedor.',
  },
  {
    numero: '4',
    emoji: '🏠',
    titulo: 'Recíbelo en la puerta de tu casa',
    descripcion:
      'Sigue el estado de tu pedido desde "Mis pedidos" hasta que sea entregado.',
  },
];

export const metadata = {
  title: '¿Cómo funciona? – Pedzio',
  description: 'Conoce en pocos pasos cómo pedir productos de emprendimientos locales en Pedzio.',
};

export default function ComoFuncionaPage() {
  return (
    <div className="max-w-[1200px] mx-auto my-8 bg-white rounded-2xl overflow-hidden shadow-card-lg">
      {/* Topbar */}
      <div className="flex items-center justify-between px-8 py-3.5 border-b border-gray-border bg-white">
        <Logo />
        <div className="hidden md:flex gap-6">
          <Link href="/" className="text-sm text-gray font-medium hover:text-orange">Inicio</Link>
          <Link href="/cliente/catalogo" className="text-sm text-gray font-medium hover:text-orange">Emprendimientos</Link>
          <Link href="/#categorias" className="text-sm text-gray font-medium hover:text-orange">Categorías</Link>
          <Link href="/como-funciona" className="text-sm text-orange font-medium">¿Cómo funciona?</Link>
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
            ¿Cómo funciona <span className="text-orange">Pedzio</span>?
          </h1>
          <p className="text-sm text-[#9CA3AF] mb-6 leading-relaxed max-w-[360px]">
            Pedir a tus emprendimientos favoritos toma solo cuatro pasos.
          </p>
          <div className="flex gap-3">
            <Link href="/cliente/catalogo" className="btn btn-primary">Explorar emprendimientos</Link>
            <Link href="/register" className="btn btn-outline text-white border-white/40">Registrarse</Link>
          </div>
        </div>
        <div className="text-[100px] z-10">🛍️</div>
      </div>

      {/* Pasos */}
      <div className="px-8 py-7">
        <div className="mb-3.5">
          <span className="text-[15px] font-bold text-dark">Pasos para hacer tu pedido</span>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          {PASOS.map((paso) => (
            <div
              key={paso.numero}
              className="rounded-[10px] overflow-hidden border border-gray-border bg-white p-5 flex gap-4 items-start"
            >
              <div className="w-11 h-11 shrink-0 rounded-full bg-orange/10 flex items-center justify-center text-xl">
                {paso.emoji}
              </div>
              <div>
                <div className="text-[11px] text-orange font-semibold mb-1">Paso {paso.numero}</div>
                <div className="text-[13px] font-bold text-dark mb-1">{paso.titulo}</div>
                <p className="text-[13px] text-gray leading-relaxed">{paso.descripcion}</p>
              </div>
            </div>
          ))}
        </div>

        <div className="flex justify-center mt-8">
          <Link href="/cliente/catalogo" className="btn btn-primary">Empezar a explorar</Link>
        </div>
      </div>
    </div>
  );
}
