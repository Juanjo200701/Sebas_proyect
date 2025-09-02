<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Gestor de Tareas</title>
  <link rel="stylesheet" href="style/inicio.css" />
</head>
<body>
  <main class="todo-container">
    <h1>Mis Tareas</h1>

    <form class="task-form">
      <input type="text" placeholder="Agregar nueva tarea..." required />
      <button type="submit">Añadir</button>
    </form>

    <ul class="task-list">
      <li class="task">
        <span class="task-text">Aprender HTML y CSS</span>
        <div class="task-actions">
          <button class="done">✓</button>
          <button class="delete">✕</button>
        </div>
      </li>
      <li class="task completed">
        <span class="task-text">Terminar login del proyecto</span>
        <div class="task-actions">
          <button class="done">✓</button>
          <button class="delete">✕</button>
        </div>
      </li>
    </ul>
  </main>
</body>
</html>
