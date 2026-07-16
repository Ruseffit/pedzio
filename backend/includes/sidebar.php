<?php
/**
 * NOTA DE ARQUITECTURA:
 * Este archivo se mantiene por compatibilidad con la estructura de carpetas
 * definida en la Fase 2 del proyecto (includes/sidebar.php era obligatorio).
 *
 * Sin embargo, el sidebar real de los paneles de emprendedor y superadmin
 * se implementó en includes/app_header.php (junto con el topbar y el layout
 * completo), porque el sidebar necesita conocer el rol de sesión, la ruta
 * activa y los datos del usuario — información que app_header.php ya recibe
 * como parte del layout que abre.
 *
 * Este archivo no se incluye en ninguna página. Se deja vacío intencionalmente
 * para no duplicar lógica de navegación en dos lugares distintos.
 */
