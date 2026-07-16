<?php

namespace Pedzio\Services;

use PDO;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

/**
 * Envía notificaciones push del navegador (Web Push API) firmadas con
 * VAPID, usando la librería minishlink/web-push-php (maneja el cifrado
 * AES128GCM y el JWT VAPID; hacerlo a mano en PHP puro es innecesariamente
 * riesgoso, así que aquí sí se depende de Composer).
 *
 * Requiere en el .env:
 *   VAPID_PUBLIC_KEY=...
 *   VAPID_PRIVATE_KEY=...
 *   VAPID_SUBJECT=mailto:contacto@pedzio.com
 *
 * Genera el par de llaves una sola vez con:
 *   php backend/generar-vapid-keys.php
 */
class PushSenderService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    private function crearWebPush(): WebPush
    {
        $publicKey = env('VAPID_PUBLIC_KEY', '');
        $privateKey = env('VAPID_PRIVATE_KEY', '');
        $subject = env('VAPID_SUBJECT', 'mailto:contacto@pedzio.com');

        if ($publicKey === '' || $privateKey === '') {
            throw new \RuntimeException(
                'Faltan VAPID_PUBLIC_KEY / VAPID_PRIVATE_KEY en el .env. ' .
                'Genera el par de llaves con: php backend/generar-vapid-keys.php'
            );
        }

        return new WebPush([
            'VAPID' => [
                'subject'    => $subject,
                'publicKey'  => $publicKey,
                'privateKey' => $privateKey,
            ],
        ]);
    }

    /**
     * Envía una notificación push a TODAS las suscripciones activas de un
     * usuario (puede tener varias: celular, laptop, etc.). Las suscripciones
     * que el navegador ya invalidó (404/410) se eliminan automáticamente.
     *
     * @return array{enviadas: int, fallidas: int, eliminadas: int}
     */
    public function enviarAUsuario(int $usuarioId, string $titulo, string $mensaje, ?string $url = null): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, endpoint, p256dh, auth FROM notificaciones_subscriptions WHERE usuario_id = :uid'
        );
        $stmt->execute(['uid' => $usuarioId]);
        $suscripciones = $stmt->fetchAll();

        if (empty($suscripciones)) {
            return ['enviadas' => 0, 'fallidas' => 0, 'eliminadas' => 0];
        }

        $webPush = $this->crearWebPush();

        $payload = json_encode([
            'titulo'  => $titulo,
            'mensaje' => $mensaje,
            'url'     => $url ?? '/',
        ], JSON_UNESCAPED_UNICODE);

        $idsPorEndpoint = [];
        foreach ($suscripciones as $s) {
            $idsPorEndpoint[$s['endpoint']] = (int) $s['id'];

            $webPush->queueNotification(
                Subscription::create([
                    'endpoint' => $s['endpoint'],
                    'keys'     => [
                        'p256dh' => $s['p256dh'],
                        'auth'   => $s['auth'],
                    ],
                ]),
                $payload
            );
        }

        $enviadas = 0;
        $fallidas = 0;
        $eliminadas = 0;

        foreach ($webPush->flush() as $reporte) {
            $endpoint = $reporte->getRequest()->getUri()->__toString();

            if ($reporte->isSuccess()) {
                $enviadas++;
                continue;
            }

            $fallidas++;

            // 404/410 = la suscripción ya no existe en el navegador (el
            // usuario desinstaló, borró datos, o revocó el permiso).
            // Se limpia de la BD para no seguir intentando enviarle.
            if ($reporte->isSubscriptionExpired() && isset($idsPorEndpoint[$endpoint])) {
                $stmtBorrar = $this->pdo->prepare('DELETE FROM notificaciones_subscriptions WHERE id = :id');
                $stmtBorrar->execute(['id' => $idsPorEndpoint[$endpoint]]);
                $eliminadas++;
            }
        }

        return ['enviadas' => $enviadas, 'fallidas' => $fallidas, 'eliminadas' => $eliminadas];
    }
}
