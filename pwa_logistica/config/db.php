<?php
// No mostrar errores/warnings de PHP en la respuesta (pueden revelar rutas del
// servidor y detalles internos); quedan igual disponibles en el log de errores.
// Se configura aquí (no en .htaccess) porque funciona sin importar si el
// hosting corre PHP como módulo de Apache o como FPM/CGI (ej. Hostinger).
ini_set('display_errors', '0');
ini_set('log_errors', '1');

$host = 'localhost';
$db   = 'u916414417_sisLogistica';
$user = 'u916414417_sisLogistica';
$pass = 'Cesar.lopez113GBT';

$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>