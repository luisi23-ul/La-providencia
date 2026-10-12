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
            $sql = "UPDATE sistema SET 
                        nombre_empresa   = :nombre_empresa,
                        rif              = :rif,
                        telefono         = :telefono,
                        email_contacto   = :email_contacto,
                        direccion        = :direccion,
                        whatsapp         = :whatsapp,
                        logo_path        = :logo_path,
                        tasa_modo        = :tasa_modo,
                        tasa_manual      = :tasa_manual,
                        titulo_hero      = :titulo_hero,
                        subtitulo_hero   = :subtitulo_hero,
                        footer_texto     = :footer_texto,
                        color_primario   = :color_primario,
                        color_secundario = :color_secundario,
                        color_terciario  = :color_terciario,
                        color_cuaternario= :color_cuaternario
                    WHERE id = 1";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':nombre_empresa'   => $datos['nombre_empresa'] ?? $datos['nombre'] ?? '',
                ':rif'              => $datos['rif'] ?? '',
                ':telefono'         => $datos['telefono'] ?? '',
                ':email_contacto'   => $datos['email_contacto'] ?? $datos['correo'] ?? '',
                ':direccion'        => $datos['direccion'] ?? '',
                ':whatsapp'         => $datos['whatsapp'] ?? '',
                ':logo_path'        => $datos['logo_path'] ?? '',
                ':tasa_modo'        => $datos['tasa_modo'] ?? 'bcv',
                ':tasa_manual'      => $datos['tasa_manual'] ?? 0,
                ':titulo_hero'      => $datos['titulo_hero'] ?? $datos['titulo_principal'] ?? '',
                ':subtitulo_hero'   => $datos['subtitulo_hero'] ?? $datos['subtitulo'] ?? '',
                ':footer_texto'     => $datos['footer_texto'] ?? $datos['pie_pagina'] ?? '',
                ':color_primario'   => $datos['color_primario'] ?? '#1b34da',
                ':color_secundario' => $datos['color_secundario'] ?? '#363c54',
                ':color_terciario'  => $datos['color_terciario'] ?? '#dcdcea',
                ':color_cuaternario'=> $datos['color_cuaternario'] ?? '#282b2f'
            ]);
        } catch (Exception $e) {
            return false;
        }
    }
}