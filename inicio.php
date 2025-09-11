<?php
session_start();
include('database.php');

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$errores = [];

// --- CRUD TAREAS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nueva_tarea'])) {
    $titulo = trim($_POST['titulo'] ?? '');
    if (empty($titulo)) {
        $errores[] = "El título de la tarea no puede estar vacío.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO tareas (titulo, creador_id, asignado_id, estado) VALUES (?, ?, ?, 'todo')");
        $stmt->execute([$titulo, $usuario_id, $usuario_id]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_tarea'])) {
    $id = intval($_POST['tarea_id']);
    $titulo = trim($_POST['titulo'] ?? '');
    if (empty($titulo)) {
        $errores[] = "El título de la tarea no puede estar vacío.";
    } else {
        $stmt = $pdo->prepare("UPDATE tareas SET titulo=? WHERE id=? AND asignado_id=?");
        $stmt->execute([$titulo, $id, $usuario_id]);
    }
}

// Completar / Eliminar tarea
if (isset($_GET['completar'])) {
    $id = intval($_GET['completar']);
    $stmt = $pdo->prepare("UPDATE tareas SET estado = 'done' WHERE id = ? AND asignado_id = ?");
    $stmt->execute([$id, $usuario_id]);
}
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    $stmt = $pdo->prepare("DELETE FROM tareas WHERE id = ? AND asignado_id = ?");
    $stmt->execute([$id, $usuario_id]);
}

// Consultar tareas
$stmt = $pdo->prepare("SELECT * FROM tareas WHERE asignado_id = ? ORDER BY creado_en DESC");
$stmt->execute([$usuario_id]);
$tareas = $stmt->fetchAll();

$tarea_editar = null;
if (isset($_GET['editar'])) {
    $id = intval($_GET['editar']);
    $stmt = $pdo->prepare("SELECT * FROM tareas WHERE id = ? AND asignado_id = ?");
    $stmt->execute([$id, $usuario_id]);
    $tarea_editar = $stmt->fetch();
}

// --- CRUD SUBTAREAS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nueva_subtarea'])) {
    $titulo = trim($_POST['titulo'] ?? '');
    $parent_id = intval($_POST['parent_task_id'] ?? 0);
    if (empty($titulo) || $parent_id == 0) {
        $errores[] = "Debes escribir el título y seleccionar la tarea principal.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO tareas (titulo, creador_id, asignado_id, estado, parent_task_id) VALUES (?, ?, ?, 'todo', ?)");
        $stmt->execute([$titulo, $usuario_id, $usuario_id, $parent_id]);
    }
}

if (isset($_GET['completar_sub'])) {
    $id = intval($_GET['completar_sub']);
    $stmt = $pdo->prepare("UPDATE tareas SET estado = 'done' WHERE id = ? AND asignado_id = ?");
    $stmt->execute([$id, $usuario_id]);
}

if (isset($_GET['eliminar_sub'])) {
    $id = intval($_GET['eliminar_sub']);
    $stmt = $pdo->prepare("DELETE FROM tareas WHERE id = ? AND asignado_id = ?");
    $stmt->execute([$id, $usuario_id]);
}

$subtarea_editar = null;
if (isset($_GET['editar_sub'])) {
    $id = intval($_GET['editar_sub']);
    $stmt = $pdo->prepare("SELECT * FROM tareas WHERE id = ? AND asignado_id = ?");
    $stmt->execute([$id, $usuario_id]);
    $subtarea_editar = $stmt->fetch();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_subtarea'])) {
    $id = intval($_POST['subtarea_id']);
    $titulo = trim($_POST['titulo'] ?? '');
    if (empty($titulo)) {
        $errores[] = "El título de la subtarea no puede estar vacío.";
    } else {
        $stmt = $pdo->prepare("UPDATE tareas SET titulo=? WHERE id=? AND asignado_id=?");
        $stmt->execute([$titulo, $id, $usuario_id]);
    }
}

// --- SUBIR ARCHIVOS ---
$carpetaDestino = "uploads/";
if (!is_dir($carpetaDestino)) {
    mkdir($carpetaDestino, 0755, true);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["subir_archivo"])) {
    if (isset($_FILES["archivo"]) && $_FILES["archivo"]["error"] == 0) {
        $nombreArchivo = basename($_FILES["archivo"]["name"]);
        $rutaDestino = $carpetaDestino . $nombreArchivo;

        $tipoArchivo = strtolower(pathinfo($rutaDestino, PATHINFO_EXTENSION));
        $tamanioMaximo = 5 * 1024 * 1024; // 5MB

        if ($_FILES["archivo"]["size"] > $tamanioMaximo) {
            $errores[] = "El archivo es demasiado grande. Máximo 5MB.";
        } elseif (!in_array($tipoArchivo, ["jpg", "jpeg", "png", "pdf", "txt"])) {
            $errores[] = "Solo se permiten archivos JPG, PNG, PDF y TXT.";
        } else {
            if (move_uploaded_file($_FILES["archivo"]["tmp_name"], $rutaDestino)) {
                $mensaje = "El archivo " . htmlspecialchars($nombreArchivo) . " se ha subido correctamente.";
            } else {
                $errores[] = "Hubo un error al subir el archivo.";
            }
        }
    } else {
        $errores[] = "No se envió ningún archivo o hubo un error en la subida.";
    }
}
?>


