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
    <img src="ruta-de-tu-imagen.jpg" alt="Foto de perfil" class="profile-avatar">
    <h2>Nombre del Usuario</h2>
    <p class="profile-email">usuario@email.com</p>
  </div>

  <div class="profile-actions">
    <button onclick="cerrarSesion()">Cerrar sesión</button>
    <!-- <button onclick="editarPerfil()">Editar perfil</button> -->
    <button onclick="window.location.href='edit.perfil.php'">Editar perfil</button>
    <button onclick="window.location.href='inicio.php'">inicio</button>
  </div>
</div>

</body>
</html>