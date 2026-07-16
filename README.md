# Pedzio — Guía de instalación y puesta en marcha

Sistema de delivery para emprendedores locales.
Backend: PHP 8.1 puro · Frontend: Next.js 14 · BD: MySQL/MariaDB (XAMPP)

---

## Requisitos previos

| Herramienta | Versión mínima | Descarga |
|-------------|---------------|---------|
| XAMPP       | 8.1+          | https://www.apachefriends.org |
| Node.js     | 18 LTS+       | https://nodejs.org |
| npm         | 9+            | incluido con Node.js |

Verifica que estén instalados:

```bash
php --version      # PHP 8.1.x
node --version     # v18.x.x
npm --version      # 9.x.x
```

---

## Paso 1 — Importar la base de datos

1. Abre XAMPP Control Panel y arranca **Apache** y **MySQL**.
2. Abre `http://localhost/phpmyadmin` en el navegador.
3. Crea una base de datos nueva llamada `pedzio_db` (cotejamiento `utf8mb4_unicode_ci`).
4. Selecciona `pedzio_db` → pestaña **Importar**.
5. Importa primero `backend/sql/schema.sql` (estructura de tablas).
6. Luego importa `backend/sql/seed.sql` (datos iniciales de prueba).

---

## Paso 2 — Configurar el backend (.env)

Abre `backend/.env` y ajusta tus credenciales de MySQL:

```env
APP_ENV=development
APP_DEBUG=true

DB_HOST=localhost
DB_PORT=3306
DB_NAME=pedzio_db
DB_USER=root
DB_PASS=

SESSION_NAME=pedzio_session
SESSION_LIFETIME=7200

UPLOADS_MAX_MB=5
```

> Si tu MySQL tiene contraseña en XAMPP (poco común), ponla en `DB_PASS`.

---

## Paso 3 — Correr el backend PHP

**Opción A — PHP built-in server (recomendado para desarrollo):**

Abre una terminal en la carpeta del proyecto y ejecuta:

```bash
cd backend/public_html
php -S localhost:8000
```

Debes ver:
```
PHP 8.1.x Development Server (http://localhost:8000) started
```

Deja esta terminal abierta.

**Opción B — XAMPP Apache:**

1. Copia la carpeta `backend/` completa a `C:\xampp\htdocs\pedzio\`.
2. Asegúrate de que Apache esté corriendo en XAMPP.
3. El backend quedará en `http://localhost/pedzio/public_html/`.
4. En este caso cambia `NEXT_PUBLIC_API_URL` (Paso 5) a:
   ```
   NEXT_PUBLIC_API_URL=http://localhost/pedzio/public_html/api
   ```

**Verificar que funciona:**

Abre en el navegador:
```
http://localhost:8000/api/emprendimientos.php
```
Debe devolver: `{"success":true,"emprendimientos":[]}`

```
http://localhost:8000/api/auth/me.php
```
Debe devolver: `{"error":"No autenticado"}` (código 401 — correcto, no hay sesión aún)

---

## Paso 4 — Instalar dependencias del frontend

Abre una segunda terminal:

```bash
cd frontend
npm install
```

Espera a que termine. Deberías ver `added XXX packages`.

---

## Paso 5 — Configurar el frontend (.env.local)

El archivo `frontend/.env.local` ya viene configurado:

```env
NEXT_PUBLIC_API_URL=http://localhost:8000/api
```

Si usaste la Opción B (XAMPP Apache), cámbialo a:
```env
NEXT_PUBLIC_API_URL=http://localhost/pedzio/public_html/api
```

> Cada vez que modifiques `.env.local` debes reiniciar el servidor Next.js.

---

## Paso 6 — Correr el frontend

En la segunda terminal (con el backend ya corriendo en la primera):

```bash
cd frontend
npm run dev
```

Debes ver:
```
▲ Next.js 14.x.x
- Local:   http://localhost:3000
```

---

## Paso 7 — Verificar que funcionan juntos

**1. Catálogo público**
Abre `http://localhost:3000` — debe cargar sin errores de consola.

