<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Smalot\PdfParser\Parser;

header('Content-Type: application/json');

if (!isset($_FILES['pdf'])) {
    echo json_encode(["error" => "No se envió PDF"]);
    exit;
}

$parser = new Parser();
$pdf = $parser->parseFile($_FILES['pdf']['tmp_name']);
$texto = $pdf->getText();


/* =============================
   FUNCION EXTRAER SECCION
============================= */

function extraerSeccion($texto, $inicio, $fin = null)
{
    $inicio = strtoupper($inicio);
    $fin = $fin ? strtoupper($fin) : null;

    // Permite cosas como "1. CONTEXTO", "I CONTEXTO", etc
    $regexInicio = ".*?$inicio";
    $regexFin = $fin ? "(.*?)$fin" : "(.*)";

    if (preg_match("/$regexInicio$regexFin/s", $texto, $matches)) {
        return trim($matches[1]);
    }

    return '';
}

function extraerEntre($texto, $inicio, $fin)
{
    $posInicio = strpos($texto, $inicio);
    $posFin = strpos($texto, $fin);

    if ($posInicio === false || $posFin === false) {
        return '';
    }

    $posInicio += strlen($inicio);

    return trim(substr($texto, $posInicio, $posFin - $posInicio));
}


/* =============================
   EXTRAER DATOS
============================= */

$contexto = extraerEntre($texto, "I. CONTEXTO", "OBJETIVO GENERAL");

$objetivo = extraerEntre($texto, "OBJETIVO GENERAL", "PERFIL DE EGRESO");
$perfil = extraerEntre($texto, "PERFIL DE EGRESO", "REQUISITOS PREVIOS");
$requisitos = extraerEntre($texto, "REQUISITOS PREVIOS", "ESTRUCTURA DE CONTENIDOS");

/* =============================
   LIMPIAR LISTAS
============================= */

function limpiarLista($texto)
{
    $lineas = explode("\n", $texto);

    $resultado = [];

    foreach ($lineas as $l) {

        $l = trim($l);

        if ($l == '')
            continue;

        // eliminar viñetas tipo -, •, números
        $l = preg_replace('/^[\-\•\d\.\)]+\s*/', '', $l);

        $resultado[] = $l;
    }

    return $resultado;
}

echo json_encode([
    "contexto" => $contexto,
    "objetivo" => $objetivo,
    "perfil" => limpiarLista($perfil),
    "requisitos" => limpiarLista($requisitos)
]);