<?php

function cargarEnv($ruta)
{
    if (!file_exists($ruta))
        return;

    $lineas = file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lineas as $linea) {
        if (strpos(trim($linea), '#') === 0)
            continue;

        list($nombre, $valor) = explode('=', $linea, 2);

        $nombre = trim($nombre);
        $valor = trim($valor);

        $_ENV[$nombre] = $valor;
        putenv("$nombre=$valor");
    }
}