<?php

namespace Pedzio\Services;

use PDO;

/**
 * Crea notificaciones in-app para un usuario. Es un servicio aislado y
 * opcional: ningún endpoint existente lo llama todavía. Para que, por
 * ejemplo, pedidos.php avise al emprendedor cuando llega un pedido nuevo,
 * basta con agregar (sin tocar el resto del archivo):
 *
 *   use Pedzio\Services\NotificacionService;
 *   (new NotificacionService($pdo))->crear(
 *       $idUsuarioEmprendedor,
 *       'pedido_nuevo',
 *       'Nuevo pedido recibido',
 *       "Pedido #{$pedidoId} por S/ {$total}",
 *       '/emprendedor/pedidos'
 *   );
 *
 * Esa integración es opcional y queda a criterio de quien mantenga
 * pedidos.php; este servicio funciona de forma completamente
 * independiente mientras tanto (las notificaciones también se pueden
 * crear a mano insertando en la tabla `notificaciones`).
 */
class NotificacionService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Crea una notificación in-app para un usuario.
     *
     * @param int         $usuarioId  Dueño de la notificación.
     * @param string      $tipo       Categoría corta, ej. 'pedido_nuevo', 'pedido_actualizado', 'sistema'.
     * @param string      $titulo     Título corto (máx. 150 caracteres).
     * @param string|null $mensaje    Detalle opcional (máx. 255 caracteres).
     * @param string|null $url        Ruta del frontend a la que debe llevar al hacer click, ej. '/emprendedor/pedidos'.
     * @return int  ID de la notificación creada.
     */
    public function crear(int $usuarioId, string $tipo, string $titulo, ?string $mensaje = null, ?string $url = null): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje, url)
             VALUES (:usuario_id, :tipo, :titulo, :mensaje, :url)'
        );
        $stmt->execute([
            'usuario_id' => $usuarioId,
            'tipo'       => substr($tipo, 0, 40),
            'titulo'     => substr($titulo, 0, 150),
            'mensaje'    => $mensaje !== null ? substr($mensaje, 0, 255) : null,
            'url'        => $url,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Igual que crear(), pero para todos los usuarios de un rol dado
     * (ej. avisar a todos los superadmins de algo).
     *
     * @param string $rol 'cliente' | 'emprendedor' | 'superadmin'
     * @return int[] IDs de las notificaciones creadas.
     */
    public function crearParaRol(string $rol, string $tipo, string $titulo, ?string $mensaje = null, ?string $url = null): array
    {
        $stmt = $this->pdo->prepare('SELECT id FROM usuarios WHERE rol = :rol AND activo = 1');
        $stmt->execute(['rol' => $rol]);
        $ids = [];
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $usuarioId) {
            $ids[] = $this->crear((int) $usuarioId, $tipo, $titulo, $mensaje, $url);
        }
        return $ids;
    }
}
