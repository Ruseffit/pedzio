<?php
/**
 * GET /api/emprendimiento.php
 *   → Datos del emprendimiento del emprendedor autenticado, incluye categorías.
 *   Respuesta: { success: true, emprendimiento: { ...campos, categorias: [] } }
 *
 * PUT /api/emprendimiento.php
 *   → Actualiza datos del emprendimiento (texto + logo opcional en base64).
 *   Body JSON:
 *     {
 *       "nombre_negocio":    string (requerido),
 *       "descripcion":       string|null,
 *       "direccion":         string|null,
 *       "telefono_contacto": string|null,
 *       "categoria":         string|null,   ← nombre de la categoría principal
 *       "logo_base64":       string|null    ← "data:image/png;base64,..." (opcional)
 *     }
 *   Respuesta: { success: true, emprendimiento: { ...camposActualizados } }
 *
 * Errores comunes:
 *   400 – Validación fallida (nombre vacío, imagen muy pesada, etc.)
 *   401 – No autenticado
 *   403 – Rol no permitido
 *   404 – Emprendimiento no encontrado
 *   405 – Método no permitido
 */

require_once __DIR__ . '/config.php';

use Pedzio\Models\Emprendimiento;
use Pedzio\Models\Categoria;

// ── Solo emprendedores ────────────────────────────────────────────────────────
$usuario = requireAuth(['emprendedor']);
$pdo     = obtenerConexion();

$modeloEmprendimiento = new Emprendimiento($pdo);
$modeloCategoria      = new Categoria($pdo);

// ════════════════════════════════════════════════════════════════════════════
// GET → datos del emprendimiento + categorías
// ════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $emp = $modeloEmprendimiento->buscarPorUsuarioId($usuario['id']);

    if (!$emp) {
        jsonResponse(['error' => 'No tienes un emprendimiento registrado.'], 404);
    }

    $categorias = $modeloCategoria->listarPorEmprendimiento((int) $emp['id']);

    jsonResponse([
        'success'        => true,
        'emprendimiento' => [
            'id'                => (int) $emp['id'],
            'nombre_negocio'    => $emp['nombre_negocio'],
            'descripcion'       => $emp['descripcion']       ?? null,
            'direccion'         => $emp['direccion']         ?? null,
            'telefono_contacto' => $emp['telefono_contacto'] ?? null,
            'logo_path'         => $emp['logo_path']         ?? null,
            'activo'            => (bool) $emp['activo'],
            'categorias'        => array_values(
                array_map(fn($c) => ['id' => (int) $c['id'], 'nombre' => $c['nombre']], $categorias)
            ),
        ],
    ]);
}

