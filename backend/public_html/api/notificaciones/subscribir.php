<?php
/**
 * POST /api/notificaciones/subscribir.php
 *
 * Guarda (o actualiza) la suscripción Web Push que el navegador del
 * usuario autenticado generó con pushManager.subscribe(). Cualquier rol
 * puede suscribirse (cliente, emprendedor, superadmin): cada quien recibe
 * push solo de lo que le corresponde a su propia cuenta.
 *
 * Body JSON (accion = "subscribir"):
 *   {
 *     "accion": "subscribir",
 *     "endpoint": "https://fcm.googleapis.com/...",
 *     "keys": { "p256dh": "...", "auth": "..." }
 *   }
 *
 * Body JSON (accion = "desuscribir"):
 *   { "accion": "desuscribir", "endpoint": "https://..." }
 *
 * Respuesta (200): { "success": true }
 * Error (400 / 401): { "error": "..." }
 */

require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Método no permitido. Usa POST.'], 405);
}

$usuario = requireAuth();
$pdo = obtenerConexion();
$datos = jsonInput();
$accion = $datos['accion'] ?? 'subscribir';

if ($accion === 'subscribir') {
    $endpoint = trim((string) ($datos['endpoint'] ?? ''));
    $p256dh = trim((string) ($datos['keys']['p256dh'] ?? ''));
    $auth = trim((string) ($datos['keys']['auth'] ?? ''));

    if ($endpoint === '' || $p256dh === '' || $auth === '') {
        jsonResponse(['error' => 'Faltan datos de la suscripción (endpoint/keys).'], 400);
    }

    $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);

    // UPSERT: si el endpoint ya existía (el mismo navegador re-suscribiéndose,
    // o la suscripción cambió de dueño porque otro usuario inició sesión en
    // el mismo navegador), se actualiza en vez de duplicar la fila.
    $stmt = $pdo->prepare(
        'INSERT INTO notificaciones_subscriptions (usuario_id, endpoint, p256dh, auth, user_agent)
         VALUES (:uid, :endpoint, :p256dh, :auth, :ua)
         ON DUPLICATE KEY UPDATE
            usuario_id = VALUES(usuario_id),
            p256dh = VALUES(p256dh),
            auth = VALUES(auth),
            user_agent = VALUES(user_agent)'
    );
    $stmt->execute([
        'uid'      => $usuario['id'],
        'endpoint' => $endpoint,
        'p256dh'   => $p256dh,
        'auth'     => $auth,
        'ua'       => $userAgent,
    ]);

    jsonResponse(['success' => true]);
}

if ($accion === 'desuscribir') {
    $endpoint = trim((string) ($datos['endpoint'] ?? ''));
    if ($endpoint === '') {
        jsonResponse(['error' => 'Falta el endpoint a eliminar.'], 400);
    }

    $stmt = $pdo->prepare(
        'DELETE FROM notificaciones_subscriptions WHERE endpoint = :endpoint AND usuario_id = :uid'
    );
    $stmt->execute(['endpoint' => $endpoint, 'uid' => $usuario['id']]);

    jsonResponse(['success' => true]);
}

jsonResponse(['error' => 'Acción inválida. Usa "subscribir" o "desuscribir".'], 400);
