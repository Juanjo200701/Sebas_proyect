<?php
session_start();
include('database.php');

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nueva_tarea'])){
  $titulo = trim($_POST['titulo'] ?? '');
  if (empty($titulo)){
    $errores[] = "El título de la tarea no puede estar vacío.";
  } else{
    $stmt = $pdo->prepare("INSERT INTO tareas (titulo, creador_id, asignado_id, estado) VALUES (?, ?, ?, 'todo')");
    $stmt->execute([$titulo, $usuario_id, $usuario_id]);
  }
}

if (isset($_GET['completar'])){
  $id = intval($_GET['completar']);
  $stmt = $pdo->prepare("UPDATE tareas SET estado = 'done' WHERE id = ? AND asignado_id = ?");
  $stmt->execute([$id, $usuario_id]);
}

if (isset($_GET['eliminar'])){
  $id = intval($_GET['eliminar']);
  $stmt = $pdo->prepare("DELETE FROM tareas WHERE id = ? AND asignado_id = ?");
  $stmt->execute([$id, $usuario_id]);
}

$stmt = $pdo->prepare("SELECT * FROM tareas WHERE asignado_id = ? ORDER BY creado_en DESC");
$stmt->execute([$usuario_id]);
$tareas = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Gestor de Tareas</title>
  <link rel="stylesheet" href="style/inicio.css" />
</head>
<body>
  <?php include('header.php'); ?>
  <main class="todo-container">
    <h1>Mis Tareas</h1>
    <?php foreach ($errores as $error): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endforeach; ?>
    <form class="task-form" method="post">
      <input type="text" name="titulo" placeholder="Agregar nueva tarea..." required />
      <button type="submit" name="nueva_tarea">Añadir</button>
    </form>
    <ul class="task-list">
      <?php foreach ($tareas as $tarea): ?>
        <li class="task<?= $tarea['estado'] === 'done' ? ' completed' : '' ?>">
          <?= htmlspecialchars($tarea['titulo']) ?>
          <?php if ($tarea['estado'] !== 'done'): ?>
            <a href="?completar=<?= $tarea['id'] ?>">Completar</a>
          <?php endif; ?>
          <a href="?eliminar=<?= $tarea['id'] ?>" onclick="return confirm('¿Eliminar tarea?')">Eliminar</a>
        </li>
      <?php endforeach; ?>
    </ul>
    <a href="logout.php">Cerrar sesión</a>
  </main>
</body>
</html>
