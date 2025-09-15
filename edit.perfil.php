<?php
session_start();
include("database.php");

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$errores = [];

// Solo procesamos si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password_actual = $_POST['current-password'] ?? '';
    $password_nueva = $_POST['new-password'] ?? '';
    $password_confirmar = $_POST['confirm-password'] ?? '';

    // 1. Traer la contraseña actual del usuario
    $stmt = $pdo->prepare("SELECT password FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        $errores[] = "Usuario no encontrado.";
    } else {
        // 2. Verificar la contraseña actual
        if (!password_verify($password_actual, $usuario['password'])) {
            $errores[] = "La contraseña actual es incorrecta.";
        }
    }

    // 3. Si no hay errores hasta aquí, actualizamos datos
    if (empty($errores)) {
        $updates = [];
        $params = [];

        // Actualizar correo si se envió uno
        if (!empty($email)) {
            $updates[] = "email = ?";
            $params[] = $email;
        }

        // Si el usuario quiere cambiar su contraseña
        if (!empty($password_nueva)) {
            if ($password_nueva !== $password_confirmar) {
                $errores[] = "La nueva contraseña y su confirmación no coinciden.";
            } else {
                $password_hash = password_hash($password_nueva, PASSWORD_DEFAULT);
                $updates[] = "password = ?";
                $params[] = $password_hash;
            }
        }

        // Si subió una foto de perfil
        if (!empty($_FILES['profile-photo']['name'])) {
            $directorio = "uploads/";
            if (!is_dir($directorio)) {
                mkdir($directorio, 0777, true);
            }
            $archivo = $directorio . basename($_FILES["profile-photo"]["name"]);
            if (move_uploaded_file($_FILES["profile-photo"]["tmp_name"], $archivo)) {
                $updates[] = "foto = ?";
                $params[] = $archivo;
            }
        }

        // Ejecutar actualización si hay cambios
        if (empty($errores) && !empty($updates)) {
            $sql = "UPDATE usuarios SET " . implode(", ", $updates) . " WHERE id = ?";
            $params[] = $usuario_id;
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            header("Location: perfil.php");
            exit();
        } elseif (empty($updates)) {
            $errores[] = "No realizaste ningún cambio.";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil</title>
    <link rel="stylesheet" href="style/todo.css">
</head>
<body>
    <div class="edit-profile-container">
        <form class="edit-profile-form" action="procesar_editar_perfil.php" method="POST" enctype="multipart/form-data">
            <h2>Editar Perfil</h2>

            <!-- Foto de perfil -->
            <label for="profile-photo">Foto de perfil</label>
            <input type="file" id="profile-photo" name="profile-photo" accept="image/*">

            <!-- Correo -->
            <label for="email">Correo</label>
            <input type="password" id="email" name="email" required>

            <!-- Contraseña actual -->
            <label for="current-password">Contraseña actual</label>
            <input type="password" id="current-password" name="current-password" required>

            <!-- Nueva contraseña -->
            <label for="new-password">Nueva contraseña</label>
            <input type="password" id="new-password" name="new-password">

            <!-- Confirmar nueva contraseña -->
            <label for="confirm-password">Confirmar nueva contraseña</label>
            <input type="password" id="confirm-password" name="confirm-password">

            <div class="edit-profile-buttons">
                <button type="submit">Guardar cambios</button>
                <button type="button" onclick="window.location.href='perfil.php'">Volver</button>
            </div>
        </form>
    </div>
</body>
</html>
