'use client';

import { useState, useEffect, useCallback, useRef } from 'react';
import { api } from '@/lib/api';
import { useToast } from '@/components/ToastContext';

const API_BASE = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

const CATEGORIAS_OPCIONES = [
  'Comida',
  'Postres',
  'Cafetería',
  'Bebidas',
  'Regalos',
  'Tiendas',
  'Ropa y accesorios',
  'Artesanías',
  'Tecnología',
  'Salud y belleza',
  'Otro',
];

// ── Componente principal ──────────────────────────────────────────────────────

export default function MiNegocioPage() {
  const { toast } = useToast();
  const fileInputRef = useRef(null);

  const [loading, setLoading]   = useState(true);
  const [saving, setSaving]     = useState(false);
  const [error, setError]       = useState('');
  const [formError, setFormError] = useState('');

  // Campos del formulario
  const [nombreNegocio,    setNombreNegocio]    = useState('');
  const [descripcion,      setDescripcion]      = useState('');
  const [direccion,        setDireccion]        = useState('');
  const [telefonoContacto, setTelefonoContacto] = useState('');
  const [categoria,        setCategoria]        = useState('');

  // Logo
  const [logoActual,   setLogoActual]   = useState(null);  // URL del logo guardado
  const [logoPreview,  setLogoPreview]  = useState(null);  // preview local (base64)
  const [logoBase64,   setLogoBase64]   = useState(null);  // base64 listo para enviar

  // ── Carga inicial ───────────────────────────────────────────────────────────
  const cargarNegocio = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const { emprendimiento: emp } = await api.getNegocio();
      setNombreNegocio(emp.nombre_negocio    ?? '');
      setDescripcion(emp.descripcion          ?? '');
      setDireccion(emp.direccion              ?? '');
      setTelefonoContacto(emp.telefono_contacto ?? '');
      setCategoria(emp.categorias?.[0]?.nombre ?? '');
      setLogoActual(emp.logo_path             ?? null);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { cargarNegocio(); }, [cargarNegocio]);

  // ── Selección de imagen ─────────────────────────────────────────────────────
  function manejarArchivo(e) {
    const archivo = e.target.files?.[0];
    if (!archivo) return;

    const tiposPermitidos = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!tiposPermitidos.includes(archivo.type)) {
      setFormError('Solo se aceptan imágenes JPEG, PNG, WEBP o GIF.');
      return;
    }
    if (archivo.size > 2 * 1024 * 1024) {
      setFormError('La imagen no puede superar 2 MB.');
      return;
    }

    setFormError('');
    const reader = new FileReader();
    reader.onload = (ev) => {
      const base64 = ev.target.result; // "data:image/png;base64,..."
      setLogoPreview(base64);
      setLogoBase64(base64);
    };
    reader.readAsDataURL(archivo);
  }

  function quitarLogo() {
    setLogoPreview(null);
    setLogoBase64(null);
    setLogoActual(null);
    if (fileInputRef.current) fileInputRef.current.value = '';
  }

  // ── Guardar ─────────────────────────────────────────────────────────────────
  async function handleSubmit(e) {
    e.preventDefault();
    setFormError('');

    if (!nombreNegocio.trim()) {
      setFormError('El nombre del negocio es obligatorio.');
      return;
    }

    setSaving(true);
    try {
      await api.actualizarNegocio({
        nombre_negocio:    nombreNegocio.trim(),
        descripcion:       descripcion.trim()       || null,
        direccion:         direccion.trim()         || null,
        telefono_contacto: telefonoContacto.trim()  || null,
        categoria:         categoria                || null,
        logo_base64:       logoBase64               || null,
      });
      toast('Negocio actualizado correctamente', '✅');
      setLogoBase64(null); // ya guardado, limpiar el buffer
    } catch (err) {
      setFormError(err.message);
    } finally {
      setSaving(false);
    }
  }

  // ── Imagen a mostrar ─────────────────────────────────────────────────────────
  const imagenMostrada = logoPreview
    ?? (logoActual ? `${API_BASE.replace('/api', '')}/${logoActual}` : null);

  // ── Renders ──────────────────────────────────────────────────────────────────
  return (
    <div className="p-8 max-w-2xl">
      <h1 className="text-2xl font-bold text-dark mb-6">Mi negocio</h1>

      {/* Skeleton */}
      {loading && <FormSkeleton />}

      {/* Error de carga */}
      {!loading && error && (
        <div className="bg-white border border-red/20 rounded-DEFAULT shadow-card p-6 text-center">
          <p className="text-red font-medium mb-1">No se pudieron cargar los datos del negocio</p>
          <p className="text-gray text-sm mb-4">{error}</p>
          <button
            onClick={cargarNegocio}
            className="bg-orange text-white text-sm font-medium rounded-DEFAULT px-4 py-2 hover:opacity-90 transition"
          >
            Reintentar
          </button>
        </div>
      )}

      {/* Formulario */}
      {!loading && !error && (
        <form onSubmit={handleSubmit} className="space-y-5">

          {/* Logo */}
          <section className="bg-white rounded-DEFAULT shadow-card p-6">
            <h2 className="text-sm font-semibold text-gray uppercase tracking-wide mb-4">
              Logo del negocio
            </h2>

            <div className="flex items-center gap-5">
              {/* Preview */}
              <div className="w-20 h-20 rounded-xl border border-gray-border bg-gray-light flex items-center justify-center overflow-hidden shrink-0">
                {imagenMostrada ? (
                  <img
                    src={imagenMostrada}
                    alt="Logo del negocio"
                    className="w-full h-full object-cover"
                  />
                ) : (
                  <span className="text-3xl">🏪</span>
                )}
              </div>

              <div className="flex flex-col gap-2">
                <button
                  type="button"
                  onClick={() => fileInputRef.current?.click()}
                  className="text-sm font-medium text-orange border border-orange rounded-DEFAULT px-4 py-1.5 hover:bg-orange-light transition"
                >
                  {imagenMostrada ? 'Cambiar imagen' : 'Subir imagen'}
                </button>
                {imagenMostrada && (
                  <button
                    type="button"
                    onClick={quitarLogo}
                    className="text-xs text-gray hover:text-red transition"
                  >
                    Quitar logo
                  </button>
                )}
                <p className="text-[11px] text-gray">JPEG, PNG, WEBP o GIF · Máx. 2 MB</p>
              </div>
            </div>

            <input
              ref={fileInputRef}
              type="file"
              accept="image/jpeg,image/png,image/webp,image/gif"
              className="hidden"
              onChange={manejarArchivo}
            />
          </section>

          {/* Datos del negocio */}
          <section className="bg-white rounded-DEFAULT shadow-card p-6 space-y-4">
            <h2 className="text-sm font-semibold text-gray uppercase tracking-wide">
              Datos del negocio
            </h2>

            {/* Nombre */}
            <Campo etiqueta="Nombre del negocio" required>
              <input
                type="text"
                value={nombreNegocio}
                onChange={(e) => setNombreNegocio(e.target.value)}
                maxLength={150}
                placeholder="Ej. Dulce Tentación"
                className="input"
                required
              />
            </Campo>

            {/* Descripción */}
            <Campo etiqueta="Descripción">
              <textarea
                value={descripcion}
                onChange={(e) => setDescripcion(e.target.value)}
                rows={3}
                placeholder="Cuéntanos sobre tu negocio…"
                className="input resize-none"
              />
            </Campo>

            {/* Categoría */}
            <Campo etiqueta="Categoría">
              <select
                value={categoria}
                onChange={(e) => setCategoria(e.target.value)}
                className="input"
              >
                <option value="">— Selecciona una categoría —</option>
                {CATEGORIAS_OPCIONES.map((c) => (
                  <option key={c} value={c}>{c}</option>
                ))}
              </select>
            </Campo>
          </section>

          {/* Contacto y ubicación */}
          <section className="bg-white rounded-DEFAULT shadow-card p-6 space-y-4">
            <h2 className="text-sm font-semibold text-gray uppercase tracking-wide">
              Contacto y ubicación
            </h2>

            {/* Dirección */}
            <Campo etiqueta="Dirección">
              <input
                type="text"
                value={direccion}
                onChange={(e) => setDireccion(e.target.value)}
                maxLength={255}
                placeholder="Ej. Av. Los Pinos 320, Lima"
                className="input"
              />
            </Campo>

            {/* Teléfono */}
            <Campo etiqueta="Teléfono de contacto">
              <input
                type="tel"
                value={telefonoContacto}
                onChange={(e) => setTelefonoContacto(e.target.value)}
                maxLength={20}
                placeholder="Ej. +51 999 888 777"
                className="input"
              />
            </Campo>
          </section>

          {/* Error de formulario */}
          {formError && (
            <div className="bg-white border border-red/20 rounded-DEFAULT p-4">
              <p className="text-red text-sm font-medium">⚠ {formError}</p>
            </div>
          )}

          {/* Botón guardar */}
          <div className="flex justify-end">
            <button
              type="submit"
              disabled={saving}
              className="bg-orange text-white font-semibold text-sm rounded-DEFAULT px-6 py-2.5 hover:opacity-90 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
            >
              {saving ? (
                <>
                  <span className="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                  Guardando…
                </>
              ) : (
                'Guardar cambios'
              )}
            </button>
          </div>

        </form>
      )}
    </div>
  );
}

