<?php
// db.php
$host = 'localhost';
$db   = 'tarea_app';
$user = 'root';
$pass = 'Current-Root-Password';
$charset = 'utf8mb4';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
     $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass, $options);
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>

logout.php

<?php
require 'functions.php';
session_start();
session_unset();
session_destroy();

// Eliminar la cookie
setcookie("user_email", "", time() - 3600, "/");

header('Location: login.php');
exit();
?>