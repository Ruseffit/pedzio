<?php
/**
 * Bootstrap incluido al inicio de toda página en public_html/.
 * Carga configuración, sesión, autoload e includes compartidos, en el orden correcto.
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/flash.php';
