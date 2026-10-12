<?php
class VentaController {
    private $db;

    public function __construct($db = null) {
        date_default_timezone_set('America/Caracas');

        if ($db) {
            $this->db = $db;
        } else {
            require_once 'config/db.php';
            $this->db = Database::connect();
        }

        try {
            $this->db->exec("SET time_zone = '-04:00'");
        } catch (Exception $e) {}
    }

    private function log($modulo, $accion, $descripcion) {
        try {
            $idUsuario = $_SESSION['id_usuario'] ?? null;
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $stmt = $this->db->prepare("INSERT INTO auditoria_logs (id_usuario, modulo, accion, descripcion, ip) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$idUsuario, $modulo, $accion, $descripcion, $ip]);
        } catch (Exception $e) {}
    }

    // =========================================================================
    // 1. PUNTO DE VENTA (POS TIENDA FÍSICA)
    // =========================================================================
    public function puntoVenta() {
       $sqlP = "SELECT p.id, p.codigo, p.nombre_producto, p.precio, p.costo_compra,
                        p.stock, p.stock_minimo, p.imagen, p.id_categoria,
                        c.nombre_categoria, c.id_padre,
                        cp.nombre_categoria AS nombre_categoria_padre
                 FROM productos p
                 LEFT JOIN categorias c ON p.id_categoria = c.id
                 LEFT JOIN categorias cp ON c.id_padre = cp.id
                 WHERE p.estado = 1 AND p.deleted_at IS NULL
                 ORDER BY p.nombre_producto ASC";
        $productos = $this->db->query($sqlP)->fetchAll(PDO::FETCH_OBJ);

        // Obtener variantes (talla, color, modelo, stock) de cada producto
        $stmtVar = $this->db->prepare("SELECT id, id_producto, talla, color, modelo, stock 
                                       FROM producto_variantes 
                                       WHERE id_producto = ? AND stock > 0");

        foreach ($productos as $p) {
            $stmtVar->execute([$p->id]);
            $p->variantes = $stmtVar->fetchAll(PDO::FETCH_OBJ);
        }

        $sqlM = "SELECT id, nombre, moneda, requiere_referencia, en_tienda, activo 
                 FROM metodos_pago 
                 WHERE activo = 1 AND en_tienda = 1 
                 ORDER BY id ASC";
        $metodos = $this->db->query($sqlM)->fetchAll(PDO::FETCH_OBJ);

        $sqlC = "SELECT id, cedula, nombre, apellido, telefono 
                 FROM usuarios 
                 WHERE id_rol = 3 AND deleted_at IS NULL 
                 ORDER BY nombre ASC";
        $clientes = $this->db->query($sqlC)->fetchAll(PDO::FETCH_OBJ);

        $ventaRegistrada = $_SESSION['venta_comprobante'] ?? null;
        unset($_SESSION['venta_comprobante']);

        require_once 'views/ventas/punto_venta.php';
    }

    public function guardarVentaTienda() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?action=punto_venta");
            exit();
        }

        try {
            $this->db->beginTransaction();

            $idUsuario = !empty($_POST['id_usuario']) ? (int)$_POST['id_usuario'] : null;
            $vendedorId = $_SESSION['id_usuario'] ?? null;

            if (!$idUsuario && !empty($_POST['nuevo_cliente']['nombre'])) {
                $nc = $_POST['nuevo_cliente'];
                $stmtNC = $this->db->prepare("
                    INSERT INTO usuarios (cedula, nombre, apellido, telefono, direccion, correo, clave, id_rol, estado)
                    VALUES (?, ?, '', ?, ?, CONCAT('cliente_', UNIX_TIMESTAMP(), '@providencia.local'), '', 3, 1)
                ");
                $stmtNC->execute([
                    trim($nc['cedula'] ?? ''),
                    trim($nc['nombre']),
                    trim($nc['telefono'] ?? ''),
                    trim($nc['direccion'] ?? '')
                ]);
                $idUsuario = (int)$this->db->lastInsertId();
            }

            if (!$idUsuario) {
                $stmtDef = $this->db->query("SELECT id FROM usuarios WHERE id_rol = 3 LIMIT 1");
                $idUsuario = (int)$stmtDef->fetchColumn() ?: ($vendedorId ?: 1);
            }

            $total = (float)$_POST['total'];
            $vuelto = (float)($_POST['vuelto'] ?? 0);
            $vueltoMoneda = $_POST['vuelto_moneda'] ?? 'USD';

            $stmtV = $this->db->prepare("
                INSERT INTO ventas (id_usuario, origen_venta, vendedor_id, total, vuelto, vuelto_moneda, estado, fecha)
                VALUES (?, 'tienda_fisica', ?, ?, ?, ?, 'pendiente', NOW())
            ");
            $stmtV->execute([$idUsuario, $vendedorId, $total, $vuelto, $vueltoMoneda]);
            $idVenta = (int)$this->db->lastInsertId();

            $prodIds    = $_POST['id_producto'] ?? [];
            $cants      = $_POST['cantidad'] ?? [];
            $precios    = $_POST['precio_unitario'] ?? [];
            $descuentos = $_POST['descuento'] ?? [];

            $stmtD = $this->db->prepare("
                INSERT INTO detalle_ventas (id_venta, id_producto, cantidad, costo_unitario, precio_unitario, descuento, subtotal)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtStock = $this->db->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");
            $stmtKardex = $this->db->prepare("
                INSERT INTO kardex_inventario 
                (id_producto, tipo_movimiento, canal, cantidad, stock_tienda_resultante, stock_online_resultante, id_referencia, observacion, id_usuario)
                VALUES (?, 'venta_tienda', 'tienda_fisica', ?, ?, ?, ?, ?, ?)
            ");

            $lineasComprobante = [];
            for ($i = 0; $i < count($prodIds); $i++) {
                $pId   = (int)$prodIds[$i];
                $cnt   = (int)$cants[$i];
                $prc   = (float)$precios[$i];
                $desc  = (float)($descuentos[$i] ?? 0);
                $subt  = ($cnt * $prc) * (1 - ($desc / 100));

                $stmtProd = $this->db->prepare("SELECT costo_compra, stock, stock_online, nombre_producto FROM productos WHERE id = ?");
                $stmtProd->execute([$pId]);
                $prodActual = $stmtProd->fetch(PDO::FETCH_OBJ);

                $costoU = (float)($prodActual->costo_compra ?? 0);
                $stmtD->execute([$idVenta, $pId, $cnt, $costoU, $prc, $desc, $subt]);

                $stmtStock->execute([$cnt, $pId]);
                $nuevoStock = max(0, (int)$prodActual->stock - $cnt);

                $stmtKardex->execute([
                    $pId, -$cnt, $nuevoStock, (int)$prodActual->stock_online,
                    $idVenta, "Venta en caja #{$idVenta}", $vendedorId ?: 1
                ]);

                $lineasComprobante[] = [
                    'nombre'    => $prodActual->nombre_producto,
                    'cantidad'  => $cnt,
                    'descuento' => $desc,
                    'subtotal'  => $subt
                ];
            }

            $metodoIds = $_POST['id_metodo_pago'] ?? [];
            $montos    = $_POST['monto'] ?? [];
            $tasas     = $_POST['tasa_cambio'] ?? [];
            $refs      = $_POST['referencia'] ?? [];
            $bancos    = $_POST['banco'] ?? [];
            $titulares = $_POST['titular_cedula'] ?? [];
            $telefonos = $_POST['titular_telefono'] ?? [];

            $stmtP = $this->db->prepare("
                INSERT INTO pagos_registro 
                (id_venta, id_metodo_pago, banco, moneda, monto, tasa_cambio, referencia, observacion, estado_verificacion, verificado_por, fecha_verificacion, fecha_pago)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            $todosAprobados = true;
            $pagosComprobante = [];
            $primerMetodoId = null;

            for ($j = 0; $j < count($metodoIds); $j++) {
                $mId = (int)$metodoIds[$j];
                $mnt = (float)$montos[$j];
                $tsa = !empty($tasas[$j]) ? (float)$tasas[$j] : 1.0;
                $rf  = trim($refs[$j] ?? '');
                $bnk = trim($bancos[$j] ?? '');
                $ced = trim($titulares[$j] ?? '');
                $tel = trim($telefonos[$j] ?? '');

                if ($mnt <= 0) continue;
                if (!$primerMetodoId) $primerMetodoId = $mId;

                $stmtM = $this->db->prepare("SELECT nombre, moneda, requiere_referencia FROM metodos_pago WHERE id = ?");
                $stmtM->execute([$mId]);
                $mObj = $stmtM->fetch(PDO::FETCH_OBJ);

                $esEfectivo = stripos($mObj->nombre ?? '', 'efectivo') !== false || empty($mObj->requiere_referencia);

                if ($esEfectivo) {
                    $stmtP->execute([$idVenta, $mId, 'Caja Física', $mObj->moneda, $mnt, $tsa, 'No aplica', 'Cobro en efectivo', 'aprobado', $vendedorId, date('Y-m-d H:i:s')]);
                    $pagosComprobante[] = [
                        'metodo'     => $mObj->nombre ?? 'Efectivo',
                        'monto'      => $mnt,
                        'moneda'     => $mObj->moneda ?? 'USD',
                        'referencia' => 'No aplica'
                    ];
                } else {
                    if (empty($rf)) {
                        throw new Exception("El método {$mObj->nombre} requiere número de referencia.");
                    }

                    $stmtChk = $this->db->prepare("
                        SELECT id, id_venta 
                        FROM pagos_registro 
                        WHERE referencia = ? AND banco = ? AND estado_verificacion IN ('pendiente', 'aprobado')
                        LIMIT 1
                    ");
                    $stmtChk->execute([$rf, $bnk]);
                    $dup = $stmtChk->fetch(PDO::FETCH_OBJ);

                    if ($dup) {
                        throw new Exception("La referencia '{$rf}' del banco '{$bnk}' ya fue utilizada en la venta #{$dup->id_venta}. No se puede duplicar.");
                    }

                    $obsBancaria = "Titular: {$ced}" . (!empty($tel) ? " | Tel: {$tel}" : "");
                    $stmtP->execute([$idVenta, $mId, $bnk, $mObj->moneda, $mnt, $tsa, $rf, $obsBancaria, 'pendiente', null, null]);
                    $todosAprobados = false;

                    $pagosComprobante[] = [
                        'metodo'     => $mObj->nombre ?? 'Bancario',
                        'monto'      => $mnt,
                        'moneda'     => $mObj->moneda ?? 'Bs',
                        'referencia' => $rf
                    ];
                }
            }

            if ($todosAprobados) {
                $stmtUpdV = $this->db->prepare("UPDATE ventas SET estado = 'retirado', id_metodo_pago = ? WHERE id = ?");
                $stmtUpdV->execute([$primerMetodoId, $idVenta]);
            } else {
                $stmtUpdV = $this->db->prepare("UPDATE ventas SET id_metodo_pago = ? WHERE id = ?");
                $stmtUpdV->execute([$primerMetodoId, $idVenta]);
            }

            $this->db->commit();
            $this->log('Ventas', 'INSERT', "Venta en caja #{$idVenta} por $" . number_format($total, 2));

            $stmtCl = $this->db->prepare("SELECT nombre, apellido, telefono FROM usuarios WHERE id = ?");
            $stmtCl->execute([$idUsuario]);
            $clObj = $stmtCl->fetch(PDO::FETCH_OBJ);

            $_SESSION['venta_comprobante'] = [
                'id'            => $idVenta,
                'total'         => $total,
                'vuelto'        => $vuelto,
                'vuelto_moneda' => $vueltoMoneda,
                'cliente'       => ($clObj ? trim($clObj->nombre . ' ' . $clObj->apellido) : 'Consumidor Final'),
                'telefono'      => $clObj->telefono ?? '',
                'fecha'         => date('d/m/Y H:i'),
                'lineas'        => $lineasComprobante,
                'pagos'         => $pagosComprobante
            ];

            header("Location: index.php?action=punto_venta");
            exit();

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error en guardarVentaTienda: " . $e->getMessage());
            echo "<script>alert('Error procesando venta: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
            exit();
        }
    }

    // =========================================================================
    // 2. HISTORIAL DE VENTAS CON FILTROS NORMALIZADOS
    // =========================================================================
    public function listarVentas() {
        $q      = trim($_GET['q'] ?? '');
        $desde  = trim($_GET['desde'] ?? '');
        $hasta  = trim($_GET['hasta'] ?? '');
        $estado = trim($_GET['estado'] ?? '');
        $origen = trim($_GET['origen'] ?? '');

        $where = ["v.deleted_at IS NULL"];
        $params = [];

        if ($q !== '') {
            $num = ltrim(preg_replace('/[^0-9]/', '', $q), '0');
            if ($num !== '') {
                $where[] = "(v.id = ? OR u.nombre LIKE ? OR u.apellido LIKE ?)";
                $params[] = (int)$num;
                $params[] = "%$q%";
                $params[] = "%$q%";
            } else {
                $where[] = "(u.nombre LIKE ? OR u.apellido LIKE ?)";
                $params[] = "%$q%";
                $params[] = "%$q%";
            }
        }

        $normalizarFecha = function($str) {
            if (empty($str)) return null;
            if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $str, $m)) {
                return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
            }
            if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $str, $m)) {
                return sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]);
            }
            $t = strtotime($str);
            return $t ? date('Y-m-d', $t) : null;
        };

        $fechaDesdeNorm = $normalizarFecha($desde);
        $fechaHastaNorm = $normalizarFecha($hasta);

        if ($fechaDesdeNorm) {
            $where[] = "v.fecha >= ?";
            $params[] = $fechaDesdeNorm . " 00:00:00";
        }
        if ($fechaHastaNorm) {
            $where[] = "v.fecha <= ?";
            $params[] = $fechaHastaNorm . " 23:59:59";
        }
        if ($estado !== '') {
            $where[] = "v.estado = ?";
            $params[] = $estado;
        }
        if ($origen !== '') {
            $where[] = "v.origen_venta = ?";
            $params[] = $origen;
        }

        $sql = "SELECT v.id, v.fecha, 
                       CONCAT(COALESCE(u.nombre, 'Consumidor'), ' ', COALESCE(u.apellido, '')) AS nombre_cliente,
                       v.origen_venta, 
                       COALESCE(mp.nombre, (
                           SELECT mp2.nombre 
                           FROM pagos_registro pr2 
                           INNER JOIN metodos_pago mp2 ON pr2.id_metodo_pago = mp2.id 
                           WHERE pr2.id_venta = v.id 
                           LIMIT 1
                       ), 'POS / Mixto') AS nombre_metodo,
                       v.total, v.estado
                FROM ventas v
                LEFT JOIN usuarios u ON v.id_usuario = u.id
                LEFT JOIN metodos_pago mp ON v.id_metodo_pago = mp.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY v.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $ventas = $stmt->fetchAll(PDO::FETCH_OBJ);

        require_once 'views/ventas/ventas.php';
    }

