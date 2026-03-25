<?php

/* =============================
   CONFIG GENERAL
============================= */

function base_url() {
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    return rtrim($dir, '/');
}

/* =============================
   RUTAS DEL SISTEMA
============================= */

define('BASE_PATH', __DIR__);
define('IMG_PATH', BASE_PATH . '/landing/img/');