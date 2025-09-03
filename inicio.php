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

<?php
//esto es para poder subir archivos en las tareas

$carpetaDestino = "uploads/";

// Crear la carpeta si no existe
if (!is_dir($carpetaDestino)) {
    mkdir($carpetaDestino, 0755, true);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_FILES["archivo"]) && $_FILES["archivo"]["error"] == 0) {

        $nombreArchivo = basename($_FILES["archivo"]["name"]);
        $rutaDestino = $carpetaDestino . $nombreArchivo;

        // Validación básica (por ejemplo, tamaño y tipo)
        $tipoArchivo = strtolower(pathinfo($rutaDestino, PATHINFO_EXTENSION));
        $tamanioMaximo = 5 * 1024 * 1024; // 5 MB

        if ($_FILES["archivo"]["size"] > $tamanioMaximo) {
            echo "El archivo es demasiado grande. Máximo 5MB.";
        } elseif (!in_array($tipoArchivo, ["jpg", "jpeg", "png", "pdf", "txt"])) {
            echo "Solo se permiten archivos JPG, PNG, PDF y TXT.";
        } else {
            if (move_uploaded_file($_FILES["archivo"]["tmp_name"], $rutaDestino)) {
                echo "El archivo " . htmlspecialchars($nombreArchivo) . " se ha subido correctamente.";
            } else {
                echo "Hubo un error al subir el archivo.";
            }
        }
    } else {
        echo "No se envió ningún archivo o hubo un error en la subida.";
    }
} else {
    echo "Acceso no permitido.";
}
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
      <input type="text" name="titulo" placeholder="Agregar Nueva Tarea..." required />
      <button type="submit" name="nueva_tarea">Añadir</button>
    </form>
    <form class="task-form2" method="post">
      <input type="text" name="titulo" placeholder="Agregar Unas Subs Tareas..." required />
      <button type="submit" name="nueva_tarea">Añadir </button>
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
        Selecciona Un Archivo:
        <input type="file" name="archivo" required>
        <br><br>
        <input type="submit" value="Subir archivo">
    <!-- <a href="logout.php">Cerrar sesión</a> -->
  </main>
</body>
</html>
