<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>edit perfil</title>
    <link rel="stylesheet" href="style/todo.css">
</head>
<body>
    <div class="edit-profile-container">
  <form class="edit-profile-form">
    <h2>Editar Perfil</h2>

    <!-- Foto de perfil -->
    <label for="profile-photo">Foto de perfil</label>
    <input type="file" id="profile-photo" name="profile-photo" accept="image/*">

    <!-- Contraseña actual -->
    <label for="current-password">Contraseña actual</label>
    <input type="password" id="current-password" name="current-password" required>

    <!-- Nueva contraseña -->
    <label for="new-password">Nueva contraseña</label>
    <input type="password" id="new-password" name="new-password" required>

    <!-- Confirmar nueva contraseña -->
    <label for="confirm-password">Confirmar nueva contraseña</label>
    <input type="password" id="confirm-password" name="confirm-password" required>

    <button type="submit">Guardar cambios</button>
        <button onclick="window.location.href='perfil.php'">perfil</button>

  </form>
</div>
</body>
</html>