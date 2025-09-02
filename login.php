<?php
session_start();
include("database.php");

$errores = [];

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $email = $_POST['email'] ?? null;
    $password = $_POST['password'] ?? null;

    if ($email && $password) {
        // Evita inyecciones SQL
        $email = $conexion->real_escape_string($email);

        // Consulta para buscar el usuario
        $sql = "SELECT * FROM usuarios WHERE email = '$email'";
        $resultado = $conexion->query($sql);

        if ($resultado && $resultado->num_rows == 1) {
            $usuario = $resultado->fetch_assoc();

            // Verifica la contraseña
            if (password_verify($password, $usuario['password'])) {
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_email'] = $usuario['email'];

                header("Location: dashboard.html");
                exit();
            } else {
                $errores[] = 'Contraseña incorrecta';
            }
        } else {
            $errores[] = 'No se encontró el usuario';
        }
    } else {
        $errores[] = 'Por favor, completa todos los campos';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
    <link rel="stylesheet" href="style/login.css">
</head>
<body>
    <div class="login-container">
        <form class="login-form">
            <h2>Iniciar Sesión</h2>
            <?php if (!empty($errores)): ?>
            <?php foreach ($errores as $error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endforeach; ?>
            <?php endif; ?>
            <input type="text" placeholder="Usuario" required>
            <input type="password" placeholder="Contraseña" required>
            <button type="submit">Entrar</button>
            <p class="register-text">¿No tienes cuenta? <a href="#">Regístrate</a></p>
        </form>
    </div>
</body>
</html>

