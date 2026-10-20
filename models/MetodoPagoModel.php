<?php
class MetodoPagoModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function obtenerPorId($codigo) {
        $stmt = $this->db->prepare("SELECT *, codigo_metodo AS id FROM metodos_pago WHERE codigo_metodo = ? LIMIT 1");
        $stmt->execute([(string)$codigo]);
        $res = $stmt->fetch(PDO::FETCH_OBJ);
        if (!$res) {
            return (object)[
                'codigo_metodo' => (string)$codigo,
                'id'            => (string)$codigo,
                'nombre'        => 'Pago Móvil',
                'moneda'        => 'Bs',
                'requiere_referencia' => 1,
                'activo'        => 1
            ];
        }
        return $res;
    }

    public function obtenerBancosDisponibles() {
        try {
            $stmt = $this->db->query("
                SELECT DISTINCT banco 
                FROM pagos 
                WHERE banco IS NOT NULL AND TRIM(banco) != '' AND banco != 'Caja Física' AND banco != 'Sin banco'
                ORDER BY banco ASC
            ");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            return [];
        }
    }

   public function obtenerReportePagos($codigoMetodo, $desde = '', $hasta = '', $banco = '', $estado = '') {
        $where = ["p.codigo_metodo = ?"];
        $params = [(string)$codigoMetodo];

        if (!empty($desde)) {
            $where[] = "DATE(p.fecha_pago) >= ?";
            $params[] = $desde;
        }
        if (!empty($hasta)) {
            $where[] = "DATE(p.fecha_pago) <= ?";
            $params[] = $hasta;
        }
        if (!empty($banco) && $banco !== 'todos') {
            $where[] = "p.banco = ?";
            $params[] = $banco;
        }
        if (!empty($estado)) {
            $where[] = "p.estado = ?";
            $params[] = $estado;
        }

        $sql = "SELECT 
                    p.numero_pago AS id,
                    p.numero_pago,
                    p.numero_orden AS id_venta,
                    p.numero_orden,
                    p.fecha_pago,
                    p.banco,
                    p.referencia,
                    p.moneda,
                    p.monto,
                    p.tasa_cambio,
                    p.estado AS estado_verificacion,
                    COALESCE(c.nombre_razon_social, u.nombre, 'Consumidor Final') AS cliente_nombre,
                    COALESCE(c.nombre_razon_social, u.nombre, 'Consumidor Final') AS nombre_cliente
                FROM pagos p
                INNER JOIN ventas v ON p.numero_orden = v.numero_orden
                LEFT JOIN clientes c ON v.cedula_cliente = c.cedula
                LEFT JOIN usuarios u ON v.cedula_usuario = u.cedula
                WHERE " . implode(' AND ', $where) . "
                ORDER BY p.fecha_pago DESC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $pagos = $stmt->fetchAll(PDO::FETCH_OBJ);

            foreach ($pagos as $p) {
                $tasa = (float)($p->tasa_cambio ?? 1.0);
                if ($tasa <= 0) $tasa = 1.0;

                $montoRaw = (float)($p->monto ?? 0);
                $esUsd = strtoupper(trim($p->moneda ?? '')) === 'USD';

                if ($esUsd) {
                    $p->monto_usd = $montoRaw;
                    $p->monto_bs  = $montoRaw * $tasa;
                } else {
                    $p->monto_bs  = $montoRaw;
                    $p->monto_usd = $tasa > 0 ? ($montoRaw / $tasa) : $montoRaw;
                }

                // Alias para cubrir cualquier variable que use la vista
                $p->total    = $p->monto_usd;
                $p->total_bs = $p->monto_bs;
                $p->fecha    = $p->fecha_pago;
            }

            return $pagos;
        } catch (Exception $e) {
            return [];
        }
    }

    public function obtenerResumenTotales($codigoMetodo, $desde = '', $hasta = '', $banco = '', $estado = '') {
        $pagos = $this->obtenerReportePagos($codigoMetodo, $desde, $hasta, $banco, $estado);

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
        return $this->db->query("
            SELECT *, 
                   codigo_metodo AS id 
            FROM metodos_pago 
            ORDER BY nombre ASC
        ")->fetchAll(PDO::FETCH_OBJ);
    }

    public function existeNombre($nombre, $codigoIgnorar = '') {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM metodos_pago WHERE LOWER(nombre) = LOWER(?) AND codigo_metodo != ?");
        $stmt->execute([$nombre, (string)$codigoIgnorar]);
        return $stmt->fetchColumn() > 0;
    }

    public function guardar($datos) {
        $codigo = trim((string)($datos['codigo_metodo'] ?? $datos['id'] ?? ''));
        $nombre = trim((string)($datos['nombre'] ?? ''));
        $moneda = ($datos['moneda'] ?? 'Bs') === 'USD' ? 'USD' : 'Bs';
        $reqRef = !empty($datos['requiere_referencia']) ? 1 : 0;
        $enTienda = !empty($datos['en_tienda']) ? 1 : 0;
        $enOnline = !empty($datos['en_online']) ? 1 : 0;

        if (empty($codigo)) {
            // Generar identificador basado en el nombre (ej. pago_movil)
            $codigo = preg_replace('/[^a-z0-9_]+/i', '_', strtolower($nombre));
        }

        $stmtCheck = $this->db->prepare("SELECT COUNT(*) FROM metodos_pago WHERE codigo_metodo = ?");
        $stmtCheck->execute([$codigo]);

        if ((int)$stmtCheck->fetchColumn() > 0) {
            $stmt = $this->db->prepare("
                UPDATE metodos_pago 
                SET nombre = ?, moneda = ?, requiere_referencia = ?, en_tienda = ?, en_online = ? 
                WHERE codigo_metodo = ?
            ");
            return $stmt->execute([$nombre, $moneda, $reqRef, $enTienda, $enOnline, $codigo]);
        } else {
            $stmt = $this->db->prepare("
                INSERT INTO metodos_pago (codigo_metodo, nombre, moneda, requiere_referencia, en_tienda, en_online, activo) 
                VALUES (?, ?, ?, ?, ?, ?, 1)
            ");
            return $stmt->execute([$codigo, $nombre, $moneda, $reqRef, $enTienda, $enOnline]);
        }
    }

    public function cambiarEstado($codigo, $activo) {
        $stmt = $this->db->prepare("UPDATE metodos_pago SET activo = ? WHERE codigo_metodo = ?");
        return $stmt->execute([(int)$activo, (string)$codigo]);
    }
}