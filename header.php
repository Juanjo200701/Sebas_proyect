<?php
session_start();
include('database.php');

?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>header - inicio</title>
  <link rel="stylesheet" href="style/header.css">
</head>
<body>
  <header class="header">
    <nav class="nav">
      <a href="#">Inicio</a>
      <a href="#">Perfil</a>
      <button id="cerrarSesionBtn" class="cerrar-sesion"><a href="logout.php">Cerrar sesión</a></button>
    </nav>
  </header>
</body>
</html>