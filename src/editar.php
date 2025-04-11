<?php
session_start();
require_once 'conexion/db_conexion.php';
include 'plantilla.php';

// Verificar sesion
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$errores = [];
$personaje = null;

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);
    $conn = conectarDB();
    
    try {
        $stmt = $conn->prepare("SELECT * FROM personajes WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $personaje = $result->fetch_assoc();
        $stmt->close();
        
        if (!$personaje) {
            $_SESSION['mensaje'] = "Personaje no encontrado";
            $_SESSION['tipo_mensaje'] = "danger";
            header("Location: index.php");
            exit();
        }
    } catch (Exception $e) {
        $errores[] = "Error al obtener datos: " . $e->getMessage();
    }
} else {
    $_SESSION['mensaje'] = "ID no válido";
    $_SESSION['tipo_mensaje'] = "danger";
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $personaje) {
    // Validar y sanitizar inputs
    $nombre = trim($_POST['nombre'] ?? '');
    $color = trim($_POST['color'] ?? '');
    $tipo = trim($_POST['tipo'] ?? '');
    $nivel = intval($_POST['nivel'] ?? 0);
    $foto = filter_var(trim($_POST['foto'] ?? ''), FILTER_VALIDATE_URL);
    
    // Validaciones
    if (empty($nombre)) {
        $errores[] = "El nombre es obligatorio";
    }
    
    if (!empty($foto) && $foto === false) {
        $errores[] = "La URL de la foto no es válida";
    }

    if (empty($errores)) {
        try {
            $stmt = $conn->prepare("UPDATE personajes SET nombre=?, color=?, tipo=?, nivel=?, foto=? WHERE id=?");
            $stmt->bind_param("sssisi", $nombre, $color, $tipo, $nivel, $foto, $id);
            
            if ($stmt->execute()) {
                $_SESSION['mensaje'] = "Personaje actualizado correctamente";
                $_SESSION['tipo_mensaje'] = "success";
                header("Location: index.php");
                exit();
            } else {
                $errores[] = "Error al actualizar: " . $conn->error;
            }
            
            $stmt->close();
        } catch (Exception $e) {
            $errores[] = "Error en la base de datos: " . $e->getMessage();
        }
    }
}

if (isset($conn) && $conn) {
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <title>Editar Personaje</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        .error { color: red; font-size: 0.9em; }
    </style>
</head>
<body>
<div class="container mt-4">
    <h2>Editar Personaje</h2>
    
    <?php if (!empty($errores)): ?>
        <div class="alert alert-danger">
            <ul>
                <?php foreach ($errores as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <?php if ($personaje): ?>
    <form action="" method="POST">
        <input type="hidden" name="id" value="<?= $personaje['id'] ?>">
        <div class="mb-3">
            <label class="form-label">Nombre:</label>
            <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($personaje['nombre']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Color Representativo:</label>
            <input type="text" name="color" class="form-control" value="<?= htmlspecialchars($personaje['color']) ?>">
            <small class="text-muted">Ejemplo: #FF0000 o 'red'</small>
        </div>
        <div class="mb-3">
            <label class="form-label">Tipo:</label>
            <input type="text" name="tipo" class="form-control" value="<?= htmlspecialchars($personaje['tipo']) ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Nivel:</label>
            <input type="number" name="nivel" class="form-control" value="<?= htmlspecialchars($personaje['nivel']) ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Foto (URL):</label>
            <input type="url" name="foto" class="form-control" value="<?= htmlspecialchars($personaje['foto']) ?>">
            <small class="text-muted">Ejemplo: https://ejemplo.com/foto.jpg</small>
        </div>
        <button type="submit" class="btn btn-primary">Actualizar</button>
        <a href="index.php" class="btn btn-secondary">Cancelar</a>
    </form>
    <?php endif; ?>
</div>
</body>
</html>