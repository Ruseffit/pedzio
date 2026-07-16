'use client';

import { useState, useEffect, useCallback } from 'react';
import { apiExtra } from '@/lib/apiExtra';
import { useToast } from '@/components/ToastContext';

export default function ConfiguracionPage() {
  const { toast } = useToast();

  const [config, setConfig] = useState(null);
  const [form, setForm] = useState(null);
  const [loading, setLoading] = useState(true);
  const [guardando, setGuardando] = useState(false);
  const [error, setError] = useState('');

  const cargar = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const data = await apiExtra.getConfiguracion();
      setConfig(data.configuracion);
      setForm(data.configuracion);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    cargar();
  }, [cargar]);

  function actualizarCampo(campo, valor) {
    setForm((prev) => ({ ...prev, [campo]: valor }));
  }

  const hayCambios = form && config && JSON.stringify(form) !== JSON.stringify(config);

  async function guardar() {
    if (!hayCambios) return;
    setGuardando(true);
    try {
      const cambios = {
        horario_apertura: form.horario_apertura,
        horario_cierre: form.horario_cierre,
        pedido_minimo: Number(form.pedido_minimo),
        radio_entrega_km: Number(form.radio_entrega_km),
        acepta_pedidos: form.acepta_pedidos,
        notif_push_activo: form.notif_push_activo,
        notif_email_activo: form.notif_email_activo,
      };
      const data = await apiExtra.actualizarConfiguracion(cambios);
      setConfig(data.configuracion);
      setForm(data.configuracion);
      toast('Configuración guardada');
    } catch (err) {
      toast(err.message, '⚠️');
    } finally {
      setGuardando(false);
    }
  }

  if (loading) return <ConfiguracionSkeleton />;

  if (error) {
    return (
      <div className="p-8">
        <div className="bg-white border border-red/20 rounded-DEFAULT shadow-card p-6 text-center max-w-md mx-auto">
          <p className="text-red font-medium mb-1">No se pudo cargar la configuración</p>
          <p className="text-gray text-sm mb-4">{error}</p>
          <button
            onClick={cargar}
            className="bg-orange text-white text-sm font-medium rounded-DEFAULT px-4 py-2 hover:opacity-90 transition"
          >
            Reintentar
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="p-8 max-w-2xl">
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-dark">Configuración</h1>
        <button
          onClick={guardar}
          disabled={!hayCambios || guardando}
          className="bg-orange text-white text-sm font-medium rounded-DEFAULT px-4 py-2 hover:opacity-90 transition disabled:opacity-40 disabled:cursor-not-allowed"
        >
          {guardando ? 'Guardando...' : 'Guardar cambios'}
        </button>
      </div>

      {/* Estado del negocio */}
      <Seccion titulo="Estado del negocio">
        <ToggleFila
          label="Aceptar pedidos nuevos"
          descripcion="Si lo desactivas, tu negocio no aparecerá disponible para nuevos pedidos en el catálogo."
          checked={form.acepta_pedidos}
          onChange={(v) => actualizarCampo('acepta_pedidos', v)}
        />
      </Seccion>

      {/* Horario de atención */}
      <Seccion titulo="Horario de atención">
        <div className="grid grid-cols-2 gap-4">
          <Campo label="Hora de apertura">
            <input
              type="time"
              value={form.horario_apertura}
              onChange={(e) => actualizarCampo('horario_apertura', e.target.value)}
              className="input-field"
            />
          </Campo>
          <Campo label="Hora de cierre">
            <input
              type="time"
              value={form.horario_cierre}
              onChange={(e) => actualizarCampo('horario_cierre', e.target.value)}
              className="input-field"
            />
          </Campo>
        </div>
      </Seccion>

      {/* Reglas de pedido */}
      <Seccion titulo="Reglas de pedido">
        <div className="grid grid-cols-2 gap-4">
          <Campo label="Pedido mínimo (S/)">
            <input
              type="number"
              min="0"
              step="0.5"
              value={form.pedido_minimo}
              onChange={(e) => actualizarCampo('pedido_minimo', e.target.value)}
              className="input-field"
            />
          </Campo>
          <Campo label="Radio de entrega (km)">
            <input
              type="number"
              min="0.5"
              step="0.5"
              value={form.radio_entrega_km}
              onChange={(e) => actualizarCampo('radio_entrega_km', e.target.value)}
              className="input-field"
            />
          </Campo>
        </div>
      </Seccion>

      {/* Notificaciones */}
      <Seccion titulo="Notificaciones">
        <ToggleFila
          label="Notificaciones push"
          descripcion="Recibe un aviso en el navegador cuando llegue un pedido nuevo."
          checked={form.notif_push_activo}
          onChange={(v) => actualizarCampo('notif_push_activo', v)}
        />
        <ToggleFila
          label="Notificaciones por correo"
          descripcion="Recibe un resumen por email de la actividad de tu negocio."
          checked={form.notif_email_activo}
          onChange={(v) => actualizarCampo('notif_email_activo', v)}
        />
      </Seccion>

      <style jsx global>{`
        .input-field {
          width: 100%;
          border: 1px solid #e5e7eb;
          border-radius: 10px;
          padding: 0.5rem 0.75rem;
          font-size: 0.875rem;
          color: #1a1a2e;
        }
        .input-field:focus {
          outline: none;
          border-color: #f26a1b;
          box-shadow: 0 0 0 3px rgba(242, 106, 27, 0.15);
        }
      `}</style>
    </div>
  );
}

function Seccion({ titulo, children }) {
  return (
    <div className="bg-white rounded-DEFAULT shadow-card p-5 mb-5">
      <h2 className="text-sm font-semibold text-dark uppercase tracking-wide mb-4">{titulo}</h2>
      <div className="space-y-4">{children}</div>
    </div>
  );
}

function Campo({ label, children }) {
  return (
    <label className="block">
      <span className="block text-xs text-gray mb-1">{label}</span>
      {children}
    </label>
  );
}

function ToggleFila({ label, descripcion, checked, onChange }) {
  return (
    <div className="flex items-center justify-between gap-4">
      <div>
        <p className="text-sm font-medium text-dark">{label}</p>
        {descripcion && <p className="text-xs text-gray mt-0.5">{descripcion}</p>}
      </div>
      <button
        type="button"
        role="switch"
        aria-checked={checked}
        onClick={() => onChange(!checked)}
        className={`relative w-11 h-6 rounded-full transition flex-shrink-0 ${
          checked ? 'bg-orange' : 'bg-gray-border'
        }`}
      >
        <span
          className={`absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform ${
            checked ? 'translate-x-5' : ''
          }`}
        />
      </button>
    </div>
  );
}

function ConfiguracionSkeleton() {
  return (
    <div className="p-8 max-w-2xl animate-pulse">
      <div className="h-8 bg-gray-light rounded w-48 mb-6" />
      {Array.from({ length: 3 }).map((_, i) => (
        <div key={i} className="bg-white rounded-DEFAULT shadow-card p-5 mb-5 h-24" />
      ))}
    </div>
  );
}
