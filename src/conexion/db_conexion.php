<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'breakingbad_db');

function conectarDB() {
        static $conn = null;
        
        if ($conn === null) {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            if ($conn->connect_error) {
                die("Conexión fallida: " . $conn->connect_error);
            }
        }
        
        return $conn;
    }

?>