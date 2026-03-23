<?php
$host = 'localhost';
$db   = 'dev_cursos';
$user = 'root';
$pass = 'Usach.-2025';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     // En un entorno de producción, no deberías mostrar el error detallado
     // throw new \PDOException($e->getMessage(), (int)$e->getCode());
     die("Error de conexión a la base de datos: " . $e->getMessage());
}
?>
