<?php

require_once __DIR__ . '/config/env.php';

// Cargar variables de entorno
cargarEnv(__DIR__ . '/.env');

/* =============================
   VARIABLES DESDE .ENV
============================= */

$host = $_ENV['DB_HOST'] ?? 'localhost';
$db = $_ENV['DB_NAME'] ?? '';
$user = $_ENV['DB_USER'] ?? '';
$pass = $_ENV['DB_PASS'] ?? '';
$charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

/* =============================
   CONEXIÓN
============================= */

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
     PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
     PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
     PDO::ATTR_EMULATE_PREPARES => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {

     // 🔥 En producción puedes cambiar esto
     die("Error de conexión a la base de datos.");
}