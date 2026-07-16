<?php
/**
 * POST /api/usuarios/foto.php
 *
 * Sube o reemplaza la foto de perfil del usuario autenticado (cualquier rol).
 * Request: multipart/form-data con un campo "foto".
 *
 * Formatos aceptados: jpg, jpeg, png, webp (PEDZIO_EXTENSIONES_IMAGEN_PERMITIDAS).
 * Tamaño máximo: PEDZIO_UPLOADS_MAX_BYTES (configurable con UPLOADS_MAX_MB en .env).
 *
 * Respuesta (200):
 *   { "success": true, "foto_url": "http://localhost:8000/uploads/perfiles/xxxx.jpg" }
 *
 * Error (400 / 401 / 413 / 500):
 *   { "error": "..." }
 */

require_once __DIR__ . '/../config.php';

use Pedzio\Models\Usuario;

// ── 1. Solo POST ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Método no permitido. Usa POST.'], 405);
}

// ── 2. Cualquier usuario autenticado puede cambiar SU PROPIA foto ──────────────
$usuario = requireAuth();

if (empty($_FILES['foto'])) {
    jsonResponse(['error' => 'No se recibió ningún archivo. Usa el campo "foto".'], 400);
}

$archivo = $_FILES['foto'];

// ── 3. Errores de subida propios de PHP (tamaño, subida incompleta, etc.) ──────
if ($archivo['error'] !== UPLOAD_ERR_OK) {
    $mensajes = [
        UPLOAD_ERR_INI_SIZE  => 'El archivo supera el tamaño máximo permitido por el servidor.',
        UPLOAD_ERR_FORM_SIZE => 'El archivo supera el tamaño máximo permitido.',
        UPLOAD_ERR_PARTIAL   => 'La subida se interrumpió. Intenta de nuevo.',
        UPLOAD_ERR_NO_FILE   => 'No se seleccionó ningún archivo.',
    ];
    jsonResponse(['error' => $mensajes[$archivo['error']] ?? 'Error al subir el archivo.'], 400);
}

// ── 4. Tamaño máximo (además del límite que ya aplica php.ini) ─────────────────
if ($archivo['size'] > PEDZIO_UPLOADS_MAX_BYTES) {
    $maxMb = (int) round(PEDZIO_UPLOADS_MAX_BYTES / 1024 / 1024);
    jsonResponse(['error' => "La imagen supera el máximo de {$maxMb} MB."], 413);
}

// ── 5. Formato real del archivo ─────────────────────────────────────────────────
// No confiamos en la extensión del nombre ni en el Content-Type que manda el
// navegador (ambos los puede falsear quien suba el archivo): leemos el tipo
// MIME real de los primeros bytes del archivo.
$mimePermitidos = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];

$finfo    = new finfo(FILEINFO_MIME_TYPE);
$mimeReal = $finfo->file($archivo['tmp_name']);

if (!isset($mimePermitidos[$mimeReal])) {
    $formatos = implode(', ', PEDZIO_EXTENSIONES_IMAGEN_PERMITIDAS);
    jsonResponse(['error' => "Formato no permitido. Usa: {$formatos}."], 400);
}

$extension = $mimePermitidos[$mimeReal];

// ── 6. Guardar con nombre no adivinable ─────────────────────────────────────────
if (!is_dir(PEDZIO_RUTA_UPLOADS_PERFILES) && !mkdir(PEDZIO_RUTA_UPLOADS_PERFILES, 0775, true)) {
    jsonResponse(['error' => 'No se pudo preparar la carpeta de uploads.'], 500);
}

$nombreArchivo = nombreArchivoSeguro($extension);
$rutaDestino   = PEDZIO_RUTA_UPLOADS_PERFILES . '/' . $nombreArchivo;

if (!move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
    jsonResponse(['error' => 'No se pudo guardar la imagen. Intenta de nuevo.'], 500);
}

// ── 7. Actualizar BD y limpiar la foto anterior (si había una) ─────────────────
$pdo           = obtenerConexion();
$modeloUsuario = new Usuario($pdo);

$datosPrevios     = $modeloUsuario->buscarPorId($usuario['id']);
$fotoAnteriorPath = $datosPrevios['foto_url'] ?? null;

$rutaRelativa = '/uploads/perfiles/' . $nombreArchivo;
$modeloUsuario->actualizarFoto($usuario['id'], $rutaRelativa);

if ($fotoAnteriorPath && str_starts_with($fotoAnteriorPath, '/uploads/perfiles/')) {
    $rutaFisicaAnterior = PEDZIO_RUTA_UPLOADS_PERFILES . '/' . basename($fotoAnteriorPath);
    if (is_file($rutaFisicaAnterior)) {
        @unlink($rutaFisicaAnterior); // best-effort: si falla el borrado no es motivo de error
    }
}

jsonResponse([
    'success'  => true,
    'foto_url' => pedzio_url_absoluta($rutaRelativa),
]);
