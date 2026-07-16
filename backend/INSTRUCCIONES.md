# Cómo probar Pedzio en tu PC con XAMPP (Windows)

Esta guía es para **probar el sistema en tu computadora**, no para el despliegue final en hosting compartido (para eso, ver la sección "Despliegue" del `README.md`).

## Requisitos

- Tener **XAMPP** instalado (incluye PHP 8+ y MySQL/MariaDB). Descárgalo de https://www.apachefriends.org si no lo tienes.
- Descomprimir este proyecto en una carpeta, por ejemplo: `C:\xampp\htdocs\pedzio`

## Paso 1 — Ubicar el proyecto correctamente

Descomprime el `.zip` de Pedzio dentro de la carpeta `htdocs` de XAMPP, de modo que quede así:

```
C:\xampp\htdocs\pedzio\
├── public_html\
├── config\
├── includes\
├── src\
├── sql\
├── instalar_pedzio.bat   ← el instalador
├── .env                  ← ya viene configurado para XAMPP
└── ...
```

## Paso 2 — Encender Apache y MySQL en XAMPP

1. Abre el **Panel de Control de XAMPP**.
2. Presiona **Start** en el módulo **MySQL**.
3. Presiona **Start** en el módulo **Apache** (lo necesitarás en el Paso 4).

Si ves ambos en verde, vas bien.

## Paso 3 — Ejecutar el instalador automático

1. Entra a la carpeta `C:\xampp\htdocs\pedzio`.
2. Haz doble clic en **`instalar_pedzio.bat`**.
3. El script va a:
   - Verificar que puede conectarse a MySQL.
   - Crear la base de datos `pedzio_db` (si no existe).
   - Importar la estructura de tablas (`sql\schema.sql`).
   - Preguntarte si quieres importar datos de prueba (`sql\seed.sql`) — te recomiendo escribir **S** y presionar Enter, para tener usuarios y productos de ejemplo listos.
4. Al final verás el mensaje **"Instalacion completada"**. Presiona una tecla para cerrar la ventana.

Si el script no encuentra `mysql.exe` automáticamente, te pedirá que escribas la ruta manualmente. Normalmente es:
```
C:\xampp\mysql\bin\mysql.exe
```

## Paso 4 — Verificar que el archivo `.env` esté correcto

Ya viene incluido un `.env` preconfigurado para XAMPP con estos valores (no necesitas tocarlo si usaste la instalación por defecto de XAMPP):

```
DB_HOST=localhost
DB_PORT=3306
DB_NAME=pedzio_db
DB_USER=root
DB_PASS=
```

Si tu XAMPP tiene contraseña en el usuario `root` de MySQL (poco común, pero posible si la configuraste tú mismo), agrégala en `DB_PASS=`.

## Paso 5 — Abrir Pedzio en el navegador

Con Apache encendido, abre tu navegador y entra a:

```
http://localhost/pedzio/public_html/
```

Deberías ver la página de inicio de Pedzio.

## Paso 6 — Iniciar sesión con usuarios de prueba

Si importaste `seed.sql` en el Paso 3, ya existen estas cuentas (todas con la contraseña **`Pedzio2026*`**):

| Rol | Correo |
|---|---|
| SuperAdmin | `admin@pedzio.test` |
| Emprendedor | `rosa@pedzio.test` |
| Emprendedor | `lucho@pedzio.test` |
| Cliente | `ana@pedzio.test` |
| Cliente | `luis@pedzio.test` |

Ve a `http://localhost/pedzio/public_html/login.php` e inicia sesión con cualquiera de ellas para explorar cada rol.

## Problemas comunes

**"No se pudo conectar a MySQL"**
→ Verifica que el módulo MySQL de XAMPP esté en verde ("Running") en el Panel de Control.

**La página se ve en blanco o dice "Error 500"**
→ Verifica que el archivo `.env` exista en la raíz del proyecto (no dentro de `public_html/`). Si lo borraste, cópialo de nuevo desde `.env.example` y ajusta los valores según el Paso 4.

**El login falla con "Correo o contraseña incorrectos" aunque uses las credenciales de la tabla de arriba**
→ Asegúrate de haber respondido **S** cuando el instalador preguntó si querías importar los datos de prueba. Si respondiste **N**, vuelve a ejecutar `instalar_pedzio.bat`.

**Quiero borrar todo y empezar de nuevo**
→ Abre phpMyAdmin (`http://localhost/phpmyadmin`), elimina la base de datos `pedzio_db`, y vuelve a ejecutar `instalar_pedzio.bat`.

## ¿Y para subirlo a un hosting real?

Esta guía es solo para pruebas locales. Cuando quieras publicar Pedzio en internet, sigue la sección **"Despliegue en `public_html/`"** del archivo `README.md`, que explica qué carpetas suben tal cual y cuáles quedan protegidas fuera del acceso público.
