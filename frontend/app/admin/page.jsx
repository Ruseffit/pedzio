import { StatCard, ChartBars, DonutChart } from '@/components/Misc';

export default function AdminDashboard() {
  return (
    <div>
      <div className="flex justify-between items-center mb-4">
        <span className="text-base font-bold">Resumen general</span>
        <div className="flex gap-2 items-center">
          <div className="topbar-icon">🔔</div>
          <div className="avatar bg-purple">A</div>
        </div>
      </div>

      <div className="grid grid-cols-2 md:grid-cols-4 gap-3.5">
        <StatCard label="Usuarios" value="1,248" delta="18%" up />
        <StatCard label="Emprendedores" value="342" delta="12%" up />
        <StatCard label="Clientes" value="906" delta="20%" up />
        <StatCard label="Pedidos" value="2,350" delta="15%" up />
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mt-3.5">
        <div className="card p-4">
          <div className="text-[15px] font-bold text-dark mb-3">Ventas mensuales</div>
          <ChartBars
            values={[40, 55, 48, 70, 60, 85]}
            color="bg-blue"
            labels={['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun']}
          />
        </div>

        <div className="card p-4">
          <div className="text-[15px] font-bold text-dark mb-3">Pedidos por estado</div>
          <DonutChart />
        </div>
      </div>

      <div className="card mt-3.5">
        <div className="p-4 border-b border-gray-border">
          <span className="text-[15px] font-bold text-dark">Nuevos usuarios</span>
        </div>
        <div className="p-4">
          <ChartBars values={[50, 70, 40, 90, 60, 80]} color="bg-purple" />
        </div>
      </div>
    </div>
  );
}
