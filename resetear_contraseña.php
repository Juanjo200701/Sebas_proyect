<?php
require "database.php"; // Aquí debe estar $pdo

$mensaje = "";

if (isset($_GET["token"])) {
    $token = trim($_GET["token"]);

    // buscar usuario con ese token válido
    $stmt = $pdo->prepare("SELECT id, reset_expiration, reset_token FROM usuarios WHERE reset_token = ?");
    $stmt->execute([$token]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        if (!empty($usuario["reset_expiration"]) && strtotime($usuario["reset_expiration"]) > time()) {

            // si el usuario envió nueva contraseña
            if ($_SERVER["REQUEST_METHOD"] === "POST") {
                $newpass = password_hash($_POST["password"], PASSWORD_DEFAULT);

                $stmt = $pdo->prepare("UPDATE usuarios 
                                        SET password = ?, reset_token = NULL, reset_expiration = NULL 
                                        WHERE id = ?");
                $stmt->execute([$newpass, $usuario["id"]]);

                $mensaje = "✅ Contraseña actualizada correctamente. <a href='login.php'>Inicia sesión</a>";
            }

        } else {
            $mensaje = "❌ El enlace ha expirado.";
        }
    } else {
        $mensaje = "❌ Token inválido.";
    }
} else {
    $mensaje = "❌ No se recibió token.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <link rel="stylesheet" href="css/reset_password.css">
  <title>Restablecer contraseña</title>
</head>
<body>
  <div class="container">
    <h2>Restablecer contraseña</h2>
    <?php if (!empty($mensaje)): ?>
      <p class="<?= strpos($mensaje, '✅') !== false ? 'success' : 'error' ?>">
        <?= $mensaje ?>
      </p>
    <?php else: ?>
      <form method="POST">
        <label for="password">Nueva contraseña:</label>
        <input type="password" name="password" id="password" required>
        <button type="submit">Cambiar contraseña</button>
      </form>
    <?php endif; ?>
  </div>
</body>
</html>
