<?php
session_start();
include("database.php");

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nueva_etiqueta'])){
    $nombre = trim($_POST['nombre'] ?? '');
    $color = trim($_POST['color'] ?? '#cccccc');
    if (empty($nombre)){
        $errores[] = "El nombre de la etiqueta no puede estar vacío.";
    } else{
        $stmt = $pdo->prepare("INSERT INTO etiquetas (nombre, color) VALUES (?, ?)");
        $stmt->execute([$nombre, $color]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_etiqueta'])){
    $id = intval($_POST['etiqueta_id']);
    $nombre = trim($_POST['nombre'] ?? '');
    $color = trim($_POST['color'] ?? '#cccccc');
    if (empty($nombre)){
        $errores[] = "El nombre de la etiqueta no puede estar vacío.";
    } else{
        $stmt = $pdo->prepare("UPDATE etiquetas SET nombre = ?, color = ? WHERE id = ?");
        $stmt->execute([$nombre, $color, $id]);
    }
}

if (isset($_GET['eliminar'])){
    $id = intval($_GET['eliminar']);
    $stmt = $pdo->prepare("DELETE FROM etiquetas WHERE id = ?");
    $stmt->execute([$id]);
}


$stmt = $pdo->prepare("SELECT * FROM etiquetas ORDER BY id DESC");
$stmt->execute();
$etiquetas = $stmt->fetchAll();

$etiqueta_editar = null;
if (isset($_GET['editar'])){
    $id = intval($_GET['editar']);
    $stmt = $pdo->prepare("SELECT * FROM etiquetas WHERE id = ?");
    $stmt->execute([$id]);
    $etiqueta_editar = $stmt->fetch();
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Etiquetas</title>
    <link rel="stylesheet" href="style/todo.css">
</head>
<body>
    <?php include('header.php'); ?>
    <main class="etiquetas-container">
        <h1>Etiquetas</h1>
        <?php foreach ($errores as $error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endforeach; ?>
        <form method="post" class="etiqueta-form">
            <input type="hidden" name="etiqueta_id" value="<?= $etiqueta_editar['id'] ?? '' ?>">
            <input type="text" name="nombre" placeholder="Nombre de la etiqueta" required value="<?= htmlspecialchars($etiqueta_editar['nombre'] ?? '') ?>">
            <input type="color" name="color" value="<?= htmlspecialchars($etiqueta_editar['color'] ?? '#cccccc') ?>">
            <?php if ($etiqueta_editar): ?>
                <button type="submit" name="editar_etiqueta">Guardar cambios</button>
                <a href="etiquetas.php">Cancelar</a>
            <?php else: ?>
                <button type="submit" name="nueva_etiqueta">Agregar etiqueta</button>
            <?php endif; ?>
        </form>

        <!-- Lista de etiquetas -->
        <ul class="etiquetas-lista">
            <?php foreach ($etiquetas as $etiqueta): ?>
                <li>
                    <span style="display:inline-block;width:16px;height:16px;background:<?= htmlspecialchars($etiqueta['color']) ?>;border-radius:3px;margin-right:8px;vertical-align:middle;"></span>
                    <strong><?= htmlspecialchars($etiqueta['nombre']) ?></strong>
                        <a href="?editar=<?= $etiqueta['id'] ?>"class="btn-etiqueta-editar">Editar</a>
                        <a href="?eliminar=<?= $etiqueta['id'] ?>"class="btn-etiqueta-eliminar">Eliminar</a>
                </li>
            <?php endforeach; ?>
        </ul>
    </main>
</body>
</html>
