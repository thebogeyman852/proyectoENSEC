<?php
$variables = [
    'INNOMADE_SMTP_HOST',
    'INNOMADE_SMTP_PORT',
    'INNOMADE_SMTP_USER',
    'INNOMADE_SMTP_PASSWORD',
    'INNOMADE_SMTP_FROM',
    'INNOMADE_SMTP_FROM_NAME'
];

foreach ($variables as $variable) {
    $valor = getenv($variable);

    if ($valor === false && isset($_SERVER[$variable])) {
        $valor = $_SERVER[$variable];
    }

    echo htmlspecialchars($variable) . ': ';
    echo ($valor !== false && $valor !== '')
        ? 'CONFIGURADA'
        : 'NO DETECTADA';

    echo '<br>';
}