<?php
session_start();
require 'db.php';
require 'functions.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $titulo = $_POST['titulo'];
    $descripcion = $_POST['descripcion'];

    $stmt = $pdo->prepare('INSERT INTO tareas (usuario_id, titulo, descripcion) VALUES (?, ?, ?)');
    $stmt->execute([$_SESSION['usuario_id'], $titulo, $descripcion]);

    echo "Tarea creada exitosamente.";
}

// Obtener tareas existentes
$stmt = $pdo->prepare('SELECT * FROM tareas WHERE usuario_id = ? ORDER BY fecha_creacion DESC');
$stmt->execute([$_SESSION['usuario_id']]);
$tareas = $stmt->fetchAll();
?>

<h2>Crear Tarea</h2>
<form method="post">
    Título: <input type="text" name="titulo" required><br><br>
    Descripción:<br>
    <textarea name="descripcion" rows="5" cols="30"></textarea><br><br>
    <button type="submit">Crear Tarea</button>
</form>

<h2>Tus Tareas</h2>
<ul>
    <?php foreach ($tareas as $tarea): ?>
        <li>
            <strong><?php echo htmlspecialchars($tarea['titulo']); ?></strong><br>
            <?php echo nl2br(htmlspecialchars($tarea['descripcion'])); ?><br>
            <em><?php echo $tarea['fecha_creacion']; ?></em>
        </li>
        <hr>
    <?php endforeach; ?>
</ul>

<a href="logout.php">Cerrar Sesión</a>