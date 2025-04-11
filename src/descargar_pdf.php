<?php
session_start();
require 'vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;
require_once 'conexion/db_conexion.php';

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Verificar y sanitizar el ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID no válido");
}

$id = intval($_GET['id']);
$conn = conectarDB();

try {
    // Obtener datos del personaje con prepared statement
    $stmt = $conn->prepare("SELECT * FROM personajes WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $personaje = $result->fetch_assoc();
    $stmt->close();
    
    if (!$personaje) {
        die("Personaje no encontrado");
    }

    // Sanitizar datos para HTML
    $nombre = htmlspecialchars($personaje['nombre'], ENT_QUOTES, 'UTF-8');
    $color = htmlspecialchars($personaje['color'], ENT_QUOTES, 'UTF-8');
    $tipo = htmlspecialchars($personaje['tipo'], ENT_QUOTES, 'UTF-8');
    $nivel = htmlspecialchars($personaje['nivel'], ENT_QUOTES, 'UTF-8');
    $foto = htmlspecialchars($personaje['foto'], ENT_QUOTES, 'UTF-8');

    // Configuración de DomPDF
    $options = new Options();
    $options->set('defaultFont', 'Arial');
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true); // Para permitir imágenes remotas
    $dompdf = new Dompdf($options);

    // Contenido del PDF con CSS en línea
    $html = "
    <style>
        body { font-family: Arial, sans-serif; color: #333; margin: 0; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; text-align: center; }
        .title { font-size: 24px; color: #0066cc; margin-bottom: 20px; font-weight: bold; }
        .profile-img { max-width: 200px; border-radius: 10px; margin: 0 auto 20px; display: block; }
        .info { font-size: 16px; margin: 10px 0; text-align: left; padding-left: 20%; }
        .info strong { display: inline-block; width: 180px; }
        hr { border: 0; height: 1px; background: #ddd; margin: 20px 0; }
        .footer { font-size: 12px; color: #777; margin-top: 30px; }
    </style>

    <div class='container'>
        <h1 class='title'>Perfil del Personaje</h1>
        <hr>
        <img class='profile-img' src='$foto' alt='Foto de $nombre'>
        <div class='info'><strong>Nombre:</strong> $nombre</div>
        <div class='info'><strong>Color Representativo:</strong> $color</div>
        <div class='info'><strong>Tipo:</strong> $tipo</div>
        <div class='info'><strong>Nivel:</strong> $nivel</div>
        <hr>
        <div class='footer'>Generado automáticamente por Breaking Bad CRUD - " . date('d/m/Y H:i') . "</div>
    </div>";

    // Generar el PDF
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    
    // Renderizar el PDF
    $dompdf->render();

    $filename = 'perfil_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $nombre) . '.pdf';
    
    $dompdf->stream($filename, [
        "Attachment" => true 
    ]);

   
    echo "
    <script>
        window.onload = function() {
            // Abre el PDF en una nueva ventana o pestaña
            var pdfPath = '$filename';
            window.open(pdfPath, '_blank');
        }
    </script>
    ";

} catch (Exception $e) {
    die("Error al generar el PDF: " . $e->getMessage());
} finally {
    if (isset($conn) && $conn) {
        $conn->close();
    }
}
