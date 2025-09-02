<?php
include("databse.php");

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $email = $_POST['email'] ?? null;
    $password = $_POST['password'] ?? null;

    if ($email && $password) {
        // Evita inyecciones SQL
        $email = $conexion->real_escape_string($email);

        // Consulta para buscar el usuario
        $sql = "SELECT * FROM usuarios WHERE email = '$email'";
        $resultado = $conexion->query($sql);

        if ($resultado && $resultado->num_rows == 1) {
            $usuario = $resultado->fetch_assoc();

            // Verifica la contraseña
            if (password_verify($password, $usuario['password'])) {
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_email'] = $usuario['email'];

                header("Location: dashboard.html");
                exit();
            } else {
                echo "<script>alert('Contraseña incorrecta'); window.location.href='index.php';</script>";
            }
        } else {
            echo "<script>alert('Correo no encontrado'); window.location.href='index.php';</script>";
        }
    } else {
        echo "<script>alert('Por favor, completa todos los campos.'); window.location.href='index.php';</script>";
    }
}
?>
