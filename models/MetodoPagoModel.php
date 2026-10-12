<?php
class MetodoPagoModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function obtenerPorId($id) {
        $stmt = $this->db->prepare("SELECT * FROM metodos_pago WHERE id = ?");
        $stmt->execute([(int)$id]);
        $res = $stmt->fetch(PDO::FETCH_OBJ);
        if (!$res) {
            return (object)[
                'id' => (int)$id,
                'nombre' => 'Pago Móvil'
            ];
        }
        return $res;
    }

    public function obtenerBancosDisponibles() {
        try {
            $stmt = $this->db->query("SELECT DISTINCT banco FROM pagos_registro WHERE banco IS NOT NULL AND TRIM(banco) != '' ORDER BY banco ASC");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            return [];
        }
    }

    public function obtenerReportePagos($idMetodo, $desde = '', $hasta = '', $banco = '', $estado = '') {
        $where = ["pr.id_metodo_pago = ?"];
        $params = [(int)$idMetodo];

        if (!empty($desde)) {
            $where[] = "DATE(pr.fecha_pago) >= ?";
            $params[] = $desde;
        }
        if (!empty($hasta)) {
            $where[] = "DATE(pr.fecha_pago) <= ?";
            $params[] = $hasta;
        }
        if (!empty($banco)) {
            $where[] = "pr.banco = ?";
            $params[] = $banco;
        }
        if (!empty($estado)) {
            $where[] = "pr.estado_verificacion = ?";
            $params[] = $estado;
        }

        $sql = "SELECT 
                    pr.id,
                    pr.id_venta,
                    pr.fecha_pago,
                    COALESCE(NULLIF(TRIM(pr.banco), ''), 'Sin banco asignado') AS banco,
                    COALESCE(pr.referencia, 'S/R') AS referencia,
                    pr.moneda,
                    pr.monto AS monto_bs_raw,
                    v.total AS monto_usd_raw,
                    COALESCE(NULLIF(pr.tasa_cambio, 0), 1) AS tasa_cambio,
                    pr.estado_verificacion,
                    CONCAT(COALESCE(u.nombre, 'Cliente Mostrador'), ' ', COALESCE(u.apellido, '')) AS cliente_nombre,
                    COALESCE(cajero.nombre, 'Caja Mostrador') AS cajero_nombre,
                    COALESCE(verificador.nombre, 'Admin') AS verificador_nombre
                FROM pagos_registro pr
                INNER JOIN ventas v ON pr.id_venta = v.id
                LEFT JOIN usuarios u ON v.id_usuario = u.id
                LEFT JOIN usuarios cajero ON v.vendedor_id = cajero.id
                LEFT JOIN usuarios verificador ON pr.verificado_por = verificador.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY pr.fecha_pago DESC, pr.id DESC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $pagos = $stmt->fetchAll(PDO::FETCH_OBJ);

            foreach ($pagos as $p) {
                $tasa = (float)$p->tasa_cambio > 0 ? (float)$p->tasa_cambio : 1;
                $p->monto = (float)($p->monto_usd_raw ?? 0);
                if ($p->monto <= 0 && (float)$p->monto_bs_raw > 0) {
                    $p->monto = (float)$p->monto_bs_raw / $tasa;
                }
                $p->monto_bs = (float)$p->monto_bs_raw > 0 ? (float)$p->monto_bs_raw : ($p->monto * $tasa);
            }

            return $pagos;
        } catch (Exception $e) {
            return [];
        }
    }

    public function obtenerResumenTotales($idMetodo, $desde = '', $hasta = '', $banco = '', $estado = '') {
        $pagos = $this->obtenerReportePagos($idMetodo, $desde, $hasta, $banco, $estado);

        $totalUsd = 0;
        $totalBs = 0;
        $ventasUnicas = [];
        $diasUnicos = [];

        foreach ($pagos as $p) {
            $totalUsd += (float)($p->monto ?? 0);
            $totalBs  += (float)($p->monto_bs ?? 0);
            $ventasUnicas[$p->id_venta] = true;
            $diasUnicos[date('Y-m-d', strtotime($p->fecha_pago))] = true;
        }

        return (object)[
            'total_usd'        => $totalUsd,
            'total_bs'         => $totalBs,
            'total_ventas'     => count($ventasUnicas),
            'dias_con_cobros'  => count($diasUnicos)
        ];
    }

    public function listar() {
        return $this->db->query("SELECT * FROM metodos_pago ORDER BY id ASC")->fetchAll(PDO::FETCH_OBJ);
    }

    public function existeNombre($nombre, $id = 0) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM metodos_pago WHERE LOWER(nombre) = LOWER(?) AND id != ?");
        $stmt->execute([$nombre, (int)$id]);
        return $stmt->fetchColumn() > 0;
    }

    public function guardar($datos) {
        $valores = [$datos['nombre'], $datos['moneda'], $datos['requiere_referencia'], $datos['en_tienda'], $datos['en_online'], $datos['datos']];
        if (empty($datos['id'])) {
            $stmt = $this->db->prepare("INSERT INTO metodos_pago (nombre, moneda, requiere_referencia, en_tienda, en_online, datos, activo) VALUES (?, ?, ?, ?, ?, ?, 1)");
            return $stmt->execute($valores);
        }
        $valores[] = (int)$datos['id'];
        $stmt = $this->db->prepare("UPDATE metodos_pago SET nombre = ?, moneda = ?, requiere_referencia = ?, en_tienda = ?, en_online = ?, datos = ? WHERE id = ?");
        return $stmt->execute($valores);
    }

    public function cambiarEstado($id, $activo) {
        $stmt = $this->db->prepare("UPDATE metodos_pago SET activo = ? WHERE id = ?");
        return $stmt->execute([(int)$activo, (int)$id]);
    }
}