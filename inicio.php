<?php
session_start();
include('database.php');

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$errores = [];
$mensaje = "";

// Obtener rol del usuario (para lógica admin)
$stmtUser = $pdo->prepare("SELECT rol FROM usuarios WHERE id = ?");
$stmtUser->execute([$usuario_id]);
$userRow = $stmtUser->fetch(PDO::FETCH_ASSOC);
$userRol = $userRow['rol'] ?? 'member';

// --- Consultar todos los usuarios (solo admin) ---
$usuarios = [];
if ($userRol === 'admin') {
    $stmtUsuarios = $pdo->query("SELECT id, nombre FROM usuarios ORDER BY nombre ASC");
    $usuarios = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC);
}

// --- Consultar todas las etiquetas (antes del HTML) ---
$stmtEtiquetas = $pdo->query("SELECT id, nombre, color FROM etiquetas ORDER BY nombre ASC");
$etiquetas = $stmtEtiquetas->fetchAll(PDO::FETCH_ASSOC);

// --- Consultar proyectos dependiendo de rol (admin = todos, else solo creados por el usuario) ---
if ($userRol === 'admin') {
    $stmtProyectos = $pdo->query("SELECT id, nombre FROM proyectos ORDER BY nombre ASC");
    $proyectos = $stmtProyectos->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmtProyectos = $pdo->prepare("SELECT id, nombre FROM proyectos WHERE creado_por = ? ORDER BY nombre ASC");
    $stmtProyectos->execute([$usuario_id]);
    $proyectos = $stmtProyectos->fetchAll(PDO::FETCH_ASSOC);
}

// --- CRUD TAREAS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nueva_tarea'])) {
    $titulo = trim($_POST['titulo'] ?? '');
    $proyecto_id = intval($_POST['proyecto_id'] ?? 0);
    $etiquetas_sel = $_POST['etiquetas'] ?? [];
    $asignado_id = ($userRol === 'admin') ? intval($_POST['asignado_id'] ?? 0) : $usuario_id;

    if (empty($titulo)) {
        $errores[] = "El título de la tarea no puede estar vacío.";
    } elseif ($proyecto_id <= 0) {
        $errores[] = "Debes seleccionar un proyecto.";
    } elseif ($userRol === 'admin' && $asignado_id <= 0) {
        $errores[] = "Debes seleccionar el usuario asignado.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO tareas (titulo, creador_id, asignado_id, estado, proyecto_id) VALUES (?, ?, ?, 'todo', ?)");
        $stmt->execute([$titulo, $usuario_id, $asignado_id, $proyecto_id]);
        $tarea_id = $pdo->lastInsertId();

        foreach ($etiquetas_sel as $etiqueta_id) {
            $stmtEtiquetasIns = $pdo->prepare("INSERT INTO tarea_etiqueta (tarea_id, etiqueta_id) VALUES (?, ?)");
            $stmtEtiquetasIns->execute([$tarea_id, intval($etiqueta_id)]);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_tarea'])) {
    $id = intval($_POST['tarea_id']);
    $titulo = trim($_POST['titulo'] ?? '');
    $proyecto_id = intval($_POST['proyecto_id'] ?? 0);
    $etiquetas_sel = $_POST['etiquetas'] ?? [];
    $asignado_id = ($userRol === 'admin') ? intval($_POST['asignado_id'] ?? 0) : $usuario_id;

    if (empty($titulo)) {
        $errores[] = "El título de la tarea no puede estar vacío.";
    } elseif ($proyecto_id <= 0) {
        $errores[] = "Debes seleccionar un proyecto.";
    } elseif ($userRol === 'admin' && $asignado_id <= 0) {
        $errores[] = "Debes seleccionar el usuario asignado.";
    } else {
        if ($userRol === 'admin') {
            $stmt = $pdo->prepare("UPDATE tareas SET titulo=?, proyecto_id=?, asignado_id=? WHERE id=?");
            $stmt->execute([$titulo, $proyecto_id, $asignado_id, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE tareas SET titulo=?, proyecto_id=? WHERE id=? AND asignado_id=?");
            $stmt->execute([$titulo, $proyecto_id, $id, $usuario_id]);
        }

        $pdo->prepare("DELETE FROM tarea_etiqueta WHERE tarea_id=?")->execute([$id]);
        foreach ($etiquetas_sel as $etiqueta_id) {
            $stmtEtiquetasIns = $pdo->prepare("INSERT INTO tarea_etiqueta (tarea_id, etiqueta_id) VALUES (?, ?)");
            $stmtEtiquetasIns->execute([$id, intval($etiqueta_id)]);
        }
    }
}

// Completar / Eliminar tarea
if (isset($_GET['completar'])) {
    $id = intval($_GET['completar']);
    if ($userRol === 'admin') {
        $stmt = $pdo->prepare("UPDATE tareas SET estado = 'done' WHERE id = ?");
        $stmt->execute([$id]);
    } else {
        $stmt = $pdo->prepare("UPDATE tareas SET estado = 'done' WHERE id = ? AND asignado_id = ?");
        $stmt->execute([$id, $usuario_id]);
    }
}
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    if ($userRol === 'admin') {
        $stmt = $pdo->prepare("DELETE FROM tareas WHERE id = ?");
        $stmt->execute([$id]);
    } else {
        $stmt = $pdo->prepare("DELETE FROM tareas WHERE id = ? AND asignado_id = ?");
        $stmt->execute([$id, $usuario_id]);
    }
}

// --- FILTROS Y BUSQUEDA ---
$where = "WHERE t.parent_task_id IS NULL";
$params = [];

if (!empty($_GET['buscar'])) {
    $where .= " AND t.titulo LIKE ?";
    $params[] = '%' . $_GET['buscar'] . '%';
}
if (!empty($_GET['proyecto_id'])) {
    $where .= " AND t.proyecto_id = ?";
    $params[] = intval($_GET['proyecto_id']);
}
if (!empty($_GET['estado'])) {
    $where .= " AND t.estado = ?";
    $params[] = $_GET['estado'];
}
if (!empty($_GET['asignado_id'])) {
    $where .= " AND t.asignado_id = ?";
    $params[] = intval($_GET['asignado_id']);
}
if (!empty($_GET['etiqueta_id'])) {
    $where .= " AND EXISTS (
        SELECT 1 FROM tarea_etiqueta te WHERE te.tarea_id = t.id AND te.etiqueta_id = ?
    )";
    $params[] = intval($_GET['etiqueta_id']);
}

