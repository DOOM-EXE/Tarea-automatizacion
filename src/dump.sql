CREATE DATABASE breakingbad_db;
USE breakingbad_db;

CREATE TABLE personajes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    color VARCHAR(50),
    tipo VARCHAR(50),
    nivel INT,
    foto VARCHAR(255)
);
