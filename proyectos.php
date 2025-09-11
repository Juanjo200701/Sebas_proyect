<?php
session_start();
include("database.php");

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nuevo_proyecto'])){
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    if (empty($nombre)){
        $errores[] = "El nombre del proyecto no puede estar vacío.";
    } else{
        $stmt = $pdo->prepare("INSERT INTO proyectos (nombre, descripcion, creado_por) VALUES (?, ?, ?)");
        $stmt->execute([$nombre, $descripcion, $usuario_id]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_proyecto'])){
    $id = intval($_POST['proyecto_id']);
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    if (empty($nombre)){
        $errores[] = "El nombre del proyecto no puede estar vacío.";
    } else{
        $stmt = $pdo->prepare("UPDATE proyectos SET nombre = ?, descripcion = ? WHERE id = ? AND creado_por = ?");
        $stmt->execute([$nombre, $descripcion, $id, $usuario_id]);
    }
}

if (isset($_GET['eliminar'])){
    $id = intval($_GET['eliminar']);
    $stmt = $pdo->prepare("DELETE FROM proyectos WHERE id = ? AND creado_por = ?");
    $stmt->execute([$id, $usuario_id]);
}

$stmt = $pdo->prepare("SELECT * FROM proyectos WHERE creado_por = ? ORDER BY creado_en DESC");
$stmt->execute([$usuario_id]);
$proyectos = $stmt->fetchAll();

$proyecto_editar = null;
if (isset($_GET['editar'])){
    $id = intval($_GET['editar']);
    $stmt = $pdo->prepare("SELECT * FROM proyectos WHERE id = ? AND creado_por = ?");
    $stmt->execute([$id, $usuario_id]);
    $proyecto_editar = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Proyectos</title>
    <link rel="stylesheet" href="style/todo.css">
</head>
<body>
    <?php include('header.php'); ?>
    <main class="proyectos-container">
        <h1>Mis Proyectos</h1>
        <?php foreach ($errores as $error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endforeach; ?>

        <!-- Formulario para agregar o editar proyecto -->
        <form method="post" class="proyecto-form">
            <input type="hidden" name="proyecto_id" value="<?= $proyecto_editar['id'] ?? '' ?>">
            <input type="text" name="nombre" placeholder="Nombre del proyecto" required value="<?= htmlspecialchars($proyecto_editar['nombre'] ?? '') ?>">
            <input type="text" name="descripcion" placeholder="Descripción" value="<?= htmlspecialchars($proyecto_editar['descripcion'] ?? '') ?>">
            <?php if ($proyecto_editar): ?>
                <button type="submit" name="editar_proyecto">Guardar cambios</button>
                <a href="proyectos.php">Cancelar</a>
            <?php else: ?>
                <button type="submit" name="nuevo_proyecto">Agregar proyecto</button>
            <?php endif; ?>
        </form>
        <ul class="proyectos-lista">
            <?php foreach ($proyectos as $proyecto): ?>
                <li>
                    <strong><?= htmlspecialchars($proyecto['nombre']) ?></strong>
                    <span><?= htmlspecialchars($proyecto['descripcion']) ?></span>
                    <a href="?editar=<?= $proyecto['id'] ?>">Editar</a>
                    <a href="?eliminar=<?= $proyecto['id'] ?>" onclick="return confirm('¿Eliminar este proyecto?')">Eliminar</a>
                </li>
            <?php endforeach; ?>
        </ul>
    </main>
</body>
</html>
