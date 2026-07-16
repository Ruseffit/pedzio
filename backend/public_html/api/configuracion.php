<?php
/**
 * GET  /api/configuracion.php
 * PUT  /api/configuracion.php
 *
 * Configuración del emprendimiento del emprendedor autenticado:
 * horario de atención, pedido mínimo, radio de entrega, si acepta
 * pedidos nuevos, y preferencias de notificación.
 *
 * No reemplaza a /api/perfil.php (que sigue siendo solo lectura de
 * datos básicos). Este endpoint es dueño de los campos "avanzados".
 *
 * GET → Respuesta (200):
 *   {
 *     "success": true,
 *     "configuracion": {
 *       "nombre_negocio", "descripcion", "direccion", "telefono_contacto",
 *       "horario_apertura", "horario_cierre", "pedido_minimo",
 *       "radio_entrega_km", "acepta_pedidos",
 *       "notif_push_activo", "notif_email_activo"
 *     }
 *   }
 *
 * PUT → Body JSON con cualquier subconjunto de los campos editables de
 * arriba (excepto nombre_negocio/descripcion/direccion/telefono_contacto,
 * que ya administra la pantalla "Mi negocio"). Respuesta (200):
 *   { "success": true, "configuracion": { ... actualizado ... } }
 *
 * Error (400 / 401 / 403 / 404): { "error": "..." }
 */

require_once __DIR__ . '/config.php';

$usuario = requireAuth(['emprendedor']);
$pdo = obtenerConexion();

// ── Resolver el emprendimiento del emprendedor logueado ────────────────────
$stmt = $pdo->prepare('SELECT * FROM emprendimientos WHERE usuario_id = :uid LIMIT 1');
$stmt->execute(['uid' => $usuario['id']]);
$emprendimiento = $stmt->fetch();

if (!$emprendimiento) {
    jsonResponse(['error' => 'Aún no has registrado tu emprendimiento.'], 404);
}

$emprendimientoId = (int) $emprendimiento['id'];

function formatearConfiguracion(array $e): array
{
    return [
        'id'                 => (int) $e['id'],
        'nombre_negocio'     => $e['nombre_negocio'],
        'descripcion'        => $e['descripcion'],
        'direccion'          => $e['direccion'],
        'telefono_contacto'  => $e['telefono_contacto'],
        'horario_apertura'   => substr((string) $e['horario_apertura'], 0, 5),
        'horario_cierre'     => substr((string) $e['horario_cierre'], 0, 5),
        'pedido_minimo'      => (float) $e['pedido_minimo'],
        'radio_entrega_km'   => (float) $e['radio_entrega_km'],
        'acepta_pedidos'     => (bool) $e['acepta_pedidos'],
        'notif_push_activo'  => (bool) $e['notif_push_activo'],
        'notif_email_activo' => (bool) $e['notif_email_activo'],
    ];
}

// ── GET: devolver configuración actual ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    jsonResponse([
        'success'       => true,
        'configuracion' => formatearConfiguracion($emprendimiento),
    ]);
}

// ── PUT: actualizar configuración ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $datos = jsonInput();

    $campos = [];
    $params = ['id' => $emprendimientoId];

    if (array_key_exists('horario_apertura', $datos)) {
        if (!preg_match('/^\d{2}:\d{2}$/', (string) $datos['horario_apertura'])) {
            jsonResponse(['error' => 'horario_apertura inválido. Formato esperado HH:MM.'], 400);
        }
        $campos[] = 'horario_apertura = :horario_apertura';
        $params['horario_apertura'] = $datos['horario_apertura'] . ':00';
    }

    if (array_key_exists('horario_cierre', $datos)) {
        if (!preg_match('/^\d{2}:\d{2}$/', (string) $datos['horario_cierre'])) {
            jsonResponse(['error' => 'horario_cierre inválido. Formato esperado HH:MM.'], 400);
        }
        $campos[] = 'horario_cierre = :horario_cierre';
        $params['horario_cierre'] = $datos['horario_cierre'] . ':00';
    }

    if (array_key_exists('pedido_minimo', $datos)) {
        if (!is_numeric($datos['pedido_minimo']) || (float) $datos['pedido_minimo'] < 0) {
            jsonResponse(['error' => 'pedido_minimo debe ser un número mayor o igual a 0.'], 400);
        }
        $campos[] = 'pedido_minimo = :pedido_minimo';
        $params['pedido_minimo'] = (float) $datos['pedido_minimo'];
    }

    if (array_key_exists('radio_entrega_km', $datos)) {
        if (!is_numeric($datos['radio_entrega_km']) || (float) $datos['radio_entrega_km'] <= 0) {
            jsonResponse(['error' => 'radio_entrega_km debe ser un número mayor a 0.'], 400);
        }
        $campos[] = 'radio_entrega_km = :radio_entrega_km';
        $params['radio_entrega_km'] = (float) $datos['radio_entrega_km'];
    }

    if (array_key_exists('acepta_pedidos', $datos)) {
        $campos[] = 'acepta_pedidos = :acepta_pedidos';
        $params['acepta_pedidos'] = !empty($datos['acepta_pedidos']) ? 1 : 0;
    }

    if (array_key_exists('notif_push_activo', $datos)) {
        $campos[] = 'notif_push_activo = :notif_push_activo';
        $params['notif_push_activo'] = !empty($datos['notif_push_activo']) ? 1 : 0;
    }

    if (array_key_exists('notif_email_activo', $datos)) {
        $campos[] = 'notif_email_activo = :notif_email_activo';
        $params['notif_email_activo'] = !empty($datos['notif_email_activo']) ? 1 : 0;
    }

    if (empty($campos)) {
        jsonResponse(['error' => 'No se envió ningún campo válido para actualizar.'], 400);
    }

    $sql = 'UPDATE emprendimientos SET ' . implode(', ', $campos) . ' WHERE id = :id';
    $stmtUpdate = $pdo->prepare($sql);
    $stmtUpdate->execute($params);

    $stmt->execute(['uid' => $usuario['id']]);
    $emprendimientoActualizado = $stmt->fetch();

    jsonResponse([
        'success'       => true,
        'configuracion' => formatearConfiguracion($emprendimientoActualizado),
    ]);
}

jsonResponse(['error' => 'Método no permitido. Usa GET o PUT.'], 405);
