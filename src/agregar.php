<?php
session_start();
require_once 'conexion/db_conexion.php';
include 'plantilla.php';

// Verificar conexion a la base de datos
$conn = conectarDB();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Inicializar variables
$errores = [];
$valores = [
    'nombre' => '',
    'color' => '',
    'tipo' => '',
    'nivel' => '',
    'foto' => ''
];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validar y sanitizar inputs
    $valores['nombre'] = trim($_POST['nombre'] ?? '');
    $valores['color'] = trim($_POST['color'] ?? '');
    $valores['tipo'] = trim($_POST['tipo'] ?? '');
    $valores['nivel'] = intval($_POST['nivel'] ?? 0);
    $valores['foto'] = filter_var(trim($_POST['foto'] ?? ''), FILTER_VALIDATE_URL);

    // Validaciones
    if (empty($valores['nombre'])) {
        $errores[] = "El nombre es obligatorio";
    }
    
    if (!empty($valores['foto']) && $valores['foto'] === false) {
        $errores[] = "La URL de la foto no es válida";
    }

    if (empty($errores)) {
        try {
            $stmt = $conn->prepare("INSERT INTO personajes (nombre, color, tipo, nivel, foto) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssis", 
                $valores['nombre'],
                $valores['color'],
                $valores['tipo'],
                $valores['nivel'],
                $valores['foto']
            );
            
            if ($stmt->execute()) {
                $_SESSION['mensaje'] = "Personaje agregado correctamente";
                $_SESSION['tipo_mensaje'] = "success";
                header("Location: index.php");
                exit();
            } else {
                $errores[] = "Error al agregar el personaje: " . $conn->error;
            }
            
            $stmt->close();
        } catch (Exception $e) {
            $errores[] = "Error en la base de datos: " . $e->getMessage();
        } finally {
            $conn->close(); 
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <title>Agregar Personaje</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        .error { color: red; font-size: 0.9em; }
    </style>
</head>
<body>
<div class="container mt-4">
    <h2>Agregar Nuevo Personaje</h2>
    
    <?php if (!empty($errores)): ?>
        <div class="alert alert-danger">
            <ul>
                <?php foreach ($errores as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <form action="" method="POST">
        <div class="mb-3">
            <label class="form-label">Nombre:</label>
            <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($valores['nombre']) ?>" required>
        </div>
        
        <div class="mb-3">
            <label class="form-label">Color Representativo:</label>
            <input type="text" name="color" class="form-control" value="<?= htmlspecialchars($valores['color']) ?>">
            <small class="text-muted">Ejemplo: #FF0000 o 'red'</small>
        </div>
        
        <div class="mb-3">
            <label class="form-label">Tipo:</label>
            <input type="text" name="tipo" class="form-control" value="<?= htmlspecialchars($valores['tipo']) ?>">
        </div>
        
        <div class="mb-3">
            <label class="form-label">Nivel:</label>
            <input type="number" name="nivel" class="form-control" value="<?= htmlspecialchars($valores['nivel']) ?>">
        </div>
        
        <div class="mb-3">
            <label class="form-label">Foto (URL):</label>
            <input type="url" name="foto" class="form-control" value="<?= htmlspecialchars($valores['foto']) ?>">
            <small class="text-muted">Ejemplo: https://ejemplo.com/foto.jpg</small>
        </div>
        
        <button type="submit" class="btn btn-success">Guardar</button>
        <a href="index.php" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
</body>
</html>