$sql = "
    SELECT t.*, p.nombre AS proyecto_nombre, u.nombre AS asignado_nombre
    FROM tareas t
    LEFT JOIN proyectos p ON t.proyecto_id = p.id
    LEFT JOIN usuarios u ON t.asignado_id = u.id
    $where
    ORDER BY t.creado_en DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Datos para edición
$tarea_editar = null;
$etiquetas_tarea = [];
if (isset($_GET['editar'])) {
    $id = intval($_GET['editar']);
    if ($userRol === 'admin') {
        $stmt = $pdo->prepare("SELECT * FROM tareas WHERE id = ?");
        $stmt->execute([$id]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM tareas WHERE id = ? AND asignado_id = ?");
        $stmt->execute([$id, $usuario_id]);
    }
    $tarea_editar = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($tarea_editar) {
        $stmt = $pdo->prepare("SELECT etiqueta_id FROM tarea_etiqueta WHERE tarea_id=?");
        $stmt->execute([$tarea_editar['id']]);
        $etiquetas_tarea = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'etiqueta_id');
    }
}

// --- CRUD SUBTAREAS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nueva_subtarea'])) {
    $titulo = trim($_POST['titulo'] ?? '');
    $parent_id = intval($_POST['parent_task_id'] ?? 0);
    if (empty($titulo) || $parent_id == 0) {
        $errores[] = "Debes escribir el título y seleccionar la tarea principal.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO tareas (titulo, creador_id, asignado_id, estado, parent_task_id) VALUES (?, ?, ?, 'todo', ?)");
        $stmt->execute([$titulo, $usuario_id, $usuario_id, $parent_id]);
    }
}

if (isset($_GET['completar_sub'])) {
    $id = intval($_GET['completar_sub']);
    $stmt = $pdo->prepare("UPDATE tareas SET estado = 'done' WHERE id = ? AND asignado_id = ?");
    $stmt->execute([$id, $usuario_id]);
}

if (isset($_GET['eliminar_sub'])) {
    $id = intval($_GET['eliminar_sub']);
    $stmt = $pdo->prepare("DELETE FROM tareas WHERE id = ? AND asignado_id = ?");
    $stmt->execute([$id, $usuario_id]);
}

