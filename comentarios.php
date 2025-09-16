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

// Buscar la tarea del usuario logueado
$stmt = $pdo->prepare("SELECT * FROM tareas WHERE id = ? AND asignado_id = ?");
$stmt->execute([$id, $usuario_id]);
$tarea = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tarea) {
    die("No tienes acceso a esta tarea.");
}

// Guardar comentario al enviar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $comentario = trim($_POST['comentario'] ?? '');

    $stmt = $pdo->prepare("UPDATE tareas SET comentario = ? WHERE id = ? AND asignado_id = ?");
    $stmt->execute([$comentario, $id, $usuario_id]);

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
</body>
</html>
