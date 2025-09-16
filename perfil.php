<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}
include("database.php");
$usuario_id = $_SESSION['usuario_id'];
$stmt = $pdo->prepare("SELECT nombre, email, foto FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>perfil</title>
    <link rel="stylesheet" href="style/todo.css">
</head>
<body>
<div class="profile-container">
  <div class="profile-header">
    <?php if (!empty($usuario['foto']) && file_exists(__DIR__ . '/' . $usuario['foto'])): ?>
  <div style="margin-bottom:12px;">
    <img src="<?= htmlspecialchars($usuario['foto']) ?>" alt="Foto perfil" width="120" style="border-radius:8px;border:1px solid #ddd;">
  </div>
<?php endif; ?>
    <?php if (isset($_SESSION['usuario_nombre'])): ?>
        <h2 class="profile-name"><?= htmlspecialchars($_SESSION['usuario_nombre']) ?></h2>
    <?php endif; ?>
    <?php if (isset($_SESSION['usuario_email'])): ?>
        <p class="profile-email"><?= htmlspecialchars($_SESSION['usuario_email']) ?></p>
    <?php endif; ?>
  </div>

  <div class="profile-actions">
    <button onclick="window.location.href='inicio.php'">Volver</button>
    <!-- <button onclick="editarPerfil()">Editar perfil</button> -->
    <button onclick="window.location.href='edit.perfil.php'">Editar perfil</button>
    <button onclick="cerrarSesion()">Cerrar sesión</button>
  </div>
</div>

</body>
</html>