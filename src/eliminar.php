<?php
session_start();
require_once 'conexion/db_conexion.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Verificar que el ID existe y es valida
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);
    $conn = conectarDB(); 
    
    try {
        $stmt = $conn->prepare("SELECT nombre FROM personajes WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $personaje = $result->fetch_assoc();
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM personajes WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $_SESSION['mensaje'] = "Personaje '".htmlspecialchars($personaje['nombre'])."' eliminado correctamente";
            $_SESSION['tipo_mensaje'] = "success";
        } else {
            $_SESSION['mensaje'] = "Error al eliminar el personaje";
            $_SESSION['tipo_mensaje'] = "danger";
        }
        
        $stmt->close();
    } catch (Exception $e) {
        $_SESSION['mensaje'] = "Error: " . $e->getMessage();
        $_SESSION['tipo_mensaje'] = "danger";
    } finally {
        $conn->close(); 
    }
} else {
    $_SESSION['mensaje'] = "ID no válido";
    $_SESSION['tipo_mensaje'] = "danger";
}

header("Location: index.php");
exit();
?>