**2. Login**
Ve a `http://localhost:3000/login` e inicia sesión con un usuario del `seed.sql`.
Si el login redirige al dashboard del rol correspondiente, todo funciona.

**3. Catálogo con datos reales**
Después del login, ve a `/cliente/catalogo`.
Los chips de emprendimientos deben cargar desde la BD.

**4. Carrito**
Agrega un producto. Ve a `/cliente/carrito`.
Los totales deben calcularse en el servidor PHP (no en localStorage).

---

## Solución de problemas comunes

### CORS bloqueado

**Error en consola:**
```
Access to fetch at 'http://localhost:8000/api/...' has been blocked by CORS policy
```

**Solución:**
1. Abre `http://localhost:8000/api/emprendimientos.php` directamente en el navegador.
2. Si ves HTML de error PHP en vez de JSON, hay un error en el servidor.
3. Revisa la terminal donde corre `php -S` — el error aparece ahí.
4. Lo más común: un `require_once` que no encuentra el archivo. Verifica que
   estés corriendo `php -S` desde dentro de `backend/public_html/`, no desde
   la raíz del proyecto.

Si usas XAMPP Apache y CORS sigue fallando, crea `backend/public_html/api/.htaccess`:
```apache
Header always set Access-Control-Allow-Origin "http://localhost:3000"
Header always set Access-Control-Allow-Credentials "true"
Header always set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
Header always set Access-Control-Allow-Headers "Content-Type, Authorization"
```

---

### Las cookies no se envían (login ok pero me.php sigue devolviendo 401)

**Diagnóstico:**
1. Abre DevTools → Network → clic en `login.php`
2. En Response Headers busca: `Set-Cookie: pedzio_session=...`
3. En la siguiente petición (ej. `me.php`) busca en Request Headers: `Cookie: pedzio_session=...`

Si `Cookie` no aparece en los requests, el problema es `SameSite`.

**Solución:** abre `backend/config/session.php` y cambia temporalmente para desarrollo local:

```php
'samesite' => 'None',
'secure'   => false,
```

---

### Error "No such file or directory" en PHP

Significa que el `require_once` no encuentra `_bootstrap.php`.

Verifica que estás corriendo el servidor desde el directorio correcto:
```bash
# Correcto ✓
cd backend/public_html
php -S localhost:8000

# Incorrecto ✗
cd backend
php -S localhost:8000
```

---

### Next.js no lee la variable de entorno

Si `NEXT_PUBLIC_API_URL` no funciona y la API llama a la URL incorrecta:
1. Detén Next.js (Ctrl+C)
2. Verifica que `.env.local` existe en la carpeta `frontend/`
3. Vuelve a correr `npm run dev`

Las variables `.env` solo se leen al arrancar el servidor, no en caliente.

---

### La BD no conecta

```
No se pudo conectar a la base de datos
```

Checklist:
- ¿MySQL está corriendo en XAMPP?
- ¿El nombre de la BD en `.env` coincide con la que creaste en phpMyAdmin?
- ¿`DB_USER` es `root` y `DB_PASS` está vacío (configuración por defecto de XAMPP)?

---

## Estructura de archivos falta actualizar

```
backend/public_html/api/
├── config.php              ← CORS, jsonResponse(), jsonInput(), requireAuth()
├── auth/
│   ├── login.php           ← POST {email, password} → sesión PHP
│   ├── me.php              ← GET → usuario de sesión actual
│   └── logout.php          ← POST → destruye sesión
├── emprendimientos.php     ← GET → lista emprendimientos activos
├── productos.php           ← GET ?emprendimiento_id → lista productos
├── carrito.php             ← GET + POST (agregar/actualizar/eliminar/vaciar)
└── pedidos.php             ← GET (por rol) + POST (crear desde carrito)

frontend/
├── lib/api.js              ← Cliente HTTP con credentials: include
├── components/
│   └── CartContext.jsx     ← Carrito sincronizado con backend (reemplaza localStorage)
└── app/
    ├── login/page.jsx      ← Login conectado a api.login()
    └── cliente/
        └── catalogo/page.jsx ← Catálogo con emprendimientos y productos reales
```