// ════════════════════════════════════════════════════════════════════════════
// PUT → actualizar emprendimiento
// ════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {

    // ── 1. Verificar que existe el emprendimiento ─────────────────────────
    $emp = $modeloEmprendimiento->buscarPorUsuarioId($usuario['id']);
    if (!$emp) {
        jsonResponse(['error' => 'No tienes un emprendimiento registrado.'], 404);
    }
    $empId = (int) $emp['id'];

    // ── 2. Leer body ──────────────────────────────────────────────────────
    $body = jsonInput();

    $nombreNegocio     = trim($body['nombre_negocio']    ?? '');
    $descripcion       = trim($body['descripcion']       ?? '') ?: null;
    $direccion         = trim($body['direccion']         ?? '') ?: null;
    $telefonoContacto  = trim($body['telefono_contacto'] ?? '') ?: null;
    $categoriaNombre   = trim($body['categoria']         ?? '') ?: null;
    $logoBase64        = $body['logo_base64'] ?? null;

    // ── 3. Validaciones básicas ───────────────────────────────────────────
    if ($nombreNegocio === '') {
        jsonResponse(['error' => 'El nombre del negocio es obligatorio.'], 400);
    }
    if (mb_strlen($nombreNegocio) > 150) {
        jsonResponse(['error' => 'El nombre del negocio no puede superar los 150 caracteres.'], 400);
    }
    if ($telefonoContacto !== null && mb_strlen($telefonoContacto) > 20) {
        jsonResponse(['error' => 'El teléfono no puede superar los 20 caracteres.'], 400);
    }

    // ── 4. Procesar logo (base64 → archivo) ──────────────────────────────
    $logoPath = $emp['logo_path'] ?? null; // mantener el existente por defecto

    if ($logoBase64 !== null && $logoBase64 !== '') {
        // Formato esperado: "data:image/jpeg;base64,/9j/..."
        if (!preg_match('/^data:(image\/(?:jpeg|png|webp|gif));base64,(.+)$/i', $logoBase64, $m)) {
            jsonResponse(['error' => 'Formato de imagen inválido. Se acepta JPEG, PNG, WEBP o GIF.'], 400);
        }

        $mime       = strtolower($m[1]);
        $datos      = base64_decode($m[2], strict: true);

        if ($datos === false) {
            jsonResponse(['error' => 'La imagen enviada no es base64 válido.'], 400);
        }

        // Límite de 2 MB
        $maxBytes = 2 * 1024 * 1024;
        if (strlen($datos) > $maxBytes) {
            jsonResponse(['error' => 'La imagen supera el tamaño máximo de 2 MB.'], 400);
        }

        // Carpeta de logos dentro de public_html
        $extensiones = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
        $ext         = $extensiones[$mime] ?? 'jpg';

        $dirLogos = __DIR__ . '/../uploads/logos/';
        if (!is_dir($dirLogos)) {
            mkdir($dirLogos, 0755, true);
        }

        $nombreArchivo = 'emp_' . $empId . '_' . time() . '.' . $ext;
        $rutaAbsoluta  = $dirLogos . $nombreArchivo;

        if (file_put_contents($rutaAbsoluta, $datos) === false) {
            jsonResponse(['error' => 'No se pudo guardar la imagen en el servidor.'], 500);
        }

        // Borrar logo anterior si existe y es distinto al nuevo
        if ($logoPath && $logoPath !== 'uploads/logos/' . $nombreArchivo) {
            $rutaAnterior = __DIR__ . '/../' . $logoPath;
            if (file_exists($rutaAnterior)) {
                @unlink($rutaAnterior);
            }
        }

        $logoPath = 'uploads/logos/' . $nombreArchivo;
    }

    // ── 5. UPDATE en la tabla emprendimientos ─────────────────────────────
    $stmt = $pdo->prepare(
        'UPDATE emprendimientos
         SET nombre_negocio    = :nombre,
             descripcion       = :descripcion,
             direccion         = :direccion,
             telefono_contacto = :telefono,
             logo_path         = :logo_path,
             actualizado_en    = NOW()
         WHERE id = :id AND usuario_id = :uid'
    );

    $stmt->execute([
        'nombre'      => $nombreNegocio,
        'descripcion' => $descripcion,
        'direccion'   => $direccion,
        'telefono'    => $telefonoContacto,
        'logo_path'   => $logoPath,
        'id'          => $empId,
        'uid'         => $usuario['id'],
    ]);

    // ── 6. Gestionar categoría principal ─────────────────────────────────
    // La página envía una sola categoría principal como string.
    // Estrategia: si cambia, eliminar la primera categoría existente y crear la nueva.
    if ($categoriaNombre !== null) {
        $categoriasActuales = $modeloCategoria->listarPorEmprendimiento($empId);

        // Si ya existe una categoría con ese nombre, no hacer nada
        $nombres = array_column($categoriasActuales, 'nombre');
        if (!in_array($categoriaNombre, $nombres, true)) {
            // Borrar la primera si existe (categoría principal)
            if (!empty($categoriasActuales)) {
                $modeloCategoria->eliminar((int) $categoriasActuales[0]['id'], $empId);
            }
            $modeloCategoria->crear($empId, $categoriaNombre);
        }
    }

    // ── 7. Devolver datos actualizados ────────────────────────────────────
    $empActualizado  = $modeloEmprendimiento->buscarPorId($empId);
    $categoriasNuevo = $modeloCategoria->listarPorEmprendimiento($empId);

    jsonResponse([
        'success'        => true,
        'emprendimiento' => [
            'id'                => (int) $empActualizado['id'],
            'nombre_negocio'    => $empActualizado['nombre_negocio'],
            'descripcion'       => $empActualizado['descripcion']       ?? null,
            'direccion'         => $empActualizado['direccion']         ?? null,
            'telefono_contacto' => $empActualizado['telefono_contacto'] ?? null,
            'logo_path'         => $empActualizado['logo_path']         ?? null,
            'categorias'        => array_values(
                array_map(fn($c) => ['id' => (int) $c['id'], 'nombre' => $c['nombre']], $categoriasNuevo)
            ),
        ],
    ]);
}

// ── Método no permitido ───────────────────────────────────────────────────────
jsonResponse(['error' => 'Método no permitido. Usa GET o PUT.'], 405);