<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Gestor de Tareas</title>
  <link rel="stylesheet" href="style/todo.css" />
</head>
<body>
  <?php include('header.php'); ?>
  <main class="todo-container">
    <h1>Mis Tareas</h1>

    <!-- Errores -->
    <?php foreach ($errores as $error): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endforeach; ?>

    <!-- Mensaje de subida de archivos -->
    <?php if (!empty($mensaje)): ?>
      <div class="success"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <!-- Formulario editar subtarea -->
    <?php if ($subtarea_editar): ?>
      <form class="task-form2" method="post">
        <input type="hidden" name="subtarea_id" value="<?= $subtarea_editar['id'] ?>">
        <input type="text" name="titulo" placeholder="Editar subtarea..." required value="<?= htmlspecialchars($subtarea_editar['titulo']) ?>" />
        <button type="submit" name="editar_subtarea">Guardar cambios</button>
        <a href="inicio.php">Cancelar</a>
      </form>
    <?php endif; ?>

    <!-- Formulario editar tarea -->
    <?php if ($tarea_editar): ?>
      <form class="task-form" method="post">
        <input type="hidden" name="tarea_id" value="<?= $tarea_editar['id'] ?>">
        <input type="text" name="titulo" placeholder="Editar título..." required value="<?= htmlspecialchars($tarea_editar['titulo']) ?>" />
        <button type="submit" name="editar_tarea">Guardar cambios</button>
        <a href="inicio.php">Cancelar</a>
      </form>
    <?php else: ?>
      <!-- Formulario nueva tarea -->
      <form class="task-form" method="post">
        <input type="text" name="titulo" placeholder="Agregar Nueva Tarea..." />
        <button type="submit" name="nueva_tarea">Añadir</button>
      </form>
    <?php endif; ?>

    <!-- Formulario nueva subtarea -->
    <form class="task-form2" method="post">
      <select name="parent_task_id" required>
        <option value="">Selecciona tarea principal</option>
        <?php foreach ($tareas as $tarea): ?>
          <option value="<?= $tarea['id'] ?>"><?= htmlspecialchars($tarea['titulo']) ?></option>
        <?php endforeach; ?>
      </select>
      <input type="text" name="titulo" placeholder="Agregar una Subtarea..." required />
      <button type="submit" name="nueva_subtarea">Añadir</button>
    </form>

    <!-- Lista de tareas y subtareas -->
    <ul class="task-list">
      <?php foreach ($tareas as $tarea): ?>
        <li class="task<?= $tarea['estado'] === 'done' ? ' completed' : '' ?>">
          <?= htmlspecialchars($tarea['titulo']) ?>
          <a href="?editar=<?= $tarea['id'] ?>">Editar</a>
          <?php if ($tarea['estado'] !== 'done'): ?>
            <a href="?completar=<?= $tarea['id'] ?>">Completar</a>
          <?php endif; ?>
          <a href="?eliminar=<?= $tarea['id'] ?>" onclick="return confirm('¿Eliminar tarea?')">Eliminar</a>

          <?php
            $stmt_sub = $pdo->prepare("SELECT * FROM tareas WHERE parent_task_id = ?");
            $stmt_sub->execute([$tarea['id']]);
            $subtareas = $stmt_sub->fetchAll();
            if ($subtareas):
          ?>
            <ul class="subtask-list">
              <?php foreach ($subtareas as $sub): ?>
                <li class="subtask<?= $sub['estado'] === 'done' ? ' completed' : '' ?>">
                  <?= htmlspecialchars($sub['titulo']) ?>
                  <?php if ($sub['estado'] !== 'done'): ?>
                    <a href="?completar_sub=<?= $sub['id'] ?>">Completar</a>
                  <?php endif; ?>
                  <a href="?editar_sub=<?= $sub['id'] ?>">Editar</a>
                  <a href="?eliminar_sub=<?= $sub['id'] ?>" onclick="return confirm('¿Eliminar subtarea?')">Eliminar</a>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>

    <!-- Formulario subida de archivos -->
    <form method="post" enctype="multipart/form-data" class="upload-form">
      <label>Selecciona un archivo:</label>
      <input type="file" name="archivo" required>
      <button type="submit" name="subir_archivo">Subir archivo</button>
    </form>

    <!-- <a href="logout.php">Cerrar sesión</a> -->
  </main>
</body>
</html>
