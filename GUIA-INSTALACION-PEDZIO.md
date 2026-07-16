# Guía de instalación — Pedzio (Windows)

Esta guía sirve para que cualquier persona, sin experiencia previa, pueda
descargar el proyecto y dejarlo funcionando en su computadora.

El proyecto tiene dos partes que deben correr **al mismo tiempo**, cada una en
su propia ventana:

- **Backend** (PHP): el servidor que maneja los datos.
- **Frontend** (Next.js): la página web que se ve en el navegador.

---

## Requisitos previos (se instalan una sola vez)

### 1. XAMPP
Incluye PHP y MySQL, necesarios para el backend.

1. Descarga: https://www.apachefriends.org/es/index.html
2. Ejecuta el instalador y deja todas las opciones por defecto.
3. Se instalará en `C:\xampp`.

### 2. Node.js
Necesario para correr el frontend.

1. Descarga la versión **LTS**: https://nodejs.org
2. Ejecuta el instalador y deja todas las opciones por defecto.

### 3. Verificar que todo quedó instalado
Abre el **Símbolo del sistema** (busca "cmd" en el menú de inicio) y escribe:

```
C:\xampp\php\php.exe --version
node --version
npm --version
```

Cada comando debe responder con un número de versión. Si alguno da error,
repite la instalación de ese programa.

---

## Paso 1 — Descargar y descomprimir el proyecto

1. Descarga el archivo `.zip` del proyecto (normalmente queda en la carpeta
   **Descargas**).
2. Haz clic derecho sobre el `.zip` → **Extraer todo...** → **Extraer**.
3. Verifica que haya quedado una carpeta con `backend` y `frontend` adentro.
   Por ejemplo:
   ```
   C:\Users\TU_USUARIO\Downloads\pedzio-final\
   ```

> A partir de aquí, cuando esta guía diga `RUTA_DEL_PROYECTO`, reemplázalo por
> esa ruta completa (por ejemplo `C:\Users\TU_USUARIO\Downloads\pedzio-final`).

---

## Paso 2 — Crear la base de datos

1. Abre **XAMPP Control Panel** (búscalo en el menú de inicio).
2. Haz clic en **Start** junto a **Apache** y junto a **MySQL**. Ambos deben
   quedar en verde.
3. Abre el navegador y entra a: `http://localhost/phpmyadmin`
4. Haz clic en **Nueva** (menú de la izquierda).
5. Nombra la base de datos `pedzio_db`, elige el cotejamiento
   `utf8mb4_unicode_ci` y haz clic en **Crear**.
6. Con `pedzio_db` seleccionada, ve a la pestaña **Importar**.
7. Haz clic en **Seleccionar archivo**, busca
   `RUTA_DEL_PROYECTO\backend\sql\schema.sql` y haz clic en **Continuar**
   (abajo de la página).
8. Repite el mismo proceso de importar, ahora con
   `RUTA_DEL_PROYECTO\backend\sql\seed.sql`.

El archivo `backend\.env` ya viene configurado para desarrollo local (usuario
`root`, sin contraseña), así que si usaste XAMPP tal cual se instaló, no hace
falta tocarlo.

---

## Paso 3 — Prender el backend

1. Abre el **Símbolo del sistema**.
2. Escribe (reemplazando la ruta):
   ```
   cd RUTA_DEL_PROYECTO\backend\public_html
   C:\xampp\php\php.exe -S localhost:8000
   ```
3. Debe aparecer un mensaje como:
   ```
   PHP 8.1.x Development Server (http://localhost:8000) started
   ```
4. **Deja esta ventana abierta** mientras uses la aplicación. Si la cierras,
   el backend se apaga.

**Para comprobar que funciona:** abre en el navegador
`http://localhost:8000/api/emprendimientos.php`. Debe mostrar algo como
`{"success":true,"emprendimientos":[]}`.

---

## Paso 4 — Prender el frontend

1. Abre **otra** ventana del Símbolo del sistema (no cierres la del backend).
2. Escribe (reemplazando la ruta):
   ```
   cd RUTA_DEL_PROYECTO\frontend
   npm install
   ```
3. Espera a que termine (puede tardar 1–3 minutos la primera vez). Debe
   terminar con un mensaje tipo `added XXX packages`.
4. Luego escribe:
   ```
   npm run dev
   ```
5. Debe aparecer:
   ```
   ▲ Next.js 14.x.x
   - Local:  http://localhost:3000
   ```
6. **Deja esta ventana abierta también.**

---

## Paso 5 — Abrir la aplicación

Con ambas ventanas abiertas y sin errores, abre el navegador en:

```
http://localhost:3000
```

Ya deberías ver la página principal de Pedzio funcionando.

---

## Cada vez que quieras volver a usarlo

No hace falta repetir todo. Solo:

1. Abre XAMPP Control Panel y prende **Apache** y **MySQL**.
2. Terminal 1:
   ```
   cd RUTA_DEL_PROYECTO\backend\public_html
   C:\xampp\php\php.exe -S localhost:8000
   ```
3. Terminal 2:
   ```
   cd RUTA_DEL_PROYECTO\frontend
   npm run dev
   ```
4. Abre `http://localhost:3000`.

(Los pasos de instalar XAMPP/Node, crear la base de datos y `npm install` son
de una sola vez.)

---

## Problemas comunes

**`'php' no se reconoce como un comando...`**
No uses `php` solo; usa la ruta completa: `C:\xampp\php\php.exe`.

**`'npm' no se reconoce como un comando...`**
Node.js no quedó instalado correctamente, o hay que cerrar y volver a abrir
la terminal después de instalarlo.

**`El sistema no puede encontrar la ruta especificada` al hacer `cd`**
Revisa que la ruta que escribiste coincida exactamente con dónde quedó la
carpeta del proyecto. Usa `dir` para ver el contenido de la carpeta en la que
estás parado y confirmar el nombre exacto.

**La página en `localhost:3000` carga pero no trae datos / da error de red**
Verifica que la ventana del backend (Paso 3) siga abierta y sin errores.

**Puerto ocupado (`address already in use` o similar)**
Ya hay algo corriendo en el puerto 8000 o 3000. Cierra esa ventana anterior
o reinicia la computadora e intenta de nuevo.