    // =========================================================================
    // 3. BANDEJA DE VERIFICACIÓN DE PAGOS
    // =========================================================================
    public function verificarPagos() {
        $estado = $_GET['estado'] ?? 'pendiente';
        $fecha  = trim($_GET['fecha'] ?? '');

        $where = ["pr.estado_verificacion = ?"];
        $params = [$estado];

        if ($fecha !== '') {
            $timeF = strtotime(str_replace('/', '-', $fecha));
            if ($timeF) {
                $where[] = "DATE(pr.fecha_pago) = ?";
                $params[] = date('Y-m-d', $timeF);
            }
        }

        $sql = "SELECT pr.id, pr.id_venta, 
                       CONCAT(COALESCE(u.nombre, 'Cliente'), ' ', COALESCE(u.apellido, '')) AS cliente,
                       u.cedula AS cliente_cedula,
                       u.telefono AS cliente_telefono,
                       COALESCE(mp.nombre, 'Sin método') AS metodo, 
                       mp.requiere_referencia,
                       pr.moneda, pr.monto, pr.tasa_cambio, pr.referencia, pr.banco,
                       v.origen_venta AS origen, pr.comprobante_img, pr.fecha_pago, pr.estado_verificacion,
                       COALESCE(cajero.nombre, 'Sistema Online') AS registrado_por_nombre,
                       COALESCE(adm.nombre, '') AS verificado_por_nombre, 
                       pr.fecha_verificacion, pr.observacion,
                       v.total AS total_venta,
                       (SELECT COALESCE(SUM(pr2.monto), 0) FROM pagos_registro pr2 WHERE pr2.id_venta = v.id AND pr2.estado_verificacion = 'aprobado') AS pagado_aprobado
                FROM pagos_registro pr
                INNER JOIN ventas v ON pr.id_venta = v.id
                LEFT JOIN usuarios u ON v.id_usuario = u.id
                LEFT JOIN usuarios cajero ON v.vendedor_id = cajero.id
                LEFT JOIN metodos_pago mp ON pr.id_metodo_pago = mp.id
                LEFT JOIN usuarios adm ON pr.verificado_por = adm.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY pr.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $pagos = $stmt->fetchAll(PDO::FETCH_OBJ);

        $stmtC = $this->db->query("SELECT COUNT(*) FROM pagos_registro WHERE estado_verificacion = 'pendiente'");
        $conteo = ['pendiente' => (int)$stmtC->fetchColumn()];

        require_once 'views/ventas/verificar_pagos.php';
    }

