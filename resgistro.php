<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $clave = trim($_POST['password']);

    $errors = [];

    // Validar que los campos no estén vacíos
    if (empty($name)) {
        $errors[] = 'El nombre es obligatorio.';
    }

    if (empty($email)) {
        $errors[] = 'El correo electrónico es obligatorio.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El correo electrónico no es válido.';
    }

    if (empty($clave)) {
        $errors[] = 'La contraseña es obligatoria.';
    } elseif (strlen($clave) < 6) {
        $errors[] = 'La contraseña debe tener al menos 6 caracteres.';
    }

    // Mostrar errores o procesar el registro
    if (empty($errors)) {
        // Conexión a la base de datos
        $servername = "localhost";
        $username = "root";
        $password = "123456";
        $dbname = "sebas_db  ";

        $conexion = new mysqli($servername, $username, $password, $dbname);

        // Verificar conexión
        if ($conexion->connect_error) {
            die("Conexión fallida: " . $conexion->connect_error);
        }

        // Insertar datos en la base de datos
        $stmt = $conexion->prepare("INSERT INTO usuarios (nombre, email, password) VALUES (?, ?, ?)");
        $hashed_password = password_hash($clave, PASSWORD_DEFAULT);
        $stmt->bind_param("sss", $name, $email, $hashed_password);

        if ($stmt->execute()) {
            header("Location: index.php");
        } else {
            echo '<p style="color: red;">Error: ' . $stmt->error . '</p>';
        }

        $stmt->close();
        $conexion->close();
    } else {
        foreach ($errors as $error) {
            echo '<p style="color: red;">' . $error . '</p>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>nintengames - Register</title>
    <link rel="stylesheet" href="css/master.css" />
  </head>
  <body>
    <main class="login">
      <form action="" method="post">
        <input type="text" name="name" placeholder="Nombre" />
        <input type="text" name="email" placeholder="Correo Electrónico" />
        <input type="password" name="password" placeholder="Contraseña" />
        <button>Registrar</button>
      </form>
    </main>
  </body>
</html>
