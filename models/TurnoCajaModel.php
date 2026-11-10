<?php
class TurnoCajaModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // Obtener turno actualmente abierto de un cajero o del sistema
    public function obtenerTurnoAbierto($cedulaCajero = null) {
        $sql = "SELECT * FROM turnos_caja WHERE estado = 'abierto'";
        $params = [];
        if (!empty($cedulaCajero)) {
            $sql .= " AND cedula_cajero = ?";
            $params[] = $cedulaCajero;
        }
        $sql .= " ORDER BY fecha_apertura DESC LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // Iniciar turno calculando el siguiente numero_turno para ese cajero
    public function iniciarTurno($datos) {
        try {
            $cedula = $datos['cedula_cajero'];

            // Obtener el siguiente correlativo para este cajero
            $stmtN = $this->db->prepare("SELECT COALESCE(MAX(numero_turno), 0) + 1 FROM turnos_caja WHERE cedula_cajero = ?");
            $stmtN->execute([$cedula]);
            $siguienteTurno = (int)$stmtN->fetchColumn();

            $sql = "INSERT INTO turnos_caja (
                        cedula_cajero, numero_turno, fecha_apertura, fondo_usd, 
                        fondo_bs, tasa_apertura, nota_apertura, estado
                    ) VALUES (
                        :cedula, :numero, NOW(), :fondo_usd, 
                        :fondo_bs, :tasa, :nota, 'abierto'
                    )";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':cedula'    => $cedula,
                ':numero'    => $siguienteTurno,
                ':fondo_usd' => (float)($datos['fondo_usd'] ?? 0),
                ':fondo_bs'  => (float)($datos['fondo_bs'] ?? 0),
                ':tasa'      => (float)$datos['tasa_apertura'],
                ':nota'      => $datos['nota_apertura'] ?? null
            ]);
        } catch (Exception $e) {
            return false;
        }
    }

    // Obtener detalle y resumen de cobros dentro del rango del turno
   public function obtenerResumenTurno($cedula, $numeroTurno) {
        $stmt = $this->db->prepare("SELECT * FROM turnos_caja WHERE cedula_cajero = ? AND numero_turno = ? LIMIT 1");
        $stmt->execute([$cedula, $numeroTurno]);
        $turno = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$turno) return null;

        $fechaInicio = $turno->fecha_apertura;
        $fechaFin    = (!empty($turno->fecha_cierre) && $turno->fecha_cierre !== '2000-01-01 00:00:00') 
                       ? $turno->fecha_cierre 
                       : date('Y-m-d H:i:s');

        // 1. Efectivo cobrado en USD
        $stmtCobradoUsd = $this->db->prepare("
            SELECT COALESCE(SUM(p.monto), 0)
            FROM pagos p
            WHERE p.cedula_cajero = ?
              AND p.codigo_metodo = 'efectivo_usd'
              AND p.estado IN ('aprobado', 'verificado', 'completado')
              AND p.fecha_pago >= ?
              AND p.fecha_pago <= ?
        ");
        $stmtCobradoUsd->execute([$cedula, $fechaInicio, $fechaFin]);
        $cobradoUsd = (float)$stmtCobradoUsd->fetchColumn();

        // 2. Efectivo cobrado en Bs
        $stmtCobradoBs = $this->db->prepare("
            SELECT COALESCE(SUM(p.monto), 0)
            FROM pagos p
            WHERE p.cedula_cajero = ?
              AND p.codigo_metodo = 'efectivo_bs'
              AND p.estado IN ('aprobado', 'verificado', 'completado')
              AND p.fecha_pago >= ?
              AND p.fecha_pago <= ?
        ");
        $stmtCobradoBs->execute([$cedula, $fechaInicio, $fechaFin]);
        $cobradoBs = (float)$stmtCobradoBs->fetchColumn();

        // 3. Vueltos entregados (si existen en ventas)
        $vueltoBs = 0;
        $vueltoUsd = 0;
        try {
            $stmtVueltos = $this->db->prepare("
                SELECT 
                    COALESCE(SUM(CASE WHEN LOWER(COALESCE(metodo_vuelto, 'efectivo_bs')) LIKE '%bs%' THEN vuelto_bs ELSE 0 END), 0) AS total_vuelto_bs,
                    COALESCE(SUM(CASE WHEN LOWER(COALESCE(metodo_vuelto, 'efectivo_usd')) LIKE '%usd%' THEN vuelto_usd ELSE 0 END), 0) AS total_vuelto_usd
                FROM ventas
                WHERE fecha >= ? AND fecha <= ? AND estado NOT IN ('cancelado', 'anulado')
            ");
            $stmtVueltos->execute([$fechaInicio, $fechaFin]);
            $filaV = $stmtVueltos->fetch(PDO::FETCH_OBJ);
            if ($filaV) {
                $vueltoBs  = (float)$filaV->total_vuelto_bs;
                $vueltoUsd = (float)$filaV->total_vuelto_usd;
            }
        } catch (Throwable $e) {
            $vueltoBs = 0;
            $vueltoUsd = 0;
        }

        $netoUsd = $cobradoUsd - $vueltoUsd;
        $netoBs  = $cobradoBs  - $vueltoBs;

        $esperadoUsd = (float)$turno->fondo_usd + $netoUsd;
        $esperadoBs  = (float)$turno->fondo_bs  + $netoBs;

        return [
            'turno'                => $turno,
            'fondo_usd'            => (float)$turno->fondo_usd,
            'fondo_bs'             => (float)$turno->fondo_bs,
            'cobrado_efectivo_usd' => $netoUsd,
            'cobrado_efectivo_bs'  => $netoBs,
            'vuelto_usd'           => $vueltoUsd,
            'vuelto_bs'            => $vueltoBs,
            'esperado_usd'         => $esperadoUsd,
            'esperado_bs'          => $esperadoBs,
            'desglose'             => $this->obtenerDesgloseMetodos($cedula, $fechaInicio, $fechaFin)
        ];
    }

    public function obtenerDesgloseMetodos($cedula, $fechaInicio, $fechaFin) {
        $sql = "SELECT p.codigo_metodo,
                       COALESCE(mp.nombre, p.codigo_metodo) AS metodo_nombre,
                       COUNT(*) AS cantidad_transacciones,
                       SUM(p.monto) AS total_monto,
                       p.moneda,
                       SUM(CASE 
                           WHEN UPPER(TRIM(p.moneda)) IN ('BS', 'VES') THEN ROUND(p.monto / NULLIF(p.tasa_cambio, 0), 2)
                           ELSE p.monto 
                       END) AS total_usd,
                       SUM(CASE 
                           WHEN UPPER(TRIM(p.moneda)) IN ('BS', 'VES') THEN p.monto
                           ELSE ROUND(p.monto * COALESCE(NULLIF(p.tasa_cambio, 0), 1), 2) 
                       END) AS total_bs
                FROM pagos p
                LEFT JOIN metodos_pago mp ON p.codigo_metodo = mp.codigo_metodo
                WHERE p.cedula_cajero = ?
                  AND p.estado IN ('aprobado', 'verificado', 'completado')
                  AND p.fecha_pago >= ?
                  AND p.fecha_pago <= ?
                GROUP BY p.codigo_metodo, p.moneda";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$cedula, $fechaInicio, $fechaFin]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // Cerrar el turno actual
    public function cerrarTurno($cedulaCajero, $numeroTurno, $cierreUsd, $cierreBs, $notaCierre = '') {
        $sql = "UPDATE turnos_caja 
                SET fecha_cierre = NOW(), 
                    cierre_usd   = :cierre_usd, 
                    cierre_bs    = :cierre_bs, 
                    nota_cierre  = :nota_cierre, 
                    estado       = 'cerrado' 
                WHERE cedula_cajero = :cedula AND numero_turno = :numero AND estado = 'abierto'
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':cierre_usd' => (float)$cierreUsd,
            ':cierre_bs'  => (float)$cierreBs,
            ':nota_cierre'=> $notaCierre,
            ':cedula'     => $cedulaCajero,
            ':numero'     => (int)$numeroTurno
        ]);
    }

    public function listarTodos($desde = '', $hasta = '', $cedulaCajero = '') {
        $sql = "SELECT tc.*, 
                       u.nombre AS cajero_nombre, 
                       u.apellido AS cajero_apellido
                FROM turnos_caja tc
                INNER JOIN usuarios u ON tc.cedula_cajero = u.cedula
                WHERE 1=1";
        $params = [];

        if (!empty($cedulaCajero) && $cedulaCajero !== 'todos') {
            $sql .= " AND tc.cedula_cajero = ?";
            $params[] = $cedulaCajero;
        }
        if (!empty($desde)) {
            $sql .= " AND DATE(tc.fecha_apertura) >= ?";
            $params[] = $desde;
        }
        if (!empty($hasta)) {
            $sql .= " AND DATE(tc.fecha_apertura) <= ?";
            $params[] = $hasta;
        }

        $sql .= " ORDER BY tc.fecha_apertura DESC, tc.numero_turno DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
}