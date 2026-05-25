<?php
class ConfiguracionModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function obtenerConfiguracion() {
        // Cambiado de "configuracion" a "sistema"
        $sql = "SELECT * FROM sistema WHERE id = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // Agrégalo dentro de la clase ConfiguracionModel
public function actualizarConfiguracionModel($datos) {
    $sql = "UPDATE sistema SET 
            nombre_empresa = ?, titulo_principal = ?, 
            color_primario = ?, color_secundario = ?, 
            footer_texto = ?, logo = ?,
            telefono = ?, email_contacto = ?, 
            direccion = ?, mapa_url = ? 
            WHERE id = 1";
            
    $stmt = $this->db->prepare($sql);
    $stmt->execute([
        $datos['nombre_empresa'], $datos['titulo_principal'], 
        $datos['color_primario'], $datos['color_secundario'], 
        $datos['footer_texto'], $datos['logo'],
        $datos['telefono'], $datos['email_contacto'], 
        $datos['direccion'], $datos['mapa_url']
    ]);
}
}