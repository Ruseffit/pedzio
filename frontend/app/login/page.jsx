'use client';

import { useState } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import Logo from '@/components/Logo';
import { useToast } from '@/components/ToastContext';
import { api } from '@/lib/api';

const DESTINOS_POR_ROL = {
  cliente:      '/cliente/catalogo',
  emprendedor:  '/emprendedor',
  superadmin:   '/admin',
};

export default function LoginPage() {
  const router     = useRouter();
  const { toast }  = useToast();

  const [email,   setEmail]   = useState('');
  const [pass,    setPass]    = useState('');
  const [error,   setError]   = useState('');
  const [loading, setLoading] = useState(false);

  async function handleSubmit(e) {
    e.preventDefault();
    setError('');

    // Validación local básica antes de llamar al backend
    if (!email || !email.includes('@')) {
      setError('Ingresa un correo válido.');
      return;
    }
    if (!pass || pass.length < 6) {
      setError('La contraseña debe tener mínimo 6 caracteres.');
      return;
    }

    setLoading(true);
    try {
      const { user } = await api.login(email, pass);
      toast(`¡Bienvenido, ${user.nombre}! 👋`);
      router.push(DESTINOS_POR_ROL[user.rol] ?? '/');
    } catch (err) {
      // err.message viene directamente del campo "error" del backend
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="max-w-[1200px] mx-auto my-8 bg-white rounded-2xl overflow-hidden shadow-card-lg">
      <div className="flex min-h-[500px] flex-col md:flex-row">

        {/* ── Formulario ── */}
        <div className="flex-1 px-8 py-14 max-w-[480px] mx-auto w-full">
          <Logo className="mb-8" />
          <h2 className="text-[26px] font-extrabold text-dark mb-1">Iniciar sesión</h2>
          <p className="text-gray text-sm mb-7">Bienvenido de vuelta</p>

          {/* Error del backend */}
          {error && (
            <div className="mb-5 px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-red-600 text-[13px]">
              {error}
            </div>
          )}

          <form onSubmit={handleSubmit}>
            <div className="form-group">
              <label className="form-label">Correo electrónico</label>
              <input
                type="email"
                className="form-input"
                placeholder="ejemplo@correo.com"
                value={email}
                onChange={(e) => { setEmail(e.target.value); setError(''); }}
                disabled={loading}
                autoFocus
              />
            </div>

            <div className="form-group">
              <label className="form-label">Contraseña</label>
              <input
                type="password"
                className="form-input"
                placeholder="Ingresa tu contraseña"
                value={pass}
                onChange={(e) => { setPass(e.target.value); setError(''); }}
                disabled={loading}
              />
            </div>

            <div className="flex justify-between items-center mb-5">
              <label className="flex items-center gap-2 text-[13px] text-gray">
                <input type="checkbox" className="w-auto" /> Recuérdame
              </label>
              <a href="#" className="text-[13px] text-orange font-medium">
                ¿Olvidaste tu contraseña?
              </a>
            </div>

            <button
              type="submit"
              disabled={loading}
              className="btn btn-primary btn-block text-[15px] py-3.5 disabled:opacity-60 disabled:cursor-not-allowed"
            >
              {loading ? 'Ingresando...' : 'Entrar'}
            </button>
          </form>

          <p className="text-center mt-5 text-[13px] text-gray">
            ¿No tienes cuenta?{' '}
            <Link href="/register" className="text-orange font-semibold">Crear cuenta</Link>
          </p>
        </div>

        {/* ── Panel decorativo ── */}
        <div className="flex-1 bg-gradient-to-br from-[#1A1A2E] to-[#2D2D4A] flex flex-col items-center justify-center px-10 py-10">
          <div className="text-8xl mb-6">🛵</div>
          <h3 className="text-white text-xl font-bold text-center">Entrega rápida en tu ciudad</h3>
          <p className="text-[#9CA3AF] text-sm text-center mt-2.5 max-w-[260px]">
            Conectamos emprendedores locales con personas que buscan los mejores productos.
          </p>
        </div>

      </div>
    </div>
  );
}
