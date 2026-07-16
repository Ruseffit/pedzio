<?php
/**
 * GET  /api/notificaciones.php
 * POST /api/notificaciones.php
 *
 * Centro de notificaciones in-app del usuario autenticado (cualquier rol:
 * cliente, emprendedor o superadmin — cada quien ve solo las suyas).
 *
 * GET → Query params opcionales:
 *   ?solo_no_leidas=1
 *   ?limite=20   (default 20, máximo 100)
 * Respuesta (200):
 *   {
 *     "success": true,
 *     "notificaciones": [
 *       { "id", "tipo", "titulo", "mensaje", "url", "leido", "creado_en" }
 *     ],
 *     "no_leidas": n
 *   }
 *
 * POST → Body JSON:
 *   { "accion": "marcar_leida", "id": 123 }
 *   { "accion": "marcar_todas_leidas" }
 * Respuesta (200): { "success": true }
 *
 * Error (400 / 401 / 404): { "error": "..." }
 */

require_once __DIR__ . '/config.php';

$usuario = requireAuth();
$pdo = obtenerConexion();

// ── GET: listar notificaciones ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $soloNoLeidas = isset($_GET['solo_no_leidas']) && $_GET['solo_no_leidas'] === '1';
    $limite = isset($_GET['limite']) ? max(1, min(100, (int) $_GET['limite'])) : 20;

    $sql = 'SELECT id, tipo, titulo, mensaje, url, leido, creado_en
            FROM notificaciones WHERE usuario_id = :uid';
    if ($soloNoLeidas) {
        $sql .= ' AND leido = 0';
    }
    $sql .= ' ORDER BY creado_en DESC LIMIT ' . $limite;

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['uid' => $usuario['id']]);

    $notificaciones = array_map(fn($n) => [
        'id'        => (int) $n['id'],
        'tipo'      => $n['tipo'],
        'titulo'    => $n['titulo'],
        'mensaje'   => $n['mensaje'],
        'url'       => $n['url'],
        'leido'     => (bool) $n['leido'],
        'creado_en' => $n['creado_en'],
    ], $stmt->fetchAll());

    $stmtNoLeidas = $pdo->prepare(
        'SELECT COUNT(*) AS total FROM notificaciones WHERE usuario_id = :uid AND leido = 0'
    );
    $stmtNoLeidas->execute(['uid' => $usuario['id']]);
    $noLeidas = (int) $stmtNoLeidas->fetch()['total'];

    jsonResponse([
        'success'         => true,
        'notificaciones'  => $notificaciones,
        'no_leidas'       => $noLeidas,
    ]);
}

// ── POST: marcar como leída(s) ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = jsonInput();
    $accion = $datos['accion'] ?? '';

    if ($accion === 'marcar_leida') {
        $id = (int) ($datos['id'] ?? 0);
        if ($id <= 0) {
            jsonResponse(['error' => 'Falta el id de la notificación.'], 400);
        }
        $stmt = $pdo->prepare(
            'UPDATE notificaciones SET leido = 1 WHERE id = :id AND usuario_id = :uid'
        );
        $stmt->execute(['id' => $id, 'uid' => $usuario['id']]);
        jsonResponse(['success' => true]);
    }

    if ($accion === 'marcar_todas_leidas') {
        $stmt = $pdo->prepare(
            'UPDATE notificaciones SET leido = 1 WHERE usuario_id = :uid AND leido = 0'
        );
        $stmt->execute(['uid' => $usuario['id']]);
        jsonResponse(['success' => true]);
    }

    jsonResponse(['error' => 'Acción inválida. Usa "marcar_leida" o "marcar_todas_leidas".'], 400);
}

jsonResponse(['error' => 'Método no permitido. Usa GET o POST.'], 405);
