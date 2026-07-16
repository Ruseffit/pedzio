<?php
/**
 * fix-seed-passwords.php
 * -----------------------------------------------------------------
 * Herramienta de UN SOLO USO para desarrollo local.
 *
 * Qué hace:
 *   1. Genera un hash bcrypt REAL para la contraseña de prueba "Pedzio2026*".
 *   2. Actualiza SOLO la columna password_hash de los 5 usuarios del seed
 *      (backend/sql/seed.sql), que hoy tienen el placeholder literal
 *      "$2y$10$examplehashreplacedatruntime" en vez de un hash real.
 *   3. Vuelve a leer cada fila de la BD y corre password_verify() en PHP
 *      puro para confirmar, ANTES de abrir el navegador, que el login
 *      va a aceptar esa contraseña para cada uno de los 5 emails.
 *
 * Qué NO hace (a propósito):
 *   - No toca login.php, Usuario.php, UsuarioController.php ni session.php.
 *   - No corre ningún ALTER TABLE ni modifica el schema.
 *   - No toca ninguna fila que no sea uno de los 5 emails de seed.sql.
 *
 * Uso:
 *   cd backend
 *   php tools/fix-seed-passwords.php
 *
 * Se puede correr varias veces sin problema (siempre re-hashea igual).
 *
 * IMPORTANTE — auto-eliminación:
 *   Si termina con los 5 usuarios verificados OK, el script se borra a sí
 *   mismo (unlink) al final. Es intencional: es una herramienta de dev que
 *   resetea contraseñas y no debería quedar en un hosting real. Si por
 *   permisos de archivo el unlink falla, el script te lo advierte bien
 *   visible por consola para que lo borres a mano.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';

const PASSWORD_PRUEBA = 'Pedzio2026*';

const EMAILS_SEED = [
    'admin@pedzio.test',
    'rosa@pedzio.test',
    'lucho@pedzio.test',
    'ana@pedzio.test',
    'luis@pedzio.test',
];

function linea(string $texto = ''): void
{
    echo $texto . PHP_EOL;
}

linea('=== Pedzio · fix-seed-passwords.php ===');
linea();

// ── 1. Conectar a la BD (usa las mismas credenciales que el resto del backend) ──
try {
    $pdo = obtenerConexion();
} catch (\Throwable $e) {
    linea('✗ No se pudo conectar a la base de datos: ' . $e->getMessage());
    linea('  Revisa backend/.env (DB_HOST/DB_PORT/DB_NAME/DB_USER/DB_PASS) y que MySQL esté encendido en XAMPP.');
    exit(1);
}
linea('✓ Conectado a la base de datos.');
linea();

// ── 2. Generar el hash bcrypt real ───────────────────────────────────────────
$hashNuevo = password_hash(PASSWORD_PRUEBA, PASSWORD_BCRYPT);
linea('Hash generado para "' . PASSWORD_PRUEBA . '":');
linea('  ' . $hashNuevo);
linea();

// ── 3. UPDATE acotado a los 5 emails del seed (nada más) ─────────────────────
$placeholders = implode(', ', array_fill(0, count(EMAILS_SEED), '?'));
$sqlUpdate = "UPDATE usuarios SET password_hash = ? WHERE email IN ({$placeholders})";

$stmt = $pdo->prepare($sqlUpdate);
$stmt->execute([$hashNuevo, ...EMAILS_SEED]);

linea("✓ UPDATE ejecutado. Filas afectadas: {$stmt->rowCount()} (se esperan 5).");
linea();

if ($stmt->rowCount() !== count(EMAILS_SEED)) {
    linea('⚠ Atención: se esperaban 5 filas afectadas y no coincide.');
    linea('  Puede ser normal si ya habías corrido este script antes (rowCount');
    linea('  cuenta solo filas que CAMBIARON de valor, no las que ya tenían el');
    linea('  mismo hash). Seguimos igual con la verificación real abajo.');
    linea();
}

// ── 4. Releer de la BD y verificar con password_verify() (sin servidor HTTP) ──
linea('Verificando password_verify() contra lo que quedó guardado en la BD:');
linea(str_repeat('-', 60));

$stmtSelect = $pdo->prepare('SELECT email, password_hash, rol FROM usuarios WHERE email = ?');

$todoOk = true;
foreach (EMAILS_SEED as $email) {
    $stmtSelect->execute([$email]);
    $usuario = $stmtSelect->fetch();

    if (!$usuario) {
        linea(sprintf('  %-20s NO ENCONTRADO en la tabla usuarios', $email));
        $todoOk = false;
        continue;
    }

    $coincide = password_verify(PASSWORD_PRUEBA, $usuario['password_hash']);
    $estado = $coincide ? 'OK  ✓' : 'FAIL ✗';
    linea(sprintf('  %-20s rol=%-12s %s', $email, $usuario['rol'], $estado));

    if (!$coincide) {
        $todoOk = false;
    }
}

linea(str_repeat('-', 60));
linea();

// ── 5. Veredicto final, explícito, antes de abrir el navegador ──────────────
if (!$todoOk) {
    linea('❌ TODAVÍA NO: al menos un usuario no va a poder loguearse. Revisa el');
    linea('   detalle arriba antes de probar el navegador.');
    linea();
    linea('ℹ️  No me autoborro en este caso: dejate el archivo para poder');
    linea('   diagnosticar y volver a correrlo. Borralo vos a mano cuando');
    linea('   todo quede en OK:');
    linea('   ' . __FILE__);
    exit(1);
}

linea('✅ LISTO: los 5 usuarios del seed van a poder loguearse con "Pedzio2026*".');
linea('   Podés seguir con los curl / navegador con confianza.');
linea();

// ── 6. Auto-eliminación: es una herramienta de un solo uso ───────────────────
// No debería quedar viva en el proyecto (mucho menos si algún día esto se
// sube a un hosting real), así que se borra a sí misma tras confirmar éxito.
$rutaPropia = __FILE__;
if (@unlink($rutaPropia)) {
    linea('🗑️  Script auto-eliminado (' . $rutaPropia . ').');
    exit(0);
}

linea(str_repeat('=', 60));
linea('⚠️  BORRÁ ESTE ARCHIVO AHORA: ' . $rutaPropia);
linea('   No se pudo auto-eliminar (probablemente permisos de archivo en');
linea('   Windows/XAMPP). Hacelo a mano antes de subir este proyecto a');
linea('   ningún hosting real: es un script que resetea contraseñas.');
linea(str_repeat('=', 60));
exit(0);