    public function aprobarPago() {
        $idPago = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        $idAdmin = $_SESSION['id_usuario'] ?? null;

        if ($idPago > 0) {
            $stmtP = $this->db->prepare("SELECT id_venta, monto, id_metodo_pago FROM pagos_registro WHERE id = ?");
            $stmtP->execute([$idPago]);
            $pago = $stmtP->fetch(PDO::FETCH_OBJ);

            if ($pago) {
                $stmtUpd = $this->db->prepare("
                    UPDATE pagos_registro 
                    SET estado_verificacion = 'aprobado', verificado_por = ?, fecha_verificacion = NOW() 
                    WHERE id = ?
                ");
                $stmtUpd->execute([$idAdmin, $idPago]);

                $stmtTot = $this->db->prepare("SELECT total, origen_venta FROM ventas WHERE id = ?");
                $stmtTot->execute([$pago->id_venta]);
                $ventaActual = $stmtTot->fetch(PDO::FETCH_OBJ);

                $stmtAprob = $this->db->prepare("SELECT COALESCE(SUM(monto), 0) FROM pagos_registro WHERE id_venta = ? AND estado_verificacion = 'aprobado'");
                $stmtAprob->execute([$pago->id_venta]);
                $totalAprobado = (float)$stmtAprob->fetchColumn();

                if ($totalAprobado >= (float)$ventaActual->total) {
                    $nuevoEstado = ($ventaActual->origen_venta === 'tienda_fisica') ? 'retirado' : 'pagado';
                    $stmtVentaUpd = $this->db->prepare("UPDATE ventas SET estado = ?, id_metodo_pago = ? WHERE id = ?");
                    $stmtVentaUpd->execute([$nuevoEstado, $pago->id_metodo_pago, $pago->id_venta]);
                }

                $this->log('Ventas', 'VERIFICACION', "Aprobó pago ID: {$idPago} venta #{$pago->id_venta}");

                $_SESSION['alerta'] = [
                    'tipo' => 'success',
                    'titulo' => '¡Pago aprobado!',
                    'texto' => "El pago ID #{$idPago} fue verificado y sumado a Pagos por banco.",
                    'deshacer_id' => $idPago
                ];
            }
        }

        header("Location: index.php?action=verificar_pagos&estado=pendiente");
        exit();
    }

    public function rechazarPago() {
        $idPago = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        $observacion = trim($_POST['observacion'] ?? $_GET['observacion'] ?? 'Comprobante no coincide');
        $idAdmin = $_SESSION['id_usuario'] ?? null;

        if ($idPago > 0) {
            $stmt = $this->db->prepare("
                UPDATE pagos_registro 
                SET estado_verificacion = 'rechazado', observacion = ?, verificado_por = ?, fecha_verificacion = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$observacion, $idAdmin, $idPago]);

            $this->log('Ventas', 'VERIFICACION', "Rechazó pago ID: {$idPago}. Motivo: {$observacion}");

            $_SESSION['alerta'] = [
                'tipo' => 'warning',
                'titulo' => 'Pago rechazado',
                'texto' => "El pago ID #{$idPago} ha sido marcado como rechazado.",
                'deshacer_id' => $idPago
            ];
        }

        header("Location: index.php?action=verificar_pagos&estado=pendiente");
        exit();
    }

