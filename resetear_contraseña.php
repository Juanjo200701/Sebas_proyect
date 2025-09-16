<?php
require "database.php";

$mensaje = "";

if (isset($_GET["token"])) {
    $token = trim($_GET["token"]);

    // // DEBUG temporal
    // echo "<p>DEBUG: Token recibido = $token</p>";

    // buscar usuario con ese token válido
    $stmt = $conn->prepare("SELECT id, reset_expiration, reset_token FROM usuarios WHERE reset_token=?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {

        // // DEBUG temporal
        // echo "<p>DEBUG: Token en DB = " . $user['reset_token'] . "</p>";

        if (!empty($user["reset_expiration"]) && strtotime($user["reset_expiration"]) > time()) {
            
            // si el usuario envió nueva contraseña
            if ($_SERVER["REQUEST_METHOD"] === "POST") {
                $newpass = password_hash($_POST["password"], PASSWORD_DEFAULT);

                $stmt = $conn->prepare("UPDATE usuarios 
                                        SET password=?, reset_token=NULL, reset_expiration=NULL 
                                        WHERE id=?");
                $stmt->bind_param("si", $newpass, $user["id"]);
                $stmt->execute();

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