// ── Sub-componentes ───────────────────────────────────────────────────────────

function Campo({ etiqueta, children, required = false }) {
  return (
    <div>
      <label className="block text-sm text-gray mb-1.5">
        {etiqueta}
        {required && <span className="text-red ml-0.5">*</span>}
      </label>
      {children}
    </div>
  );
}

function FormSkeleton() {
  return (
    <div className="space-y-5 animate-pulse">
      {/* Logo skeleton */}
      <div className="bg-white rounded-DEFAULT shadow-card p-6">
        <div className="h-3 bg-gray-light rounded w-24 mb-4" />
        <div className="flex items-center gap-5">
          <div className="w-20 h-20 bg-gray-light rounded-xl" />
          <div className="space-y-2">
            <div className="h-7 bg-gray-light rounded w-32" />
            <div className="h-3 bg-gray-light rounded w-40" />
          </div>
        </div>
      </div>

      {/* Datos skeleton */}
      {[1, 2].map((i) => (
        <div key={i} className="bg-white rounded-DEFAULT shadow-card p-6 space-y-4">
          <div className="h-3 bg-gray-light rounded w-32 mb-2" />
          {Array.from({ length: 3 }).map((_, j) => (
            <div key={j} className="space-y-1.5">
              <div className="h-3 bg-gray-light rounded w-28" />
              <div className="h-9 bg-gray-light rounded w-full" />
            </div>
          ))}
        </div>
      ))}
    </div>
  );
}
