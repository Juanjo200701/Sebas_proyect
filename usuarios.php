<?php
session_start();
include("database.php");

// Solo admin puede acceder
$usuario_id = $_SESSION['usuario_id'] ?? null;
if (!$usuario_id) {
    header("Location: login.php");
    exit();
}
$stmt = $pdo->prepare("SELECT rol FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$userRow = $stmt->fetch(PDO::FETCH_ASSOC);
if (($userRow['rol'] ?? 'member') !== 'admin') {
    header("Location: inicio.php");
    exit();
}

$errores = [];
$exito = "";

// Crear usuario (solo para pruebas, normalmente solo registro)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nuevo_usuario'])) {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $rol = $_POST['rol'] ?? 'member';

    if (!$nombre || !$email || !$password) {
        $errores[] = "Todos los campos son obligatorios.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "Correo inválido.";
    } elseif (!in_array($rol, ['admin', 'member', 'viewer'])) {
        $errores[] = "Rol inválido.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errores[] = "El correo ya está registrado.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nombre, $email, $hash, $rol]);
            $exito = "Usuario creado correctamente.";
        }
    }
}

// Editar usuario (solo nombre, rol)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_usuario'])) {
    $id = intval($_POST['usuario_id']);
    $nombre = trim($_POST['nombre'] ?? '');
    $rol = $_POST['rol'] ?? 'member';
    if (!$nombre) {
        $errores[] = "El nombre es obligatorio.";
    } elseif (!in_array($rol, ['admin', 'member', 'viewer'])) {
        $errores[] = "Rol inválido.";
    } else {
        $stmt = $pdo->prepare("UPDATE usuarios SET nombre=?, rol=? WHERE id=?");
        $stmt->execute([$nombre, $rol, $id]);
        $exito = "Usuario actualizado.";
    }
}

// Eliminar usuario
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    if ($id !== $usuario_id) { // No puede eliminarse a sí mismo
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id=?");
        $stmt->execute([$id]);
        $exito = "Usuario eliminado.";
    } else {
        $errores[] = "No puedes eliminar tu propio usuario.";
    }
}

// Obtener usuarios
$stmt = $pdo->query("SELECT id, nombre, email, rol FROM usuarios ORDER BY id ASC");
$usuarios = $stmt->fetchAll();

// Si se va a editar, obtener datos del usuario
$usuario_editar = null;
if (isset($_GET['editar'])) {
    $id = intval($_GET['editar']);
    $stmt = $pdo->prepare("SELECT id, nombre, email, rol FROM usuarios WHERE id=?");
    $stmt->execute([$id]);
    $usuario_editar = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestionar Usuarios</title>
    <link rel="stylesheet" href="style/usuarios.css">
</head>
<body>
    <?php include('header.php'); ?>
    <main class="usuarios-container">
        <h1>Usuarios</h1>
        <?php foreach ($errores as $error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endforeach; ?>
        <?php if ($exito): ?>
            <div class="exito"><?= htmlspecialchars($exito) ?></div>
        <?php endif; ?>

        <!-- Formulario para agregar o editar usuario -->
        <form method="post" class="usuario-form">
            <input type="hidden" name="usuario_id" value="<?= $usuario_editar['id'] ?? '' ?>">
            <input type="text" name="nombre" placeholder="Nombre" required value="<?= htmlspecialchars($usuario_editar['nombre'] ?? '') ?>">
            <input type="email" name="email" placeholder="Correo" required value="<?= htmlspecialchars($usuario_editar['email'] ?? '') ?>" <?= $usuario_editar ? 'readonly' : '' ?>>
            <select name="rol" required>
                <option value="admin" <?= ($usuario_editar['rol'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                <option value="member" <?= ($usuario_editar['rol'] ?? '') === 'member' ? 'selected' : '' ?>>Member</option>
                <option value="viewer" <?= ($usuario_editar['rol'] ?? '') === 'viewer' ? 'selected' : '' ?>>Viewer</option>
            </select>
            <?php if ($usuario_editar): ?>
                <button type="submit" name="editar_usuario">Guardar cambios</button>
                <a href="usuarios.php">Cancelar</a>
            <?php else: ?>
                <input type="password" name="password" placeholder="Contraseña" required>
                <button type="submit" name="nuevo_usuario">Agregar usuario</button>
            <?php endif; ?>
        </form>

        <!-- Lista de usuarios -->
        <ul class="usuarios-lista">
            <?php foreach ($usuarios as $u): ?>
                <li>
                    <strong><?= htmlspecialchars($u['nombre']) ?></strong>
                    <span><?= htmlspecialchars($u['email']) ?></span>
                    <span>(<?= htmlspecialchars($u['rol']) ?>)</span>
                    <a href="?editar=<?= $u['id'] ?>">Editar</a>
                    <?php if ($u['id'] !== $usuario_id): ?>
                        <a href="?eliminar=<?= $u['id'] ?>" onclick="return confirm('¿Eliminar este usuario?')">Eliminar</a>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </main>
</body>