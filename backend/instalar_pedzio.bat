@echo off
REM =====================================================
REM Pedzio - Instalador automático para XAMPP (Windows)
REM Crea la base de datos e importa schema.sql y seed.sql
REM =====================================================

setlocal enabledelayedexpansion

echo ============================================
echo   Instalador de Pedzio para XAMPP
echo ============================================
echo.

REM --- 1. Ubicar mysql.exe de XAMPP ---
set MYSQL_EXE=C:\xampp\mysql\bin\mysql.exe

if not exist "%MYSQL_EXE%" (
    echo No se encontro MySQL en la ruta por defecto: %MYSQL_EXE%
    echo.
    set /p MYSQL_EXE="Escribe la ruta completa a mysql.exe de tu instalacion XAMPP: "
)

if not exist "%MYSQL_EXE%" (
    echo ERROR: No se pudo encontrar mysql.exe en la ruta indicada.
    echo Verifica que XAMPP este instalado y que el modulo MySQL este activo.
    pause
    exit /b 1
)

echo Usando MySQL en: %MYSQL_EXE%
echo.

REM --- 2. Verificar que el servicio MySQL de XAMPP esta corriendo ---
echo Verificando conexion a MySQL...
"%MYSQL_EXE%" -u root -e "SELECT 1;" >nul 2>&1
if errorlevel 1 (
    echo ERROR: No se pudo conectar a MySQL.
    echo Abre el Panel de Control de XAMPP y presiona "Start" en el modulo MySQL.
    pause
    exit /b 1
)
echo Conexion exitosa.
echo.

REM --- 3. Crear la base de datos si no existe ---
echo Creando base de datos "pedzio_db" (si no existe)...
"%MYSQL_EXE%" -u root -e "CREATE DATABASE IF NOT EXISTS pedzio_db CHARACTER SET utf8mb4;"
if errorlevel 1 (
    echo ERROR: No se pudo crear la base de datos.
    pause
    exit /b 1
)
echo Base de datos lista.
echo.

REM --- 4. Importar schema.sql ---
echo Importando estructura de tablas (sql\schema.sql)...
"%MYSQL_EXE%" -u root pedzio_db < sql\schema.sql
if errorlevel 1 (
    echo ERROR: Fallo la importacion de schema.sql
    pause
    exit /b 1
)
echo Estructura importada correctamente.
echo.

REM --- 5. Preguntar si se desea importar datos de prueba ---
set /p IMPORTAR_SEED="Deseas importar datos de prueba (seed.sql)? [S/N]: "
if /i "%IMPORTAR_SEED%"=="S" (
    echo Importando datos de prueba (sql\seed.sql)...
    "%MYSQL_EXE%" -u root pedzio_db < sql\seed.sql
    if errorlevel 1 (
        echo ADVERTENCIA: Hubo un problema importando seed.sql, revisa el mensaje de arriba.
    ) else (
        echo Datos de prueba importados.
        echo.
        echo Actualizando contrasenas de prueba a: Pedzio2026*
        "%MYSQL_EXE%" -u root pedzio_db -e "UPDATE usuarios SET password_hash = '$2y$10$OyMol.liM5Bu5g5YW.b6RuRksnSeUCa8/Hp2YYkyYyRKOEPahSGzS';"
        echo Listo. Todos los usuarios de prueba usan la contrasena: Pedzio2026*
    )
) else (
    echo Se omitio la importacion de datos de prueba.
)

echo.
echo ============================================
echo   Instalacion completada.
echo   Base de datos: pedzio_db
echo   Ahora copia el archivo .env a la raiz del proyecto
echo   y sigue las instrucciones en INSTRUCCIONES.md
echo ============================================
echo.
pause
