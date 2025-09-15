<?php
session_start();
include('database.php');

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$id = intval($_GET['id'] ?? 0);

// Cargar la tarea
$stmt = $pdo->prepare("SELECT * FROM tareas WHERE id=?");
$stmt->execute([$id]);
$tarea = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tarea) {
    die("Tarea no encontrada");
}

// Actualizar tarea
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo']);
    $estado = $_POST['estado'] ?? 'todo';
    $prioridad = $_POST['prioridad'] ?? 'medium';
    $fecha_inicio = $_POST['fecha_inicio'] ?? null;
    $fecha_vencimiento = $_POST['fecha_vencimiento'] ?? null;

    $stmt = $pdo->prepare("
        UPDATE tareas 
        SET titulo=?, estado=?, prioridad=?, fecha_inicio=?, fecha_vencimiento=? 
        WHERE id=?
    ");
    $stmt->execute([$titulo, $estado, $prioridad, $fecha_inicio, $fecha_vencimiento, $id]);

    header("Location: inicio.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Editar Tarea</title>
  <link rel="stylesheet" href="style/todo.css">
</head>
<body>
  <?php include('header.php'); ?>

  <main class="todo-container">
    <h1>Editar Tarea</h1>

    <form method="post">
      <label>Título:</label>
      <input type="text" name="titulo" value="<?= htmlspecialchars($tarea['titulo']) ?>" required>

      <label>Estado:</label>
      <select name="estado">
        <option value="todo" <?= $tarea['estado']=='todo'?'selected':'' ?>>Pendiente</option>
        <option value="in_progress" <?= $tarea['estado']=='in_progress'?'selected':'' ?>>En Progreso</option>
        <option value="done" <?= $tarea['estado']=='done'?'selected':'' ?>>Completada</option>
        <option value="archived" <?= $tarea['estado']=='archived'?'selected':'' ?>>Archivada</option>
      </select>

      <label>Prioridad:</label>
      <select name="prioridad">
        <option value="low" <?= $tarea['prioridad']=='low'?'selected':'' ?>>Baja</option>
        <option value="medium" <?= $tarea['prioridad']=='medium'?'selected':'' ?>>Media</option>
        <option value="high" <?= $tarea['prioridad']=='high'?'selected':'' ?>>Alta</option>
        <option value="urgent" <?= $tarea['prioridad']=='urgent'?'selected':'' ?>>Urgente</option>
      </select>

      <label>Fecha inicio:</label>
      <input type="date" name="fecha_inicio" value="<?= htmlspecialchars($tarea['fecha_inicio'] ?? '') ?>">

      <label>Fecha vencimiento:</label>
      <input type="date" name="fecha_vencimiento" value="<?= htmlspecialchars($tarea['fecha_vencimiento'] ?? '') ?>">

      <button type="submit" class="btn-guardar-cambios">Guardar cambios</button>
      <a href="inicio.php" class="btn-cancelas">Cancelar</a>
    </form>
  </main>
</body>
</html>


