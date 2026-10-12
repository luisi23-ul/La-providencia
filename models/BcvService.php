<?php

class BcvService {
    private $db;

    public function __construct($db = null) {
        if ($db instanceof PDO) {
            $this->db = $db;
        } elseif (isset($GLOBALS['db']) && $GLOBALS['db'] instanceof PDO) {
            $this->db = $GLOBALS['db'];
        } elseif (isset($GLOBALS['conexion']) && $GLOBALS['conexion'] instanceof PDO) {
            $this->db = $GLOBALS['conexion'];
        } else {
            // Intentar conectar con la clase estándar del proyecto
            if (file_exists(__DIR__ . '/conexion.php')) {
                require_once __DIR__ . '/conexion.php';
            } elseif (file_exists(__DIR__ . '/Conexion.php')) {
                require_once __DIR__ . '/Conexion.php';
            }

            if (class_exists('Conexion')) {
                $this->db = Conexion::conectar();
            }
        }
    }

    /**
     * Obtiene la tasa activa según la configuración del sistema (automática o manual)
     */
    public function obtenerTasaActiva() {
        if (!$this->db) {
            return 526.87; // Fallback de seguridad si no hay BD conectada
        }

        try {
            $stmt = $this->db->query("SELECT tasa_modo, tasa_manual, tasa_bcv, fecha_tasa FROM sistema LIMIT 1");
            $config = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$config) {
                return 526.87;
            }

            // Si el modo es manual, respetar el monto fijado por el usuario
            if (($config['tasa_modo'] ?? '') === 'manual') {
                return (float)($config['tasa_manual'] ?? 526.87);
            }

            // Modo automático: comprobar si ya se actualizó hoy
            date_default_timezone_set('America/Caracas');
            $hoy = date('Y-m-d');
            $fechaUltima = !empty($config['fecha_tasa']) ? date('Y-m-d', strtotime($config['fecha_tasa'])) : '';

            if ($fechaUltima === $hoy && !empty($config['tasa_bcv']) && (float)$config['tasa_bcv'] > 0) {
                return (float)$config['tasa_bcv'];
            }

            // Consultar la API del BCV
            $tasaApi = $this->consultarApiBcv();
            if ($tasaApi > 0) {
                $this->actualizarTasaBcvEnBD($tasaApi);
                return $tasaApi;
            }

            // Si la API falla momentáneamente, usar la última tasa registrada
            return (float)($config['tasa_bcv'] ?: ($config['tasa_manual'] ?: 526.87));

        } catch (Exception $e) {
            return 526.87;
        }
    }

    /**
     * Consulta la tasa oficial del BCV mediante API JSON
     */
    public function consultarApiBcv() {
        $url = 'https://ve.dolarapi.com/v1/dolares/oficial';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $respuesta = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $respuesta) {
            $datos = json_decode($respuesta, true);
            if (isset($datos['promedio']) && (float)$datos['promedio'] > 0) {
                return (float)$datos['promedio'];
            }
        }

        // Respaldo secundario
        $urlFallback = 'https://pydolarvenezuela-api.vercel.app/api/v1/dollar?page=bcv';
        $ch2 = curl_init();
        curl_setopt($ch2, CURLOPT_URL, $urlFallback);
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch2, CURLOPT_TIMEOUT, 6);
        curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
        $respuesta2 = curl_exec($ch2);
        curl_close($ch2);

        if ($respuesta2) {
            $datos2 = json_decode($respuesta2, true);
            if (isset($datos2['monitors']['usd']['price'])) {
                return (float)$datos2['monitors']['usd']['price'];
            }
        }

        return 0.0;
    }

    /**
     * Guarda la tasa obtenida del BCV en la base de datos
     */
    public function actualizarTasaBcvEnBD($tasa) {
        if (!$this->db) return false;
        try {
            $stmt = $this->db->prepare("UPDATE sistema SET tasa_bcv = ?, fecha_tasa = NOW() WHERE id = 1");
            return $stmt->execute([$tasa]);
        } catch (Exception $e) {
            return false;
        }
    }
}