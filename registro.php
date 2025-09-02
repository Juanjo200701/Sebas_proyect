<?php
session_start();
include("database.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $clave = trim($_POST['password'] ?? '');

    $errores = [];

    // Validaciones
    if (empty($nombre)) {
        $errores[] = 'El nombre es obligatorio.';
    }
    if (empty($email)) {
        $errores[] = 'El correo electrónico es obligatorio.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El correo electrónico no es válido.';
    }
    if (empty($clave)) {
        $errores[] = 'La contraseña es obligatoria.';
    } elseif (strlen($clave) < 6) {
        $errores[] = 'La contraseña debe tener al menos 6 caracteres.';
    }

    // Si no hay errores, registrar usuario
    if (empty($errores)) {
        // Verificar si el correo ya existe
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errores[] = 'El correo ya está registrado.';
        } else {
            $hash = password_hash($clave, PASSWORD_DEFAULT);
            $rol = 'member'; // Rol por defecto
            $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$nombre, $email, $hash, $rol])) {
                header("Location: login.php");
                exit();
            } else {
                $errores[] = 'Error al registrar. Intenta de nuevo.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro - To Do</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style/registro.css">
</head>
<body>
    <div class="registro-container">
        <h2>Crear Cuenta</h2>
        <?php if (!empty($errores)): ?>
            <?php foreach ($errores as $error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endforeach; ?>
        <?php endif; ?>
        <form class='register-form' method="post">
            <input type="text" name="nombre" placeholder="Nombre completo" value="<?= htmlspecialchars($nombre ?? '') ?>" required>
            <input type="email" name="email" placeholder="Correo electrónico" value="<?= htmlspecialchars($email ?? '') ?>" required>
            <input type="password" name="password" placeholder="Contraseña (mínimo 6 caracteres)" required>
            <button type="submit">Registrarme</button>
        </form>
        <div class="login-link">
            ¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a>
        </div>
    </div>
</body>
</html>