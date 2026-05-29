<?php
class ConfiguracionModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function obtener() {
        return $this->db->query("SELECT * FROM sistema1 WHERE id = 1")->fetch(PDO::FETCH_OBJ);
    }

   public function actualizar($d) {
    $sql = "UPDATE sistema1 SET 
            titulo_hero = :th, 
            subtitulo_hero = :sh, 
            direccion = :dir, 
            telefono = :tel, 
            footer_texto = :ft, 
            color_primario = :cp, 
            color_secundario = :cs, 
            color_terciario = :ct, 
            color_cuaternario = :cq, 
            logo_path = :lp 
            WHERE id = 1";

    $stmt = $this->db->prepare($sql);
    $stmt->bindValue(':th', $d['titulo_hero'], PDO::PARAM_STR);
    $stmt->bindValue(':sh', $d['subtitulo_hero'], PDO::PARAM_STR);
    $stmt->bindValue(':dir', $d['direccion'], PDO::PARAM_STR);
    $stmt->bindValue(':tel', $d['telefono'], PDO::PARAM_STR);
    $stmt->bindValue(':ft', $d['footer_texto'], PDO::PARAM_STR);
    $stmt->bindValue(':cp', $d['color_primario'], PDO::PARAM_STR);
    $stmt->bindValue(':cs', $d['color_secundario'], PDO::PARAM_STR);
    $stmt->bindValue(':ct', $d['color_terciario'], PDO::PARAM_STR);
    $stmt->bindValue(':cq', $d['color_cuaternario'], PDO::PARAM_STR);
    $stmt->bindValue(':lp', $d['logo_path'], PDO::PARAM_STR);

    return $stmt->execute();
}
}