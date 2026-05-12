<?php
class Database {
    public static function connect() {
        $host = "localhost";
        $db_name = "la_providencia"; // Tu base de datos
        $user = "root";
        $pass = "";

        try {
            $conexion = new PDO("mysql:host=$host;dbname=$db_name", $user, $pass);
            $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $conexion;
        } catch(PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }
}