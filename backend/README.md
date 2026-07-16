# Pedzio

Plataforma web de delivery para emprendimientos de alimentos en Lima. Permite a clientes hacer pedidos, a emprendedores gestionar su catálogo, pedidos y finanzas, y a un SuperAdmin supervisar toda la plataforma.

Construido en **PHP puro + MySQL/MariaDB + HTML/CSS/JS**, sin frameworks, pensado para desplegarse en hosting compartido.

## Roles

- **Cliente**: navega catálogo, arma carrito, confirma pedidos, ve su historial.
- **Emprendedor**: gestiona productos, categorías, pedidos recibidos, finanzas y reportes de su propio negocio.
- **SuperAdmin**: supervisa usuarios, emprendimientos, pedidos y finanzas de toda la plataforma.

## Estructura del proyecto

```
pedzio/
├── public_html/     ← Se sube tal cual a public_html/ del hosting
├── config/          ← PRIVADO — fuera de public_html/
├── includes/        ← PRIVADO
├── src/             ← PRIVADO (Controllers, Models, Services, Helpers)
├── views/           ← PRIVADO (reservado para plantillas futuras)
├── sql/             ← PRIVADO (schema.sql, seed.sql)
├── docs/            ← PRIVADO (manuales)
├── tests/           ← PRIVADO (pruebas unitarias e integración)
├── tools/           ← PRIVADO (scripts de un solo uso; no debe quedar accesible por web)
├── vendor/          ← PRIVADO (dependencias de Composer — REQUERIDO: correr `composer install` antes de subir, ya que el envío de notificaciones push depende de minishlink/web-push)
├── .env / .env.example
├── composer.json
└── README.md
```

Ver `docs/manual_tecnico.md` para el detalle completo de arquitectura y seguridad, y `docs/manual_usuario.md` para el uso funcional por rol.

## Requisitos

- PHP 8.1 o superior con extensión `pdo_mysql`.
- MySQL 8.0+ o MariaDB 10.4+.
- Hosting compartido con acceso a `public_html/` (cPanel típico).

## Instalación en local (desarrollo)

1. Clona/copia el proyecto.
2. Copia `.env.example` a `.env` y coloca tus credenciales locales de MySQL.
3. Crea la base de datos e importa el esquema:
   ```bash
   mysql -u root -p -e "CREATE DATABASE pedzio_db CHARACTER SET utf8mb4"
   mysql -u root -p pedzio_db < sql/schema.sql
   mysql -u root -p pedzio_db < sql/seed.sql   # opcional, datos de prueba
   ```
4. Sirve `public_html/` con el servidor embebido de PHP para pruebas rápidas:
   ```bash
   php -S localhost:8000 -t public_html
   ```
5. Abre `http://localhost:8000`.

## Despliegue en hosting compartido (public_html/)

Ver instrucciones detalladas al final de este documento (sección "Despliegue").

## Pruebas

```bash
php tests/run_tests.php
```

Ejecuta pruebas unitarias (validaciones, formato, carrito en sesión) y de integración (conexión a BD, flujo completo de registro → login → catálogo → pedido → finanzas), contra una base de datos real de pruebas.

---

## Despliegue en `public_html/`

### Qué se sube dentro de `public_html/`

Todo el **contenido** de la carpeta `public_html/` de este proyecto, tal cual, manteniendo su jerarquía interna:

- `index.php`, `login.php`, `logout.php`, `registro.php`, `.htaccess`
- `cliente/`, `emprendedor/`, `superadmin/`
- `assets/`
- `uploads/` (con sus 3 subcarpetas y el `.htaccess` de `comprobantes/`)

### Qué se sube **fuera** de `public_html/` (un nivel arriba, en el home del hosting)

- `config/`, `includes/`, `src/`, `views/`, `sql/`, `docs/`, `tests/`, `tools/`, `vendor/`
- `.env` (creado a partir de `.env.example` con las credenciales reales del hosting)
- `.gitignore`, `composer.json`, `README.md`

### Pasos de despliegue

1. Accede a tu panel de hosting (cPanel/Plesk) o por FTP/SFTP.
2. En la raíz de tu cuenta (un nivel arriba de `public_html/`), sube las carpetas privadas: `config/`, `includes/`, `src/`, `views/`, `sql/`, `docs/`, `tests/`, `tools/`, junto con `.env`, `composer.json`, `README.md`.
3. Corre `composer install` en esa raíz (muchos cPanel traen "Setup PHP App" o un botón de Composer; si no, súbelo desde una máquina local con `composer install --no-dev` y sube la carpeta `vendor/` resultante). Es obligatorio: `PushSenderService` depende de `minishlink/web-push` para firmar y enviar las notificaciones VAPID, y sin `vendor/` esa clase no existe.
4. Dentro de `public_html/`, sube el contenido de la carpeta `public_html/` de este proyecto.
5. Crea la base de datos MySQL desde tu panel de hosting (ej. herramienta "MySQL Databases" de cPanel) y anota host, nombre de BD, usuario y contraseña asignados por el hosting.
6. Edita `.env` en el servidor con esos datos reales: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` con tu dominio real, `DB_HOST`/`DB_NAME`/`DB_USER`/`DB_PASS`, `CORS_ORIGENES` con el dominio real del frontend (ver `.env.example`), y las llaves `VAPID_PUBLIC_KEY`/`VAPID_PRIVATE_KEY`/`VAPID_SUBJECT` generadas en el propio servidor (paso 9).
7. Importa `sql/schema.sql` desde phpMyAdmin (herramienta que casi todo hosting compartido incluye).
8. Verifica permisos de escritura (chmod 755 o 775 según tu hosting) en `public_html/uploads/productos/`, `public_html/uploads/perfiles/` y `public_html/uploads/comprobantes/`.
9. Genera las llaves VAPID de producción corriendo `php generar-vapid-keys.php` **en el propio servidor** (nunca reutilices las de desarrollo local) y pégalas en el `.env` del paso 6.
10. Entra a tu dominio y prueba registrar una cuenta de cliente y una de emprendedor para confirmar que todo funciona de punta a punta.

### Nota si tu hosting SOLO permite acceso a `public_html/`

Algunos planes muy básicos no permiten subir nada fuera de `public_html/`. En ese caso: mueve `config/`, `includes/`, `src/`, `views/`, `sql/`, `docs/`, `tests/`, `tools/`, `vendor/` dentro de `public_html/` y agrega un archivo `.htaccess` con `Require all denied` dentro de cada una de esas carpetas para bloquear el acceso web directo. El código de `public_html/_bootstrap.php` usa rutas relativas (`__DIR__ . '/../config/...'`), así que solo necesitas mantener la misma jerarquía relativa entre `public_html/` y las carpetas privadas, estén donde estén.
