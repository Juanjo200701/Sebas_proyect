<?php
include("database.php");
// session_start();
$userRol = 'member';
if (isset($_SESSION['usuario_id'])) {
    $stmtUser = $pdo->prepare("SELECT rol FROM usuarios WHERE id = ?");
    $stmtUser->execute([$_SESSION['usuario_id']]);
    $userRow = $stmtUser->fetch(PDO::FETCH_ASSOC);
    $userRol = $userRow['rol'] ?? 'member';
}
?>
<head>
  <link rel="stylesheet" href="style/todo.css" />
</head>
<header class="header">
    <nav class="">
      <!-- <a href="#">Inicio</a> -->
      <a href="perfil.php"class="perfil">Perfil</a>
      <a href="inicio.php" class="tareas">Mis Tareas</a>
      <?php if ($userRol === 'admin'): ?>
        <a href="usuarios.php" class="usuarios">Gestionar Usuarios</a>
      <?php endif; ?>
      <a href="etiquetas.php" class="etiquetas">Gestionar Etiquetas</a>
      <a href="proyectos.php" class="proyectos">Gestionar Proyectos</a>
      <button id="cerrarSesionBtn" class="cerrar-sesion"><a href="logout.php">Cerrar sesión</a></button>
    </nav>
  </header>