$subtarea_editar = null;
if (isset($_GET['editar_sub'])) {
    $id = intval($_GET['editar_sub']);
    $stmt = $pdo->prepare("SELECT * FROM tareas WHERE id = ? AND asignado_id = ?");
    $stmt->execute([$id, $usuario_id]);
    $subtarea_editar = $stmt->fetch();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_subtarea'])) {
    $id = intval($_POST['subtarea_id']);
    $titulo = trim($_POST['titulo'] ?? '');
    if (empty($titulo)) {
        $errores[] = "El título de la subtarea no puede estar vacío.";
    } else {
        $stmt = $pdo->prepare("UPDATE tareas SET titulo=? WHERE id=? AND asignado_id=?");
        $stmt->execute([$titulo, $id, $usuario_id]);
    }
}

// --- SUBIR ARCHIVOS ---
$carpetaDestino = "uploads/";
if (!is_dir($carpetaDestino)) {
    mkdir($carpetaDestino, 0755, true);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["subir_archivo"])) {
    $tarea_id = intval($_POST["tarea_id"] ?? 0);
    if ($tarea_id <= 0) {
        $errores[] = "Debes asociar el archivo a una tarea válida.";
    } else {
        if (isset($_FILES["archivo"]) && $_FILES["archivo"]["error"] == 0) {
            $nombreArchivo = time() . "_" . basename($_FILES["archivo"]["name"]);
            $rutaDestino = $carpetaDestino . $nombreArchivo;

            $tipoArchivo = strtolower(pathinfo($rutaDestino, PATHINFO_EXTENSION));
            $tamanioMaximo = 5 * 1024 * 1024;

            if ($_FILES["archivo"]["size"] > $tamanioMaximo) {
                $errores[] = "El archivo es demasiado grande. Máximo 5MB.";
            } elseif (!in_array($tipoArchivo, ["jpg", "jpeg", "png", "pdf", "txt"])) {
                $errores[] = "Solo se permiten archivos JPG, PNG, PDF y TXT.";
            } else {
                if (move_uploaded_file($_FILES["archivo"]["tmp_name"], $rutaDestino)) {
                    $stmt = $pdo->prepare("
                        INSERT INTO adjuntos (tarea_id, nombre_archivo, ruta, subido_por) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $tarea_id,
                        $nombreArchivo,
                        $rutaDestino,
                        $_SESSION['usuario_id'] ?? null
                    ]);

                    $mensaje = "El archivo " . htmlspecialchars($nombreArchivo) . " se ha subido correctamente.";
                } else {
                    $errores[] = "Hubo un error al mover el archivo al servidor.";
                }
            }
        } else {
            $errores[] = "No se envió ningún archivo o hubo un error en la subida.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Gestor de Tareas</title>
  <link rel="stylesheet" href="style/todo.css" />
  <style>
    .task-form input, .task-form select,
    .task-form2 input, .task-form2 select {
      flex: 1;
      padding: 3% 4%;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 100%;
      background: #fff;
      color: #333;
      min-width: 160px;
    }
  </style>
</head>
<body>
  <?php include('header.php'); ?>
  <main class="todo-container">
    <h1>Mis Tareas</h1>

    <?php foreach ($errores as $error): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endforeach; ?>

    <?php if (!empty($mensaje)): ?>
      <div class="success"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <!-- Formulario editar subtarea -->
    <?php if ($subtarea_editar): ?>
      <form class="task-form2" method="post">
        <input type="hidden" name="subtarea_id" value="<?= $subtarea_editar['id'] ?>">
        <input type="text" name="titulo" required value="<?= htmlspecialchars($subtarea_editar['titulo']) ?>" />
        <button type="submit" name="editar_subtarea">Guardar cambios</button>
        <a href="inicio.php">Cancelar</a>
      </form>
    <?php endif; ?>

    <form class="task-form" method="post">
  <?php if ($tarea_editar): ?>
    <input type="hidden" name="tarea_id" value="<?= $tarea_editar['id'] ?>">

    <div class="form-group">
      <label for="titulo">Título:</label>
      <input type="text" id="titulo" name="titulo" required value="<?= htmlspecialchars($tarea_editar['titulo']) ?>" />
    </div>

    <?php if ($userRol === 'admin'): ?>
      <div class="form-group">
        <label for="asignado_editar">Asignar a:</label>
        <select name="asignado_id" id="asignado_editar" required>
          <option value="">-- Selecciona usuario --</option>
          <?php foreach ($usuarios as $u): ?>
            <option value="<?= $u['id'] ?>" <?= (isset($tarea_editar['asignado_id']) && $tarea_editar['asignado_id'] == $u['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($u['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    <?php endif; ?>

    <div class="form-group">
      <label for="proyecto_editar">Proyecto:</label>
      <select name="proyecto_id" id="proyecto_editar" required>
        <option value="">-- Selecciona un proyecto --</option>
        <?php foreach ($proyectos as $p): ?>
          <option value="<?= $p['id'] ?>" <?= (isset($tarea_editar['proyecto_id']) && $tarea_editar['proyecto_id'] == $p['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($p['nombre']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-group">
      <label for="etiquetas_editar">Etiqueta:</label>
      <select name="etiquetas[]" id="etiquetas_editar">
        <option value="">-- Sin etiqueta --</option>
        <?php foreach ($etiquetas as $etiqueta): ?>
          <option value="<?= $etiqueta['id'] ?>" <?= in_array($etiqueta['id'], $etiquetas_tarea) ? 'selected' : '' ?>>
            <?= htmlspecialchars($etiqueta['nombre']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-actions">
      <button type="submit" name="editar_tarea">Guardar cambios</button>
      <a href="inicio.php" class="btn-cancelar-editar-admin">Cancelar</a>
    </div>

  <?php else: ?>
    <div class="form-group">
      <label for="titulo">Título:</label>
      <input type="text" id="titulo" name="titulo" placeholder="Nueva Tarea" />
    </div>

    <?php if ($userRol === 'admin'): ?>
      <div class="form-group">
        <label for="asignado_nueva">Asignar a:</label>
        <select name="asignado_id" id="asignado_nueva" required>
          <option value="">-- Selecciona usuario --</option>
          <?php foreach ($usuarios as $u): ?>
            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    <?php endif; ?>

    <div class="form-group">
      <label for="proyecto_nueva">Proyecto:</label>
      <select name="proyecto_id" id="proyecto_nueva">
        <option value="">-- Sin Proyecto --</option>
        <?php foreach ($proyectos as $p): ?>
          <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-group">
      <label for="etiquetas_nueva">Etiqueta:</label>
      <select name="etiquetas[]" id="etiquetas_nueva">
        <option value="">-- Sin etiqueta --</option>
        <?php foreach ($etiquetas as $etiqueta): ?>
          <option value="<?= $etiqueta['id'] ?>"><?= htmlspecialchars($etiqueta['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-actions">
      <button type="submit" name="nueva_tarea">Añadir</button>
    </div>
  <?php endif; ?>
</form>

    <!-- Formulario nueva subtarea -->
    <form class="task-form2" method="post">
      <select name="parent_task_id" required>
        <option value="">Selecciona Tarea</option>
        <?php foreach ($tareas as $tarea): ?>
          <option value="<?= $tarea['id'] ?>"><?= htmlspecialchars($tarea['titulo']) ?></option>
        <?php endforeach; ?>
      </select>
      <input type="text" name="titulo" placeholder=" Nueva Subtarea" required />
      <button type="submit" name="nueva_subtarea">Añadir</button>
    </form>

    <!-- Formulario de búsqueda y filtros -->
    <form method="get" class="filtros-form" style="margin-bottom:20px;">
      <input type="text" name="buscar" placeholder="🔍Buscar por título..." value="<?= htmlspecialchars($_GET['buscar'] ?? '') ?>">
      <select name="proyecto_id" class="proyectos-buscar">
        <option value=""class="proyectos-buscar-opcion">Todos los proyectos</option>
        <?php foreach ($proyectos as $p): ?>
          <option value="<?= $p['id'] ?>" <?= (isset($_GET['proyecto_id']) && $_GET['proyecto_id'] == $p['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($p['nombre']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <select name="estado" class="estado-buscar">
        <option value=""class="estado-buscar-opcion">Todos los estados</option>
        <option value="todo" <?= (isset($_GET['estado']) && $_GET['estado'] == 'todo') ? 'selected' : '' ?>>Pendiente</option>
        <option value="done" <?= (isset($_GET['estado']) && $_GET['estado'] == 'done') ? 'selected' : '' ?>>Completada</option>
      </select>
      <select name="asignado_id" class="asignado-buscar">
        <option value=""class="asignado-buscar-opcion">Todos los responsables</option>
        <?php foreach ($usuarios as $u): ?>
          <option value="<?= $u['id'] ?>" <?= (isset($_GET['asignado_id']) && $_GET['asignado_id'] == $u['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($u['nombre']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <select name="etiqueta_id" class="etiqueta-buscar">
        <option value=""class="etiqueta-buscar-opcion">Todas las etiquetas</option>
        <?php foreach ($etiquetas as $et): ?>
          <option value="<?= $et['id'] ?>" <?= (isset($_GET['etiqueta_id']) && $_GET['etiqueta_id'] == $et['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($et['nombre']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <button type="submit">Filtrar</button>
    </form>

    <!-- Lista de tareas -->
    <ul class="task-list">
      <?php foreach ($tareas as $tarea): ?>
        <li class="task<?= $tarea['estado'] === 'done' ? ' completed' : '' ?>">
          <div class="task-text">
            <?= htmlspecialchars($tarea['titulo']) ?>
            <div style="font-size:0.9rem;color:#666;margin-top:6px;">
              Proyecto: <?= htmlspecialchars($tarea['proyecto_nombre'] ?? 'Sin proyecto') ?>
              <br>
              Asignado a: <?= htmlspecialchars($tarea['asignado_nombre'] ?? 'Sin usuario') ?>
            </div>
          </div>

          <!-- Mostrar etiquetas -->
          <div class="etiquetas" style="margin-left:12px;">
            <?php
              $stmt_tags = $pdo->prepare("
                SELECT e.* FROM etiquetas e
                INNER JOIN tarea_etiqueta te ON e.id = te.etiqueta_id
                WHERE te.tarea_id = ?
              ");
              $stmt_tags->execute([$tarea['id']]);
              $etiquetas_asignadas = $stmt_tags->fetchAll(PDO::FETCH_ASSOC);
              foreach ($etiquetas_asignadas as $etq): ?>
                <span style="background: <?= htmlspecialchars($etq['color']) ?>; padding:3px 6px; border-radius:6px; color:#fff; margin-right:4px;">
                  <?= htmlspecialchars($etq['nombre']) ?>
                </span>
            <?php endforeach; ?>
          </div>

          <div class="task-actions">
            <a href="?editar=<?= $tarea['id'] ?>" class='btn-editar'>Editar</a>
            <?php if ($tarea['estado'] !== 'done'): ?>
              <a href="?completar=<?= $tarea['id'] ?>" class="btn-completar">Completar</a>
            <?php endif; ?>
            <?php if (empty($tarea['comentario'])): ?>
              <a href="comentarios.php?id=<?= $tarea['id'] ?>" class="btn-comentar">Comentar</a>
            <?php else: ?>
              <a href="comentarios.php?id=<?= $tarea['id'] ?>" class="btn-ver-comentario">Ver comentario</a>
            <?php endif; ?>
            <a href="?eliminar=<?= $tarea['id'] ?>" onclick="return confirm('¿Seguro que deseas eliminar esta tarea?')" class='btn-eliminar'>Eliminar</a>
          </div>

          <!-- Subtareas -->
          <?php
            $stmt_sub = $pdo->prepare("SELECT * FROM tareas WHERE parent_task_id = ?");
            $stmt_sub->execute([$tarea['id']]);
            $subtareas = $stmt_sub->fetchAll(PDO::FETCH_ASSOC);
            if ($subtareas):
          ?>
            <ul class="subtask-list">
              <?php foreach ($subtareas as $sub): ?>
                <li class="subtask<?= $sub['estado'] === 'done' ? ' completed' : '' ?>">
                  <?= htmlspecialchars($sub['titulo']) ?>
                  <?php if ($sub['estado'] !== 'done'): ?>
                    <a href="?completar_sub=<?= $sub['id'] ?>" class="btn-sub-completar">Completar</a>
                  <?php endif; ?>
                  <a href="?editar_sub=<?= $sub['id'] ?>" class="btn-sub-editar">Editar</a>
                  <a href="?eliminar_sub=<?= $sub['id'] ?>" class="btn-sub-eliminar">Eliminar</a>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>

          <!-- Adjuntos -->
          <?php
            $stmt_adj = $pdo->prepare("SELECT * FROM adjuntos WHERE tarea_id = ?");
            $stmt_adj->execute([$tarea['id']]);
            $adjuntos = $stmt_adj->fetchAll(PDO::FETCH_ASSOC);
            if ($adjuntos):
          ?>
            <ul class="attachments">
              <?php foreach ($adjuntos as $adj): ?>
                <li>
                  <a href="<?= htmlspecialchars($adj['ruta']) ?>" target="_blank">
                    <?= htmlspecialchars($adj['nombre_archivo']) ?>
                  </a>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>

          <!-- Formulario subida de archivos -->
          <form method="post" enctype="multipart/form-data" class="upload-form" style="margin-top:8px;">
            <input type="hidden" name="tarea_id" value="<?= $tarea['id'] ?>">
            <input type="file" name="archivo" required>
            <button type="submit" name="subir_archivo">Subir archivo</button>
          </form>
        </li>
      <?php endforeach; ?>
    </ul>
  </main>
</body>
</html>