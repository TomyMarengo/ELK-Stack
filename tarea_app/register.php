<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    $stmt = $pdo->prepare('INSERT INTO usuarios (email, password) VALUES (?, ?)');
    try {
        $stmt->execute([$email, $password]);
        echo "Usuario registrado exitosamente. <a href='login.php'>Iniciar sesión</a>";
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>

<h2>Registro</h2>
<form method="post">
    Correo Electrónico: <input type="email" name="email" required><br><br>
    Contraseña: <input type="password" name="password" required><br><br>
    <button type="submit">Registrar</button>
</form>