    public function deshacerPago() {
        $idPago = (int)($_GET['id'] ?? 0);

        if ($idPago > 0) {
            $stmtP = $this->db->prepare("SELECT id_venta FROM pagos_registro WHERE id = ?");
            $stmtP->execute([$idPago]);
            $pago = $stmtP->fetch(PDO::FETCH_OBJ);

            if ($pago) {
                $stmtUpd = $this->db->prepare("
                    UPDATE pagos_registro 
                    SET estado_verificacion = 'pendiente', verificado_por = NULL, fecha_verificacion = NULL, observacion = NULL 
                    WHERE id = ?
                ");
                $stmtUpd->execute([$idPago]);

                $this->db->prepare("UPDATE ventas SET estado = 'pendiente' WHERE id = ? AND estado != 'retirado'")->execute([$pago->id_venta]);

                $this->log('Ventas', 'VERIFICACION', "Deshizo verificación de pago ID: {$idPago}");

                $_SESSION['alerta'] = [
                    'tipo' => 'info',
                    'titulo' => 'Acción revertida',
                    'texto' => "El pago ID #{$idPago} ha vuelto a estado pendiente."
                ];
            }
        }

        header("Location: index.php?action=verificar_pagos&estado=pendiente");
        exit();
    }

    // =========================================================================
    // 4. REPORTE DE PAGOS POR BANCO CON FILTROS, TITULAR Y CÉDULA
    // =========================================================================
    public function pagosBanco() {
        $desde  = trim($_GET['desde'] ?? date('Y-m-d'));
        $hasta  = trim($_GET['hasta'] ?? date('Y-m-d'));
        $idMet  = !empty($_GET['metodo']) ? (int)$_GET['metodo'] : null;
        $banco  = trim($_GET['banco'] ?? '');

        $where = ["pr.estado_verificacion = 'aprobado'"];
        $params = [];

        $normalizarFecha = function($str) {
            if (empty($str)) return null;
            if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $str, $m)) {
                return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
            }
            if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $str, $m)) {
                return sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]);
            }
            $t = strtotime($str);
            return $t ? date('Y-m-d', $t) : null;
        };

        $fechaDesdeNorm = $normalizarFecha($desde);
        $fechaHastaNorm = $normalizarFecha($hasta);

        if ($fechaDesdeNorm) {
            $where[] = "pr.fecha_pago >= ?";
            $params[] = $fechaDesdeNorm . " 00:00:00";
        }
        if ($fechaHastaNorm) {
            $where[] = "pr.fecha_pago <= ?";
            $params[] = $fechaHastaNorm . " 23:59:59";
        }

        if ($idMet) {
            $where[] = "pr.id_metodo_pago = ?";
            $params[] = $idMet;
        }

        if (!empty($banco)) {
            $where[] = "pr.banco = ?";
            $params[] = $banco;
        }

        $sql = "SELECT pr.id, pr.id_venta, 
                       CONCAT(COALESCE(u.nombre, 'Cliente'), ' ', COALESCE(u.apellido, '')) AS cliente,
                       u.cedula AS cliente_cedula,
                       u.telefono AS cliente_telefono,
                       COALESCE(mp.nombre, 'General') AS metodo,
                       mp.requiere_referencia,
                       pr.moneda, pr.monto, pr.tasa_cambio, pr.referencia, 
                       COALESCE(NULLIF(TRIM(pr.banco), ''), 'Sin banco') AS banco,
                       pr.observacion,
                       pr.fecha_pago,
                       v.origen_venta,
                       COALESCE(cajero.nombre, 'Caja Mostrador') AS custodio_cajero
                FROM pagos_registro pr
                INNER JOIN ventas v ON pr.id_venta = v.id
                LEFT JOIN usuarios u ON v.id_usuario = u.id
                LEFT JOIN usuarios cajero ON v.vendedor_id = cajero.id
                LEFT JOIN metodos_pago mp ON pr.id_metodo_pago = mp.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY pr.fecha_pago DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $pagosRecibidos = $stmt->fetchAll(PDO::FETCH_OBJ);

        // Preparamos y limpiamos el titular para cada pago
        foreach ($pagosRecibidos as $p) {
            $titularLimpio = '';
            if (!empty($p->observacion)) {
                $titularLimpio = preg_replace('/^(Titular:\s*|Emisor:\s*[^|]+\|\s*Titular:\s*)/i', '', $p->observacion);
            } elseif (!empty($p->cliente_cedula)) {
                $titularLimpio = $p->cliente_cedula . ' (' . $p->cliente . ')';
            } else {
                $titularLimpio = $p->cliente;
            }
            $p->titular = $titularLimpio;
        }

        $metodos = $this->db->query("SELECT id, nombre FROM metodos_pago WHERE activo = 1 ORDER BY nombre ASC")->fetchAll(PDO::FETCH_OBJ);

        require_once 'views/ventas/pagos_banco.php';
    }

    // =========================================================================
    // 5. RETIRO DE PEDIDOS
    // =========================================================================
    public function retiroPedidos() {
        $sql = "SELECT v.id, v.fecha, CONCAT(COALESCE(u.nombre, 'Cliente'), ' ', COALESCE(u.apellido, '')) AS nombre_cliente,
                       u.telefono, COALESCE(mp.nombre, 'Mixto') AS nombre_metodo, v.total,
                       ROUND(v.total * 526.87, 2) AS total_bs, v.estado, v.fecha_entrega, v.retirado_por
                FROM ventas v
                LEFT JOIN usuarios u ON v.id_usuario = u.id
                LEFT JOIN metodos_pago mp ON v.id_metodo_pago = mp.id
                WHERE v.estado IN ('pagado', 'retirado') AND v.deleted_at IS NULL
                ORDER BY v.id DESC";
        $ventas = $this->db->query($sql)->fetchAll(PDO::FETCH_OBJ);

        require_once 'views/retiro_pedidos.php';
    }

    public function procesarRetiro($id = null) {
        $idVenta = (int)($_GET['id'] ?? $id ?? 0);
        $retira = $_GET['retira'] ?? 'cliente';
        $retiradoPor = trim($_GET['retirado_por'] ?? '');
        $retiradoCedula = trim($_GET['retirado_cedula'] ?? '');

        if ($idVenta > 0) {
            $stmt = $this->db->prepare("
                UPDATE ventas 
                SET estado = 'retirado', fecha_entrega = NOW(), retirado_por = ?, retirado_cedula = ?
                WHERE id = ?
            ");
            $nombreEntrega = ($retira === 'cliente') ? 'Titular de la cuenta' : $retiradoPor;
            $stmt->execute([$nombreEntrega, $retiradoCedula, $idVenta]);

            $this->log('Ventas', 'UPDATE', "Pedido entregado #{$idVenta} a: {$nombreEntrega}");
        }

        header("Location: index.php?action=retiro_pedidos");
        exit();
    }

    // =========================================================================
    // 6. DETALLE DE VENTA
    // =========================================================================
    public function verDetalle($id = null) {
        $idVenta = (int)($_GET['id'] ?? $id ?? 0);

        $sqlV = "SELECT v.*, 
                        COALESCE(u.nombre, 'Consumidor Final') AS nombre,
                        COALESCE(u.apellido, '') AS apellido,
                        CONCAT(COALESCE(u.nombre, 'Cliente'), ' ', COALESCE(u.apellido, '')) AS cliente,
                        u.telefono, u.correo, 
                        COALESCE(adm.nombre, 'Sistema') AS vendedor
                 FROM ventas v
                 LEFT JOIN usuarios u ON v.id_usuario = u.id
                 LEFT JOIN usuarios adm ON v.vendedor_id = adm.id
                 WHERE v.id = ?";
        $stmtV = $this->db->prepare($sqlV);
        $stmtV->execute([$idVenta]);
        $venta = $stmtV->fetch(PDO::FETCH_OBJ);

        $sqlD = "SELECT dv.*, 
                        p.nombre_producto, 
                        p.nombre_producto AS producto_nombre,
                        p.codigo, 
                        p.imagen 
                 FROM detalle_ventas dv
                 INNER JOIN productos p ON dv.id_producto = p.id
                 WHERE dv.id_venta = ?";
        $stmtD = $this->db->prepare($sqlD);
        $stmtD->execute([$idVenta]);
        $detalles = $stmtD->fetchAll(PDO::FETCH_OBJ);

        $sqlP = "SELECT pr.*, 
                        mp.nombre AS metodo, 
                        COALESCE(u.nombre, '') AS verificado_por_nombre
                 FROM pagos_registro pr
                 LEFT JOIN metodos_pago mp ON pr.id_metodo_pago = mp.id
                 LEFT JOIN usuarios u ON pr.verificado_por = u.id
                 WHERE pr.id_venta = ?";
        $stmtP = $this->db->prepare($sqlP);
        $stmtP->execute([$idVenta]);
        $pagos = $stmtP->fetchAll(PDO::FETCH_OBJ);

        require_once 'views/detalle_venta.php';
    }

    // =========================================================================
    // MÉTODOS AUXILIARES
    // =========================================================================
    public function pagosPendientes() {
        header("Location: index.php?action=verificar_pagos&estado=pendiente");
        exit();
    }
    public function confirmarPago($id = null) {
        $_POST['id'] = $id;
        $this->aprobarPago();
    }
    public function mostrarCarrito() { require_once 'views/Carrito.php'; }
    public function añadir() {}
    public function eliminarItem() {}
    public function finalizarCompra() {}
    public function manejarReporte($idMetodo) {
        $desde = $_GET['desde'] ?? '';
        $hasta = $_GET['hasta'] ?? '';
        require_once 'views/ventas/cobrado_metodo.php';
    }
}