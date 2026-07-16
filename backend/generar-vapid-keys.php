<?php
/**
 * Genera un par de llaves VAPID para Web Push. Correr UNA sola vez desde
 * la raíz de backend/:
 *
 *   php generar-vapid-keys.php
 *
 * Copia las 2 líneas que imprime a tu backend/.env (no las subas a git).
 */

require __DIR__ . '/vendor/autoload.php';

use Minishlink\WebPush\VAPID;

$llaves = VAPID::createVapidKeys();

echo "Agrega esto a backend/.env:\n\n";
echo "VAPID_PUBLIC_KEY={$llaves['publicKey']}\n";
echo "VAPID_PRIVATE_KEY={$llaves['privateKey']}\n";
echo "VAPID_SUBJECT=mailto:contacto@pedzio.com\n\n";
echo "Y esto a frontend/.env.local:\n\n";
echo "NEXT_PUBLIC_VAPID_PUBLIC_KEY={$llaves['publicKey']}\n";
