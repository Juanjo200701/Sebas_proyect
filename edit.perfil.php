<?php
session_start();
include("database.php");

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$errores = [];
$exito = "";

// Configuración de uploads
$UPLOAD_DIR = __DIR__ . "/uploads/";         // directorio físico donde se guardan
$UPLOAD_URL_PREFIX = "uploads/";             // prefijo para la URL que guardaremos en DB (ajusta si lo cambias)
$MAX_FILE_SIZE = 5 * 1024 * 1024;           // 5 MB
$ALLOWED_MIME = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif'
];

// Traer datos actuales del usuario
$stmt = $pdo->prepare("SELECT email, foto, password FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$usuario) {
    die("Usuario no encontrado.");
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // campos
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password_actual = $_POST['current-password'] ?? '';
    $password_nueva = $_POST['new-password'] ?? '';
    $password_confirmar = $_POST['confirm-password'] ?? '';

    // =========================
    // 1) Validar correo (si cambió)
    // =========================
    if (!empty($email) && $email !== $usuario['email']) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errores[] = "El correo no tiene formato válido.";
        } else {
            // verificar unicidad
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id <> ?");
            $stmt->execute([$email, $usuario_id]);
            if ($stmt->fetch()) {
                $errores[] = "El correo ya está en uso por otro usuario.";
            }
        }
    }

    // =========================
    // 2) Validar cambio de contraseña (si aplica)
    // =========================
    if (!empty($password_nueva) || !empty($password_confirmar)) {
        if (empty($password_actual)) {
            $errores[] = "Debes ingresar tu contraseña actual para cambiarla.";
        } else {
            if (!password_verify($password_actual, $usuario['password'])) {
                $errores[] = "La contraseña actual es incorrecta.";
            } else {
                if ($password_nueva !== $password_confirmar) {
                    $errores[] = "La nueva contraseña y la confirmación no coinciden.";
                } elseif (strlen($password_nueva) < 6) {
                    $errores[] = "La nueva contraseña debe tener al menos 6 caracteres.";
                }
            }
        }
    }

    // =========================
    // 3) Validar y procesar foto (si se subió)
    // =========================
    $nueva_ruta_en_db = null;
    if (isset($_FILES['profile-photo']) && $_FILES['profile-photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $fileErr = $_FILES['profile-photo']['error'];
        // errores comunes
        if ($fileErr !== UPLOAD_ERR_OK) {
            $msg = "Error en la subida (código: $fileErr). ";
            switch ($fileErr) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $msg .= "El archivo supera el tamaño máximo permitido.";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $msg .= "La subida fue parcial.";
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $msg .= "Falta carpeta temporal en el servidor.";
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $msg .= "No se pudo escribir el archivo en disco.";
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $msg .= "La subida fue detenida por extensión.";
                    break;
                default:
                    $msg .= "Error desconocido.";
            }
            $errores[] = $msg;
        } else {
            // Tamaño
            if ($_FILES['profile-photo']['size'] > $MAX_FILE_SIZE) {
                $errores[] = "La imagen excede el límite de " . ($MAX_FILE_SIZE / (1024 * 1024)) . " MB.";
            } else {
                // Validar MIME real
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $_FILES['profile-photo']['tmp_name']);
                finfo_close($finfo);

                if (!array_key_exists($mime, $ALLOWED_MIME)) {
                    $errores[] = "Tipo de archivo no permitido. Solo JPG, PNG y GIF.";
                } else {
                    // preparar carpeta
                    if (!is_dir($UPLOAD_DIR)) {
                        if (!mkdir($UPLOAD_DIR, 0755, true)) {
                            $errores[] = "No se pudo crear la carpeta de uploads en el servidor.";
                        }
                    }

                    // nombre único seguro
                    try {
                        $random = bin2hex(random_bytes(8));
                    } catch (Exception $e) {
                        $random = uniqid();
                    }
                    $ext = $ALLOWED_MIME[$mime];
                    $nuevo_nombre = time() . "_" . $random . "." . $ext;
                    $ruta_fisica = $UPLOAD_DIR . $nuevo_nombre;      // ruta en disco
                    $ruta_para_db = $UPLOAD_URL_PREFIX . $nuevo_nombre; // ruta que guardaremos en DB

                    // mover archivo
                    if (!is_uploaded_file($_FILES['profile-photo']['tmp_name'])) {
                        $errores[] = "Archivo subido inválido.";
                    } else {
                        if (move_uploaded_file($_FILES['profile-photo']['tmp_name'], $ruta_fisica)) {
                            // opcional: permisos
                            @chmod($ruta_fisica, 0644);
                            $nueva_ruta_en_db = $ruta_para_db;

                            // OPCIONAL: borrar foto anterior si existe (y si no es default)
                            if (!empty($usuario['foto']) && file_exists(__DIR__ . "/" . $usuario['foto'])) {
                                // no forzamos error si no se puede borrar
                                @unlink(__DIR__ . "/" . $usuario['foto']);
                            }
                        } else {
                            $errores[] = "No se pudo mover el archivo al directorio final.";
                        }
                    }
                }
            }
        }
    }

    // =========================
    // 4) Si no hay errores: ejecutar updates (uno por campo)
    // =========================
    if (empty($errores)) {
        $realizoCambio = false;

        // email
        if (!empty($email) && $email !== $usuario['email']) {
            $stmt = $pdo->prepare("UPDATE usuarios SET email = ? WHERE id = ?");
            $stmt->execute([$email, $usuario_id]);
            $realizoCambio = true;
            // actualizar variable local y sesion
            $usuario['email'] = $email;
            $_SESSION['usuario_email'] = $email;
        }
        if (!empty($nombre) && $nombre !== ($_SESSION['usuario_nombre'] ?? '')) {
            $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ? WHERE id = ?");
            $stmt->execute([$nombre, $usuario_id]);
            $realizoCambio = true;
            $usuario['nombre'] = $nombre;
            $_SESSION['usuario_nombre'] = $nombre;
        }

        // password
        if (!empty($password_nueva)) {
            $password_hash = password_hash($password_nueva, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
            $stmt->execute([$password_hash, $usuario_id]);
            $realizoCambio = true;
            $usuario['password'] = $password_hash;
        }

        // foto
        if (!empty($nueva_ruta_en_db)) {
            $stmt = $pdo->prepare("UPDATE usuarios SET foto = ? WHERE id = ?");
            $stmt->execute([$nueva_ruta_en_db, $usuario_id]);
            $realizoCambio = true;
            $usuario['foto'] = $nueva_ruta_en_db;
        }

        if ($realizoCambio) {
            $exito = "Perfil actualizado correctamente.";
        } else {
            $errores[] = "No realizaste ningún cambio.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Editar Perfil</title>
  <link rel="stylesheet" href="style/todo.css">
  <style>
    .edit-profile-container { max-width:700px;margin:60px auto;padding:20px;background:#fff;border-radius:8px;}
    .edit-profile-form label { display:block;margin:10px 0 6px;}
    .edit-profile-form input[type="text"], .edit-profile-form input[type="email"], .edit-profile-form input[type="password"] { width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;}
    .edit-profile-buttons { margin-top:14px; display:flex; gap:8px;}
  </style>
</head>
<body>
  <?php include('header.php'); ?>

  <main class="edit-profile-container">
    <h2>Editar Perfil</h2>

    <?php if (!empty($errores)): ?>
      <div class="error">
        <?php foreach ($errores as $err): ?>
          <p><?= htmlspecialchars($err) ?></p>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($exito)): ?>
      <div class="success"><?= htmlspecialchars($exito) ?></div>
    <?php endif; ?>

    <!-- mostrar foto actual -->
    <?php if (!empty($usuario['foto']) && file_exists(__DIR__ . '/' . $usuario['foto'])): ?>
      <div style="margin-bottom:12px;">
        <img src="<?= htmlspecialchars($usuario['foto']) ?>" alt="Foto perfil" width="120" style="border-radius:8px;border:1px solid #ddd;">
      </div>
    <?php endif; ?>

    <form class="edit-profile-form" action="" method="POST" enctype="multipart/form-data">
      <label for="profile-photo">Foto de perfil (JPG/PNG/GIF, máx 5MB)</label>
      <input type="file" id="profile-photo" name="profile-photo" accept="image/jpeg,image/png,image/gif">

      <label for="nombre">Nombre</label>
        <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($_SESSION['usuario_nombre'] ?? '') ?>">

      <label for="email">Correo</label>
      <input type="email" id="email" name="email" value="<?= htmlspecialchars($usuario['email']) ?>" required>

      <label for="current-password">Contraseña actual (solo si vas a cambiar la contraseña)</label>
      <input type="password" id="current-password" name="current-password" autocomplete="current-password">

      <label for="new-password">Nueva contraseña</label>
      <input type="password" id="new-password" name="new-password" autocomplete="new-password">

      <label for="confirm-password">Confirmar nueva contraseña</label>
      <input type="password" id="confirm-password" name="confirm-password">

      <div class="edit-profile-buttons">
        <button type="submit">Guardar cambios</button>
        <button type="button" onclick="window.location.href='perfil.php'">Volver</button>
      </div>
    </form>
  </main>
</body>
</html>
