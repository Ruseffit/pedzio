'use client';

import { useState } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import Logo from '@/components/Logo';
import { useToast } from '@/components/ToastContext';
import { api } from '@/lib/api';

export default function RegisterPage() {
  const router = useRouter();
  const { toast } = useToast();
  const [form, setForm] = useState({
    tipo: 'Cliente',
    nombre: '',
    negocio: '',
    email: '',
    tel: '',
    pass: '',
    pass2: '',
  });
  const [errors, setErrors] = useState({});
  const [loading, setLoading] = useState(false);

  function update(field, value) {
    setForm((f) => ({ ...f, [field]: value }));
  }

  async function handleSubmit(e) {
    e.preventDefault();
    const newErrors = {};
    if (!form.nombre.trim()) newErrors.nombre = 'Campo requerido';
    if (!form.email || !form.email.includes('@')) newErrors.email = 'Correo inválido';
    if (!form.tel.trim()) newErrors.tel = 'Campo requerido';
    if (!form.pass || form.pass.length < 8) newErrors.pass = 'Mínimo 8 caracteres';
    if (form.pass !== form.pass2) newErrors.pass2 = 'Las contraseñas no coinciden';
    setErrors(newErrors);
    if (Object.keys(newErrors).length) return;

    setLoading(true);
    try {
      await api.register({
        nombre: form.nombre,
        email: form.email,
        password: form.pass,
        telefono: form.tel,
        rol: form.tipo === 'Emprendedor' ? 'emprendedor' : 'cliente',
        nombre_negocio: form.negocio,
      });
      toast('¡Cuenta creada exitosamente! 🎉');
      router.push('/login');
    } catch (err) {
      toast(err.message || 'No se pudo crear la cuenta. Intenta nuevamente.', '❌');
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="max-w-[1200px] mx-auto my-8 bg-white rounded-2xl overflow-hidden shadow-card-lg">
      <div className="flex min-h-[540px] flex-col md:flex-row">
        <div className="flex-1 px-8 py-10 max-w-[520px]">
          <Logo className="mb-6" />
          <h2 className="text-[22px] font-extrabold text-dark">Crear cuenta</h2>
          <p className="text-gray text-[13px] mb-5">Únete a Pedzio</p>

          <form onSubmit={handleSubmit}>
            <div className="form-group">
              <label className="form-label">Quiero registrarme como</label>
              <select
                className="form-input"
                value={form.tipo}
                onChange={(e) => update('tipo', e.target.value)}
              >
                <option>Cliente</option>
                <option>Emprendedor</option>
              </select>
            </div>
            <div className="form-group">
              <label className="form-label">Nombre completo</label>
              <input
                type="text"
                className={`form-input ${errors.nombre ? 'input-error' : ''}`}
                placeholder="Tu nombre completo"
                value={form.nombre}
                onChange={(e) => update('nombre', e.target.value)}
              />
              {errors.nombre && <span className="text-red text-[11px] mt-1 block">{errors.nombre}</span>}
            </div>
            <div className="form-group">
              <label className="form-label">
                Nombre de tu negocio{' '}
                <span className="text-gray font-normal text-[11px]">(solo emprendedores)</span>
              </label>
              <input
                type="text"
                className="form-input"
                placeholder="Ingresa el nombre de tu negocio"
                value={form.negocio}
                onChange={(e) => update('negocio', e.target.value)}
              />
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div className="form-group">
                <label className="form-label">Correo electrónico</label>
                <input
                  type="email"
                  className={`form-input ${errors.email ? 'input-error' : ''}`}
                  placeholder="ejemplo@correo.com"
                  value={form.email}
                  onChange={(e) => update('email', e.target.value)}
                />
                {errors.email && <span className="text-red text-[11px] mt-1 block">{errors.email}</span>}
              </div>
              <div className="form-group">
                <label className="form-label">Teléfono</label>
                <input
                  type="tel"
                  className={`form-input ${errors.tel ? 'input-error' : ''}`}
                  placeholder="9XX XXX XXX"
                  value={form.tel}
                  onChange={(e) => update('tel', e.target.value)}
                />
                {errors.tel && <span className="text-red text-[11px] mt-1 block">{errors.tel}</span>}
              </div>
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div className="form-group">
                <label className="form-label">Contraseña</label>
                <input
                  type="password"
                  className={`form-input ${errors.pass ? 'input-error' : ''}`}
                  placeholder="Mínimo 8 caracteres"
                  value={form.pass}
                  onChange={(e) => update('pass', e.target.value)}
                />
                {errors.pass && <span className="text-red text-[11px] mt-1 block">{errors.pass}</span>}
              </div>
              <div className="form-group">
                <label className="form-label">Confirmar contraseña</label>
                <input
                  type="password"
                  className={`form-input ${errors.pass2 ? 'input-error' : ''}`}
                  placeholder="Repite tu contraseña"
                  value={form.pass2}
                  onChange={(e) => update('pass2', e.target.value)}
                />
                {errors.pass2 && <span className="text-red text-[11px] mt-1 block">{errors.pass2}</span>}
              </div>
            </div>
            <button type="submit" disabled={loading} className="btn btn-primary btn-block text-[15px] py-3.5">
              {loading ? 'Creando cuenta...' : 'Crear cuenta'}
            </button>
          </form>
          <p className="text-center mt-4 text-[13px] text-gray">
            ¿Ya tienes cuenta?{' '}
            <Link href="/login" className="text-orange font-semibold">Inicia sesión aquí</Link>
          </p>
        </div>
        <div className="flex-1 bg-gray-light flex items-center justify-center px-10 py-10">
          <div className="text-center">
            <div className="text-[100px]">👩‍💼</div>
            <h3 className="text-dark text-xl font-bold mt-4">Haz crecer tu negocio</h3>
            <p className="text-gray text-[13px] mt-2 max-w-[260px]">
              Llega a más clientes y gestiona tus pedidos de forma sencilla.
            </p>
          </div>
        </div>
      </div>
    </div>
  );
}
