<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($_POST['password'], $user['password'])) {
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];

        // Obtener la IP real del cliente, considerando proxies
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip_address = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            $ip_address = $_SERVER['REMOTE_ADDR'];
        }

        // Crear un array con los datos a loguear
        $log_data = [
            'timestamp' => date('c'),
            'ip_address' => $ip_address,
            'email' => $user['email'],
            'result' => 'success'
        ];

        // Convertir a JSON
        $log_entry = json_encode($log_data) . PHP_EOL;

        // Ruta al archivo de log personalizado
        $log_file = '/var/log/myapp/login.json';

        // Registrar el log
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
        header('Location: create_task.php');
        exit();
    } else {
        // Obtener la IP real del cliente, considerando proxies
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip_address = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            $ip_address = $_SERVER['REMOTE_ADDR'];
        }

        // Crear un array con los datos a loguear
        $log_data = [
            'timestamp' => date('c'),
            'ip_address' => $ip_address,
            'email' => $email,
            'result' => 'failed'
        ];

        // Convertir a JSON
        $log_entry = json_encode($log_data) . PHP_EOL;

        // Ruta al archivo de log personalizado
        $log_file = '/var/log/myapp/login.json';

        // Registrar el log
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
        echo "Credenciales incorrectas.";
    }
}
?>

<h2>Inicio de Sesión</h2>
<form method="post">
    Correo Electrónico: <input type="email" name="email" required><br><br>
    Contraseña: <input type="password" name="password" required><br><br>
    <button type="submit">Iniciar Sesión</button>
</form>