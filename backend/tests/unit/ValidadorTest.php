<?php
require_once __DIR__ . '/../bootstrap.php';

use Pedzio\Helpers\Validador;

echo "== tests/unit/ValidadorTest.php ==\n";

assertVerdadero(Validador::requerido('algo'), 'requerido() acepta texto no vacío');
assertVerdadero(!Validador::requerido('   '), 'requerido() rechaza solo espacios');
assertVerdadero(!Validador::requerido(null), 'requerido() rechaza null');

assertVerdadero(Validador::email('ana@pedzio.test'), 'email() acepta correo válido');
assertVerdadero(!Validador::email('no-es-correo'), 'email() rechaza correo inválido');

assertVerdadero(Validador::numeroPositivo('18.50'), 'numeroPositivo() acepta decimal positivo');
assertVerdadero(!Validador::numeroPositivo('0'), 'numeroPositivo() rechaza cero');
assertVerdadero(!Validador::numeroPositivo('-5'), 'numeroPositivo() rechaza negativo');
assertVerdadero(!Validador::numeroPositivo('abc'), 'numeroPositivo() rechaza texto no numérico');

assertVerdadero(Validador::passwordSegura('Pedzio2026'), 'passwordSegura() acepta letras+números de 8+ caracteres');
assertVerdadero(!Validador::passwordSegura('1234567'), 'passwordSegura() rechaza solo números cortos');
assertVerdadero(!Validador::passwordSegura('soloLetras'), 'passwordSegura() rechaza sin números');
