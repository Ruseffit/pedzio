import Link from 'next/link';
import { ClienteTopbar, CategoryGrid, Badge } from '@/components/Misc';
import ProductCard from '@/components/ProductCard';
import { categorias, productos, pedidosCliente } from '@/lib/data';

export default function ClienteDashboard() {
  const recomendados = productos.slice(3, 7);
  const ultimoPedido = pedidosCliente[0];

  return (
    <div>
      <ClienteTopbar />

      <p className="text-lg font-bold mb-1">¡Hola, Juan! 👋</p>
      <p className="text-gray text-[13px] mb-5">¿Qué quieres pedir hoy?</p>

      <div className="mb-5">
        <CategoryGrid categorias={categorias} />
      </div>

      <div className="flex items-center justify-between mb-3.5">
        <span className="text-[15px] font-bold text-dark">Recomendados para ti</span>
        <Link href="/cliente/catalogo" className="text-xs text-orange font-semibold">Ver todos</Link>
      </div>
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">
        {recomendados.map((p) => (
          <ProductCard key={p.id} producto={p} />
        ))}
      </div>

      <div className="flex items-center justify-between mb-3.5">
        <span className="text-[15px] font-bold text-dark">Últimos pedidos</span>
        <Link href="/cliente/pedidos" className="text-xs text-orange font-semibold">Ver todos</Link>
      </div>
      <div className="card">
        <table className="tbl">
          <tbody>
            <tr>
              <td>
                <div className="flex items-center gap-2">
                  <div className="text-xl">🍔</div>
                  <div>
                    <div className="font-semibold text-sm">Burger Clásica</div>
                    <div className="text-xs text-gray">{ultimoPedido.negocio}</div>
                  </div>
                </div>
              </td>
              <td className="text-sm text-gray">{ultimoPedido.fecha}</td>
              <td><Badge estado={ultimoPedido.estado} /></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  );
}
