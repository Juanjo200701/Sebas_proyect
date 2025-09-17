<?php
session_start();
include('database.php');

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$id = intval($_GET['id'] ?? 0);
$mensaje = "";

// Buscar la tarea
$stmt = $pdo->prepare("SELECT * FROM tareas WHERE id = ?");
$stmt->execute([$id]);
$tarea = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tarea) {
    die("La tarea no existe.");
}

// Verifica acceso: asignado, creador o admin
$stmtUser = $pdo->prepare("SELECT rol FROM usuarios WHERE id = ?");
$stmtUser->execute([$usuario_id]);
$userRow = $stmtUser->fetch(PDO::FETCH_ASSOC);
$userRol = $userRow['rol'] ?? 'member';

if (
    $tarea['asignado_id'] != $usuario_id &&
    $tarea['creador_id'] != $usuario_id &&
    $userRol !== 'admin'
) {
    die("No tienes acceso a esta tarea.");
}

// Guardar comentario al enviar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $comentario = trim($_POST['comentario'] ?? '');

    $stmt = $pdo->prepare("UPDATE tareas SET comentario = ? WHERE id = ?");
    $stmt->execute([$comentario, $id]);

    $mensaje = "Comentario guardado correctamente.";
    $tarea['comentario'] = $comentario; // actualizar variable
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Comentario de Tarea</title>
  <link rel="stylesheet" href="style/todo.css">
</head>
<body>
  <?php include('header.php'); ?>
  <main class="todo-container">
    <h2>Comentario de: <?= htmlspecialchars($tarea['titulo']) ?></h2>

    <?php if (!empty($mensaje)): ?>
      <div class="success"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <form method="post">
      <textarea name="comentario" rows="5" style="width:100%;"><?= htmlspecialchars($tarea['comentario'] ?? '') ?></textarea>
      <br><br>
      <button type="submit">Guardar comentario</button>
      <a href="inicio.php">Volver</a>
    </form>
  </main>