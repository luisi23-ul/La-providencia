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
    public function obtenerResumenTurno($cedulaCajero, $numeroTurno) {
        $stmtT = $this->db->prepare("SELECT * FROM turnos_caja WHERE cedula_cajero = ? AND numero_turno = ? LIMIT 1");
        $stmtT->execute([$cedulaCajero, $numeroTurno]);
        $turno = $stmtT->fetch(PDO::FETCH_OBJ);
        if (!$turno) return null;

        $fechaInicio = $turno->fecha_apertura;
        $fechaFin    = $turno->fecha_cierre ?: date('Y-m-d H:i:s');

        // Totalizar pagos aprobados registrados por ese cajero en tienda física durante el turno
        $sql = "SELECT p.codigo_metodo, 
                       mp.nombre AS metodo_nombre,
                       UPPER(TRIM(p.moneda)) AS moneda,
                       COUNT(p.numero_pago) AS cantidad_transacciones,
                       SUM(p.monto) AS total_monto,
                       SUM(CASE 
                           WHEN UPPER(TRIM(p.moneda)) = 'USD' THEN p.monto 
                           ELSE (p.monto / NULLIF(p.tasa_cambio, 0)) 
                       END) AS total_usd,
                       SUM(CASE 
                           WHEN UPPER(TRIM(p.moneda)) = 'USD' THEN (p.monto * COALESCE(NULLIF(p.tasa_cambio, 0), ?)) 
                           ELSE p.monto 
                       END) AS total_bs
                FROM pagos p
                INNER JOIN ventas v ON p.numero_orden = v.numero_orden
                LEFT JOIN metodos_pago mp ON p.codigo_metodo = mp.codigo_metodo
                WHERE p.estado = 'aprobado'
                  AND v.origen_venta IN ('tienda', 'tienda_fisica', 'pos')
                  AND p.fecha_pago BETWEEN ? AND ?
                GROUP BY p.codigo_metodo, mp.nombre, UPPER(TRIM(p.moneda))";

        $stmtP = $this->db->prepare($sql);
        $stmtP->execute([$turno->tasa_apertura, $fechaInicio, $fechaFin]);
        $desglose = $stmtP->fetchAll(PDO::FETCH_OBJ);

        return [
            'turno'    => $turno,
            'desglose' => $desglose
        ];
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