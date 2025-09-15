<?php
session_start();
include("database.php");
?>
<head>
  <link rel="stylesheet" href="style/todo.css" />
</head>
<header class="header">
    <nav class="">
      <!-- <a href="#">Inicio</a> -->
      <a href="perfil.php"class="perfil">Perfil</a>
      <a href="etiquetas.php" class="etiquetas">Gestionar Etiquetas</a>
      <a href="proyectos.php" class="proyectos">Gestionar Proyectos</a>
      <button id="cerrarSesionBtn" class="cerrar-sesion"><a href="logout.php">Cerrar sesión</a></button>
    </nav>
  </header>
