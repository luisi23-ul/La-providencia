<?php

class ConfiguracionModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function obtener() {
        try {
            $stmt = $this->db->query("SELECT * FROM sistema LIMIT 1");
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            return [];
        }
    }

  public function actualizar($datos) {
        try {
            // Filtrar y asignar exclusivamente las columnas que existen en la tabla sistema
            $parametros = [
                ':nombre_empresa'    => trim((string)($datos['nombre_empresa'] ?? 'La Providenciaa')),
                ':rif'               => trim((string)($datos['rif'] ?? '')),
                ':telefono'          => trim((string)($datos['telefono'] ?? '')),
                ':email_contacto'    => trim((string)($datos['email_contacto'] ?? $datos['correo'] ?? '')),
                ':direccion'         => trim((string)($datos['direccion'] ?? '')),
                ':whatsapp'          => trim((string)($datos['whatsapp'] ?? '')),
                ':tasa_modo'         => trim((string)($datos['tasa_modo'] ?? 'bcv')),
                ':tasa_manual'       => (float)($datos['tasa_manual'] ?? 0),
                ':titulo_hero'       => trim((string)($datos['titulo_hero'] ?? '')),
                ':subtitulo_hero'    => trim((string)($datos['subtitulo_hero'] ?? '')),
                ':footer_texto'      => trim((string)($datos['footer_texto'] ?? '')),
                ':color_primario'    => trim((string)($datos['color_primario'] ?? '#8c4a2f')),
                ':color_secundario'  => trim((string)($datos['color_secundario'] ?? '#f5f0e8')),
                ':color_terciario'   => trim((string)($datos['color_terciario'] ?? '#2a2420')),
                ':color_cuaternario' => trim((string)($datos['color_cuaternario'] ?? '#64748b'))
            ];

            $campoLogo = "";
            if (!empty($datos['logo_path'])) {
                $campoLogo = ", logo_path = :logo_path";
                $parametros[':logo_path'] = $datos['logo_path'];
            }

            $sql = "UPDATE sistema SET 
                        nombre_empresa   = :nombre_empresa,
                        rif              = :rif,
                        telefono         = :telefono,
                        email_contacto   = :email_contacto,
                        direccion        = :direccion,
                        whatsapp         = :whatsapp,
                        tasa_modo        = :tasa_modo,
                        tasa_manual      = :tasa_manual,
                        titulo_hero      = :titulo_hero,
                        subtitulo_hero   = :subtitulo_hero,
                        footer_texto     = :footer_texto,
                        color_primario   = :color_primario,
                        color_secundario = :color_secundario,
                        color_terciario  = :color_terciario,
                        color_cuaternario= :color_cuaternario
                        $campoLogo
                    LIMIT 1";

            $stmt = $this->db->prepare($sql);
            return $stmt->execute($parametros);

        } catch (PDOException $e) {
            // Imprime el error exacto en pantalla si vuelve a fallar
            die("Error en UPDATE sistema: " . $e->getMessage());
        }
    }
}