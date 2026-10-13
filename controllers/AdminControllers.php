<?php
class AdminControllers {
    private $productoModelo;
    private $db;

    public function __construct() {
        require_once 'config/db.php';
        $this->db = Database::connect();

        require_once 'models/ProductoModel.php';
        $this->productoModelo = new ProductoModel();
    }

    // Helper interno para auditoría
    private function log($modulo, $accion, $descripcion) {
        try {
            $idUsuario = $_SESSION['id_usuario'] ?? null;
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $stmt = $this->db->prepare("INSERT INTO auditoria_logs (id_usuario, modulo, accion, descripcion, ip) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$idUsuario, $modulo, $accion, $descripcion, $ip]);
        } catch (Exception $e) {}
    }

    // Helper de seguridad para validación y procesamiento de imágenes
    private function procesarImagenUpload($file, $imagenAnterior = null) {
        if (empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return $imagenAnterior;
        }

        if ($file['size'] > 3 * 1024 * 1024) {
            throw new Exception("La imagen supera el límite permitido de 3 MB.");
        }

        $mime = '';
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
        } elseif (function_exists('getimagesize')) {
            $info = @getimagesize($file['tmp_name']);
            $mime = $info['mime'] ?? '';
        }

        $mimesPermitidos = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp'
        ];

        if (!array_key_exists($mime, $mimesPermitidos)) {
            throw new Exception("El archivo debe ser una imagen válida (JPG, PNG o WEBP).");
        }

        $ext = $mimesPermitidos[$mime];
        $nombreLimpio = 'prod_' . bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;
        $carpeta = 'public/uploads/';

        if (!is_dir($carpeta)) {
            mkdir($carpeta, 0755, true);
        }

        $destino = $carpeta . $nombreLimpio;
        if (!move_uploaded_file($file['tmp_name'], $destino)) {
            throw new Exception("Error al mover la imagen al servidor.");
        }

        if (!empty($imagenAnterior) && file_exists($carpeta . $imagenAnterior)) {
            @unlink($carpeta . $imagenAnterior);
        }

        return $nombreLimpio;
    }

    // -------------------------------------------------------------------------
    // VISTAS PRINCIPALES Y CATÁLOGO
    // -------------------------------------------------------------------------

    public function mostrarDashboard() {
        date_default_timezone_set('America/Caracas');
        $hoy  = date('Y-m-d');
        $ayer = date('Y-m-d', strtotime('-1 day'));

        $tasa = 526.87;
        try {
            $stmtTasa = $this->db->query("SELECT tasa_bcv FROM configuracion ORDER BY id DESC LIMIT 1");
            $resTasa = $stmtTasa->fetch(PDO::FETCH_OBJ);
            if ($resTasa && (float)$resTasa->tasa_bcv > 0) {
                $tasa = (float)$resTasa->tasa_bcv;
            }
        } catch (Exception $e) {}

        $ventasHoyMonto = 0.0;
        $ventasHoyCantidad = 0;
        $ventasAyerMonto = 0.0;

        try {
            $sqlHoy = "SELECT COALESCE(SUM(total), 0) AS monto, COUNT(id) AS cantidad 
                       FROM ventas 
                       WHERE (DATE(fecha) = ? OR DATE(fecha) = CURDATE())
                         AND (estado IN ('retirado', 'pagado', 'completada', 'aprobada') OR estado IS NULL)";
            $stmtHoy = $this->db->prepare($sqlHoy);
            $stmtHoy->execute([$hoy]);
            $resHoy = $stmtHoy->fetch(PDO::FETCH_ASSOC);
            if ($resHoy) {
                $ventasHoyMonto = (float)$resHoy['monto'];
                $ventasHoyCantidad = (int)$resHoy['cantidad'];
            }

            $sqlAyer = "SELECT COALESCE(SUM(total), 0) AS monto 
                        FROM ventas 
                        WHERE DATE(fecha) = ? 
                          AND (estado IN ('retirado', 'pagado', 'completada', 'aprobada') OR estado IS NULL)";
            $stmtAyer = $this->db->prepare($sqlAyer);
            $stmtAyer->execute([$ayer]);
            $ventasAyerMonto = (float)($stmtAyer->fetchColumn() ?: 0.0);
        } catch (Exception $e) {}

        $pagosPendientes = [];
        $pagosPendientesMonto = 0.0;
        try {
            $sqlPagosPend = "SELECT pr.*, 
                                    COALESCE(mp.nombre, 'Pago Móvil') AS metodo,
                                    COALESCE(u.nombre, 'Cliente Mostrador') AS cliente
                             FROM pagos_registro pr
                             LEFT JOIN metodos_pago mp ON pr.id_metodo_pago = mp.id
                             LEFT JOIN ventas v ON pr.id_venta = v.id
                             LEFT JOIN usuarios u ON v.id_usuario = u.id
                             WHERE pr.estado_verificacion = 'pendiente'
                             ORDER BY pr.fecha_pago DESC";
            $stmtPP = $this->db->query($sqlPagosPend);
            $todosPagosPend = $stmtPP->fetchAll(PDO::FETCH_OBJ);
            foreach ($todosPagosPend as $p) {
                $pagosPendientesMonto += (float)($p->monto ?? 0);
            }
            $pagosPendientes = array_slice($todosPagosPend, 0, 5);
        } catch (Exception $e) {}

        $porRetirar = [];
        $totalPorRetirar = 0;
        try {
            $sqlRetirar = "SELECT v.*, 
                                  COALESCE(u.nombre, 'Cliente') AS nombre_cliente
                           FROM ventas v
                           LEFT JOIN usuarios u ON v.id_usuario = u.id
                           WHERE v.estado IN ('pagado', 'por_retirar', 'pendiente_retiro')
                           ORDER BY v.fecha DESC";
            $stmtRet = $this->db->query($sqlRetirar);
            $todosRetirar = $stmtRet->fetchAll(PDO::FETCH_OBJ);
            $totalPorRetirar = count($todosRetirar);
            $porRetirar = array_slice($todosRetirar, 0, 5);
        } catch (Exception $e) {}

        $stockBajo = [];
        $totalStockBajo = 0;
        $totalAgotados = 0;
        try {
            $sqlStock = "SELECT p.*, COALESCE(c.nombre_categoria, 'General') AS categoria
                         FROM productos p
                         LEFT JOIN categorias c ON p.id_categoria = c.id
                         WHERE p.stock <= 5
                         ORDER BY p.stock ASC";
            $stmtStk = $this->db->query($sqlStock);
            $todosStock = $stmtStk->fetchAll(PDO::FETCH_OBJ);
            $totalStockBajo = count($todosStock);
            foreach ($todosStock as $prod) {
                if ((int)$prod->stock <= 0) $totalAgotados++;
            }
            $stockBajo = array_slice($todosStock, 0, 5);
        } catch (Exception $e) {}

        $kpis = [
            'ventas_hoy'             => $ventasHoyMonto,
            'ventas_hoy_n'           => $ventasHoyCantidad,
            'ventas_ayer'            => $ventasAyerMonto,
            'pagos_pendientes'       => count($pagosPendientes),
            'pagos_pendientes_monto' => $pagosPendientesMonto,
            'por_retirar'            => $totalPorRetirar,
            'stock_bajo'             => $totalStockBajo,
            'agotados'               => $totalAgotados
        ];
        $k = $kpis;

        $ventasSemana = [];
        try {
            for ($i = 6; $i >= 0; $i--) {
                $diaF = date('Y-m-d', strtotime("-{$i} days"));
                $ventasSemana[$diaF] = [
                    'fecha'  => $diaF,
                    'tienda' => 0.0,
                    'online' => 0.0
                ];
            }

            $hace7Dias = date('Y-m-d', strtotime('-6 days'));
            $sqlSemana = "SELECT 
                            DATE(fecha) AS dia,
                            COALESCE(SUM(CASE WHEN origen_venta IN ('tienda', 'tienda_fisica') THEN total ELSE 0 END), 0) AS tienda,
                            COALESCE(SUM(CASE WHEN origen_venta NOT IN ('tienda', 'tienda_fisica') THEN total ELSE 0 END), 0) AS online
                          FROM ventas
                          WHERE DATE(fecha) >= ? AND DATE(fecha) <= ?
                            AND (estado IN ('retirado', 'pagado', 'completada', 'aprobada') OR estado IS NULL)
                          GROUP BY DATE(fecha)
                          ORDER BY dia ASC";
            $stmtSem = $this->db->prepare($sqlSemana);
            $stmtSem->execute([$hace7Dias, $hoy]);
            $datosSemana = $stmtSem->fetchAll(PDO::FETCH_ASSOC);

            foreach ($datosSemana as $fila) {
                if (isset($ventasSemana[$fila['dia']])) {
                    $ventasSemana[$fila['dia']]['tienda'] = (float)$fila['tienda'];
                    $ventasSemana[$fila['dia']]['online'] = (float)$fila['online'];
                }
            }
            $ventasSemana = array_values($ventasSemana);
        } catch (Exception $e) {
            $ventasSemana = [];
        }

        include "views/dashboard.php";
    }

    public function mostrarPanel() {
        $categorias = $this->obtenerCategoriasConAtributos();
        $p = null;
        include 'views/formulario_producto.php';
    }

    public function mostrarListado() {
        $listaProductos = $this->productoModelo->obtenerTodos();
        $stmtCat = $this->db->query("SELECT id, nombre_categoria FROM categorias ORDER BY nombre_categoria ASC");
        $categorias = $stmtCat->fetchAll(PDO::FETCH_OBJ);
        
        $aviso = $_SESSION['aviso'] ?? null;
        unset($_SESSION['aviso']);

        include 'views/listado_productos.php';
    }

    public function catalogo() {
        $nombre = $_GET['busqueda'] ?? '';
        $id_cat = $_GET['id_categoria'] ?? '';

        $categorias = $this->productoModelo->obtenerCategorias();
        $listaProductos = $this->productoModelo->buscarProductos($nombre, $id_cat);

        require_once 'views/catalogo.php';
    }

    // -------------------------------------------------------------------------
    // PRODUCTOS: CREAR, EDITAR Y BORRAR
    // -------------------------------------------------------------------------

    private function obtenerCategoriasConAtributos() {
        $stmtCat = $this->db->query("SELECT id, nombre_categoria, id_padre FROM categorias ORDER BY nombre_categoria ASC");
        $cats = $stmtCat->fetchAll(PDO::FETCH_OBJ);

        $stmtAtr = $this->db->prepare("SELECT id, nombre FROM categoria_atributos WHERE id_categoria = ?");
        foreach ($cats as $cat) {
            $stmtAtr->execute([$cat->id]);
            $cat->atributos = $stmtAtr->fetchAll(PDO::FETCH_OBJ);
        }
        return $cats;
    }

    public function agregar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $db = $this->productoModelo->getDb();
            try {
                $nombre = trim($_POST['nombre_producto'] ?? $_POST['nombre'] ?? '');
                $codigo = trim($_POST['codigo'] ?? '');
                $desc   = trim($_POST['descripcion'] ?? $_POST['desc'] ?? '');
                $precio = (float)($_POST['precio'] ?? 0);
                $costo  = (float)($_POST['costo_compra'] ?? 0);
                $stock  = (int)($_POST['stock'] ?? 0);
                $stockOnline = (int)($_POST['stock_online'] ?? 0);
                $stockMinimo = (int)($_POST['stock_minimo'] ?? 5);
                $catId  = !empty($_POST['id_categoria']) ? (int)$_POST['id_categoria'] : (!empty($_POST['categoria']) ? (int)$_POST['categoria'] : null);
                $estado = isset($_POST['estado']) ? (int)$_POST['estado'] : 1;
                $atributos = $_POST['atributo'] ?? [];
                $variantes = $_POST['variantes'] ?? [];

                if (empty($nombre)) {
                    throw new Exception("El nombre del producto es obligatorio.");
                }

                if (!empty($codigo) && $this->productoModelo->existeCodigo($codigo)) {
                    throw new Exception("El código '{$codigo}' ya está registrado en otro producto.");
                }

                $nombreImagen = null;
                if (!empty($_FILES['imagen']['tmp_name'])) {
                    $nombreImagen = $this->procesarImagenUpload($_FILES['imagen']);
                }

                $datos = [
                    'codigo'          => $codigo,
                    'nombre_producto' => $nombre,
                    'descripcion'     => $desc,
                    'precio'          => $precio,
                    'costo_compra'    => $costo,
                    'stock'           => $stock,
                    'stock_online'    => $stockOnline,
                    'stock_minimo'    => $stockMinimo,
                    'id_categoria'    => $catId,
                    'estado'          => $estado,
                    'imagen'          => $nombreImagen
                ];

                $db->beginTransaction();

                $nuevoId = $this->productoModelo->guardarCompleto($datos, $atributos, $variantes);
                $this->log('Inventario', 'INSERT', "Creó producto: {$nombre} (ID: {$nuevoId})");

                $db->commit();

                $_SESSION['aviso'] = 'Producto creado exitosamente.';
                header("Location: index.php?action=listado_productos");
                exit();

            } catch (Exception $e) {
                if ($db && $db->inTransaction()) {
                    $db->rollBack();
                }
                $_SESSION['aviso_error'] = $e->getMessage();
                header("Location: index.php?action=formulario_producto");
                exit();
            }
        }
    }

    public function editar() {
        $id = (int)($_GET['id'] ?? 0);
        $p = $this->productoModelo->obtenerPorId($id);

        if (!$p) {
            $_SESSION['aviso'] = 'El producto solicitado no existe.';
            header("Location: index.php?action=listado_productos");
            exit();
        }

        $p->atributos = $this->productoModelo->obtenerAtributosProducto($id);
        $p->variantes = $this->productoModelo->obtenerVariantesProducto($id);
        $categorias = $this->obtenerCategoriasConAtributos();
        
        $aviso = $_SESSION['aviso'] ?? null;
        unset($_SESSION['aviso']);

        require_once 'views/editar_producto.php';
    }

   public function actualizar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $db = $this->productoModelo->getDb();
            $id = (int)($_POST['id'] ?? 0);
            try {
                $pActual = $this->productoModelo->obtenerPorId($id);

                if (!$pActual) {
                    throw new Exception("El producto a actualizar no existe.");
                }

                $codigo = trim($_POST['codigo'] ?? $pActual->codigo);

                if (!empty($codigo) && $this->productoModelo->existeCodigo($codigo, $id)) {
                    throw new Exception("El código '{$codigo}' ya está asignado a otro producto.");
                }

                $nombreImagen = $pActual->imagen;
                if (!empty($_FILES['imagen']['tmp_name'])) {
                    $nombreImagen = $this->procesarImagenUpload($_FILES['imagen'], $pActual->imagen);
                }

                $nombre = trim($_POST['nombre_producto'] ?? $_POST['nombre'] ?? $pActual->nombre_producto);
                $desc   = trim($_POST['descripcion'] ?? $_POST['desc'] ?? $pActual->descripcion);
                $precio = isset($_POST['precio']) && $_POST['precio'] !== '' ? (float)$_POST['precio'] : (float)$pActual->precio;
                $costo  = isset($_POST['costo_compra']) && $_POST['costo_compra'] !== '' ? (float)$_POST['costo_compra'] : (float)$pActual->costo_compra;
                
                $stockMinimo = isset($_POST['stock_minimo']) && $_POST['stock_minimo'] !== '' 
                                ? (int)$_POST['stock_minimo'] 
                                : (int)$pActual->stock_minimo;

                $catId  = !empty($_POST['id_categoria']) ? (int)$_POST['id_categoria'] : (!empty($_POST['categoria']) ? (int)$_POST['categoria'] : $pActual->id_categoria);
                $estado = isset($_POST['estado']) ? (int)$_POST['estado'] : 0;
                $atributos = $_POST['atributo'] ?? [];
                $variantes = $_POST['variantes'] ?? [];

                $datos = [
                    'codigo'          => $codigo,
                    'nombre_producto' => $nombre,
                    'descripcion'     => $desc,
                    'precio'          => $precio,
                    'costo_compra'    => $costo,
                    'stock_minimo'    => $stockMinimo,
                    'id_categoria'    => $catId,
                    'estado'          => $estado,
                    'imagen'          => $nombreImagen
                ];

                $db->beginTransaction();

                $this->productoModelo->actualizarCompleto($id, $datos, $atributos, $variantes);
                if (method_exists($this, 'log')) {
                    $this->log('Inventario', 'UPDATE', "Actualizó producto y variantes: {$nombre} (ID: {$id})");
                }

                $db->commit();

                $_SESSION['aviso'] = 'Producto actualizado correctamente.';
                header("Location: index.php?action=listado_productos");
                exit();

            } catch (Exception $e) {
                if ($db && $db->inTransaction()) {
                    $db->rollBack();
                }
                $_SESSION['aviso_error'] = $e->getMessage();
                header("Location: index.php?action=editar&id=" . $id);
                exit();
            }
        }
    }


    public function eliminar() {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $this->productoModelo->borrarProducto($id);
            $this->log('Inventario', 'DELETE', "Eliminó producto (borrado lógico) ID: {$id}");
            $_SESSION['aviso'] = 'Producto eliminado correctamente.';
        }
        header("Location: index.php?action=listado_productos");
        exit();
    }

    // -------------------------------------------------------------------------
    // CATEGORÍAS Y ATRIBUTOS
    // -------------------------------------------------------------------------

    public function categorias() {
        $sql = "SELECT c.id, c.id_padre, c.nombre_categoria,
                       p.nombre_categoria AS nombre_padre,
                       COUNT(pr.id) AS total_productos,
                       COALESCE(SUM(pr.stock), 0) AS total_unidades
                FROM categorias c
                LEFT JOIN categorias p ON c.id_padre = p.id
                LEFT JOIN productos pr ON pr.id_categoria = c.id AND pr.deleted_at IS NULL
                GROUP BY c.id, c.id_padre, c.nombre_categoria, p.nombre_categoria
                ORDER BY COALESCE(c.id_padre, c.id), c.id_padre IS NOT NULL, c.nombre_categoria ASC";

        $stmt = $this->db->query($sql);
        $categorias = $stmt->fetchAll(PDO::FETCH_OBJ);

        $stmtAtr = $this->db->prepare("SELECT id, nombre FROM categoria_atributos WHERE id_categoria = ?");
        foreach ($categorias as $cat) {
            $stmtAtr->execute([$cat->id]);
            $cat->atributos = $stmtAtr->fetchAll(PDO::FETCH_OBJ);
        }

        if (file_exists('views/categorias.php')) {
            require_once 'views/categorias.php';
        } elseif (file_exists('views/inventario/categorias.php')) {
            require_once 'views/inventario/categorias.php';
        }
    }

    public function guardarCategoria() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
            $nombre = trim($_POST['nombre_categoria'] ?? '');
            $rawPadre = $_POST['id_padre'] ?? '';
            $nuevaPadre = trim($_POST['nueva_categoria_padre'] ?? '');
            $idPadre = null;

            if (empty($nombre)) {
                $_SESSION['aviso_error'] = "El nombre de la categoría es obligatorio.";
                header("Location: index.php?action=categorias");
                exit();
            }

            try {
                $this->db->beginTransaction();

                if ($rawPadre === 'nueva_padre' && !empty($nuevaPadre)) {
                    $stmtNP = $this->db->prepare("INSERT INTO categorias (nombre_categoria, id_padre) VALUES (?, NULL)");
                    $stmtNP->execute([$nuevaPadre]);
                    $idPadre = (int)$this->db->lastInsertId();
                    $this->log('Inventario', 'INSERT', "Creó categoría principal padre: {$nuevaPadre}");
                } elseif (!empty($rawPadre) && is_numeric($rawPadre)) {
                    $idPadre = (int)$rawPadre;
                }

                if ($id) {
                    if ($idPadre === $id) $idPadre = null;
                    $stmt = $this->db->prepare("UPDATE categorias SET nombre_categoria = ?, id_padre = ? WHERE id = ?");
                    $stmt->execute([$nombre, $idPadre, $id]);
                    $this->log('Inventario', 'UPDATE', "Editó categoría: {$nombre}");
                } else {
                    $stmt = $this->db->prepare("INSERT INTO categorias (nombre_categoria, id_padre) VALUES (?, ?)");
                    $stmt->execute([$nombre, $idPadre]);
                    $id = (int)$this->db->lastInsertId();
                    $this->log('Inventario', 'INSERT', "Creó categoría: {$nombre}");
                }

                $atrIds = $_POST['atributo_id'] ?? [];
                $atrNombres = $_POST['atributo_nombre'] ?? [];
                $atrOpciones = $_POST['atributo_opciones'] ?? [];

                $atributosProcesados = [];

                for ($i = 0; $i < count($atrNombres); $i++) {
                    $nomAtr = trim($atrNombres[$i]);
                    $claveUnica = mb_strtolower($nomAtr, 'UTF-8');

                    if ($nomAtr === '' || isset($atributosProcesados[$claveUnica])) {
                        continue;
                    }

                    $opcRaw = explode(',', $atrOpciones[$i] ?? '');
                    $opcLimpias = [];
                    foreach ($opcRaw as $opc) {
                        $opcT = trim($opc);
                        if ($opcT !== '' && !in_array($opcT, $opcLimpias, true)) {
                            $opcLimpias[] = $opcT;
                        }
                    }
                    $opcAtr = implode(', ', $opcLimpias);
                    $atrId = !empty($atrIds[$i]) ? (int)$atrIds[$i] : 0;

                    $atributosProcesados[$claveUnica] = [
                        'id' => $atrId,
                        'nombre' => $nomAtr,
                        'opciones' => $opcAtr
                    ];
                }

                $idsMantener = [];
                foreach ($atributosProcesados as $item) {
                    if ($item['id'] > 0) {
                        $idsMantener[] = $item['id'];
                    }
                }

                if (!empty($idsMantener)) {
                    $placeholders = implode(',', array_fill(0, count($idsMantener), '?'));
                    $stmtDel = $this->db->prepare("DELETE FROM categoria_atributos WHERE id_categoria = ? AND id NOT IN ($placeholders)");
                    $stmtDel->execute(array_merge([$id], $idsMantener));
                } else {
                    $this->db->prepare("DELETE FROM categoria_atributos WHERE id_categoria = ?")->execute([$id]);
                }

                $stmtUpd = $this->db->prepare("UPDATE categoria_atributos SET nombre = ? WHERE id = ? AND id_categoria = ?");
                $stmtIns = $this->db->prepare("INSERT INTO categoria_atributos (id_categoria, nombre) VALUES (?, ?)");

                foreach ($atributosProcesados as $item) {
                    if ($item['id'] > 0) {
                        $stmtUpd->execute([$item['nombre'], $item['id'], $id]);
                    } else {
                        $stmtIns->execute([$id, $item['nombre']]);
                    }
                }

                $this->db->commit();
                $_SESSION['aviso'] = "Categoría guardada correctamente.";

            } catch (Exception $e) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                $_SESSION['aviso_error'] = "Error al guardar la categoría: " . $e->getMessage();
            }

            header("Location: index.php?action=categorias");
            exit();
        }
    }

    public function eliminarCategoria() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            $stmtCheck = $this->db->prepare("SELECT COUNT(*) FROM productos WHERE id_categoria = ? AND deleted_at IS NULL");
            $stmtCheck->execute([$id]);

            if ((int)$stmtCheck->fetchColumn() === 0) {
                $this->db->prepare("DELETE FROM categorias WHERE id = ?")->execute([$id]);
                $this->log('Inventario', 'DELETE', "Eliminó categoría ID: {$id}");
            }
            header("Location: index.php?action=categorias");
            exit();
        }
    }

// -------------------------------------------------------------------------
    // AJUSTES DE STOCK Y KARDEX
    // -------------------------------------------------------------------------

    public function ajustesInventario() {
        $stmtP = $this->db->query("SELECT id, codigo, nombre_producto, stock, stock_minimo 
                                   FROM productos WHERE deleted_at IS NULL ORDER BY nombre_producto ASC");
        $productos = $stmtP->fetchAll(PDO::FETCH_OBJ);

        $stmtAjustes = $this->db->query("
            SELECT p.nombre_producto, k.tipo_movimiento, k.observacion, k.fecha,
                   COALESCE(u.nombre, 'Admin') AS usuario, k.cantidad
            FROM kardex_inventario k
            INNER JOIN productos p ON k.id_producto = p.id
            LEFT JOIN usuarios u ON k.id_usuario = u.id
            WHERE k.tipo_movimiento IN ('ajuste', 'merma', 'transferencia')
            ORDER BY k.fecha DESC LIMIT 5
        ");
        $ultimosAjustes = $stmtAjustes->fetchAll(PDO::FETCH_OBJ);

        require_once 'views/inventario/ajustes.php';
    }

    public function guardarAjuste() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?action=ajustes_inventario");
            exit();
        }

        $idProducto = (int)($_POST['id_producto'] ?? 0);
        $tipo       = $_POST['tipo_movimiento'] ?? '';
        $cantidad   = (int)($_POST['cantidad'] ?? 0);
        $observacion= trim($_POST['observacion'] ?? '');
        $idUsuario  = $_SESSION['id_usuario'] ?? 1;

        if ($idProducto <= 0 || $cantidad <= 0) {
            header("Location: index.php?action=ajustes_inventario");
            exit();
        }

        try {
            $this->db->beginTransaction();

            $stmtP = $this->db->prepare("SELECT stock FROM productos WHERE id = ?");
            $stmtP->execute([$idProducto]);
            $stockActual = (int)$stmtP->fetchColumn();

            // Calcular nuevo stock según el tipo de movimiento
            if ($tipo === 'merma') {
                $nuevoStock = max(0, $stockActual - $cantidad);
                $cantKardex = -$cantidad;
            } else { // ajuste positivo o ajuste general
                $nuevoStock = $stockActual + $cantidad;
                $cantKardex = $cantidad;
            }

            // Actualizar stock único del producto
            $stmtUpd = $this->db->prepare("UPDATE productos SET stock = ? WHERE id = ?");
            $stmtUpd->execute([$nuevoStock, $idProducto]);

            // Registrar en kardex con la columna unificada stock_resultante
            $stmtK = $this->db->prepare("
                INSERT INTO kardex_inventario 
                (id_producto, tipo_movimiento, canal, cantidad, stock_resultante, observacion, id_usuario)
                VALUES (?, ?, 'general', ?, ?, ?, ?)
            ");
            $stmtK->execute([$idProducto, $tipo, $cantKardex, $nuevoStock, $observacion, $idUsuario]);

            $this->db->commit();
            header("Location: index.php?action=ajustes_inventario&msg=ok");
            exit();

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            die("Error al guardar ajuste: " . $e->getMessage());
        }
    }

    public function kardex() {
        $tipo  = $_GET['tipo'] ?? '';
        $desde = $_GET['desde'] ?? '';
        $hasta = $_GET['hasta'] ?? '';
        $q     = trim($_GET['q'] ?? '');

        $where = ["1=1"];
        $params = [];
        if (!empty($tipo))  { $where[] = "k.tipo_movimiento = ?"; $params[] = $tipo; }
        if (!empty($desde)) { $where[] = "DATE(k.fecha) >= ?";    $params[] = $desde; }
        if (!empty($hasta)) { $where[] = "DATE(k.fecha) <= ?";    $params[] = $hasta; }
        if (!empty($q)) {
            $where[] = "(p.nombre_producto LIKE ? OR p.codigo LIKE ?)";
            $params[] = "%$q%"; 
            $params[] = "%$q%";
        }

        $sql = "SELECT k.fecha, p.nombre_producto, p.imagen, p.stock_minimo,
                       k.tipo_movimiento, k.canal, k.cantidad, 
                       k.stock_resultante,
                       k.id_referencia, k.observacion, COALESCE(u.nombre, 'Sistema') AS usuario
                FROM kardex_inventario k
                INNER JOIN productos p ON k.id_producto = p.id
                LEFT JOIN usuarios u ON k.id_usuario = u.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY k.fecha DESC";
                
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $movimientos = $stmt->fetchAll(PDO::FETCH_OBJ);

        require_once 'views/inventario/kardex.php';
    }
    // -------------------------------------------------------------------------
    // GRÁFICOS ANTIGUOS
    // -------------------------------------------------------------------------

    public function grafico_barras() {
        $datosEstadisticas = $this->productoModelo->obtenerEstadisticasGenerales();
        require_once 'views/grafico_barras.php';
    }

    public function grafico_torta() {
        $datosEstadisticas = $this->productoModelo->obtenerEstadisticasGenerales();
        require_once 'views/grafico_tortas.php';
    }
public function mostrarEstadisticas() {
        date_default_timezone_set('America/Caracas');
        $hoy = date('Y-m-d');

        // Leer fechas directas enviadas por el formulario de la vista
        $desde = !empty($_GET['desde']) ? date('Y-m-d', strtotime($_GET['desde'])) : date('Y-m-d', strtotime('-13 days'));
        $hasta = !empty($_GET['hasta']) ? date('Y-m-d', strtotime($_GET['hasta'])) : $hoy;

        if ($desde > $hasta) {
            $tmp = $desde; $desde = $hasta; $hasta = $tmp;
        }

        $agrupar = ($_GET['agrupar'] ?? 'dia') === 'mes' ? 'mes' : 'dia';
        $tab     = $_GET['tab'] ?? 'ventas';

        // 1. Resumen General (KPIs) usando DATE() para coincidencia exacta con el día
        $sqlResumen = "
            SELECT 
                COALESCE(SUM(v.total), 0) AS vendido,
                COUNT(v.id) AS ventas,
                COALESCE(SUM(CASE WHEN v.origen_venta = 'tienda_fisica' THEN v.total ELSE 0 END), 0) AS tienda,
                COALESCE(SUM(
                    (SELECT COALESCE(SUM((dv.precio_unitario - dv.costo_unitario) * dv.cantidad), 0)
                     FROM detalle_ventas dv 
                     WHERE dv.id_venta = v.id)
                ), 0) AS ganancia
            FROM ventas v
            WHERE v.estado != 'cancelado' 
              AND DATE(v.fecha) BETWEEN ? AND ?
        ";
        $stmtR = $this->db->prepare($sqlResumen);
        $stmtR->execute([$desde, $hasta]);
        $resumenRow = $stmtR->fetch(PDO::FETCH_ASSOC) ?: [];

        $resumen = [
            'vendido'  => (float)($resumenRow['vendido'] ?? 0),
            'ganancia' => (float)($resumenRow['ganancia'] ?? 0),
            'ventas'   => (int)($resumenRow['ventas'] ?? 0),
            'tienda'   => (float)($resumenRow['tienda'] ?? 0)
        ];

        // 2. Calendario para el gráfico
        $calendario = [];
        $inicioTs = strtotime($desde);
        $finTs    = strtotime($hasta);

        if ($agrupar === 'mes') {
            $actual = strtotime(date('Y-m-01', $inicioTs));
            $finMes = strtotime(date('Y-m-01', $finTs));
            while ($actual <= $finMes) {
                $clave = date('Y-m-01', $actual);
                $calendario[$clave] = ['fecha' => $clave, 'tienda' => 0.0, 'online' => 0.0];
                $actual = strtotime('+1 month', $actual);
            }
        } else {
            $actual = $inicioTs;
            while ($actual <= $finTs) {
                $clave = date('Y-m-d', $actual);
                $calendario[$clave] = ['fecha' => $clave, 'tienda' => 0.0, 'online' => 0.0];
                $actual = strtotime('+1 day', $actual);
            }
        }

        $formato = ($agrupar === 'mes') ? '%Y-%m-01' : '%Y-%m-%d';
        $sqlDias = "
            SELECT 
                DATE_FORMAT(v.fecha, '{$formato}') AS fecha,
                COALESCE(SUM(CASE WHEN v.origen_venta = 'tienda_fisica' THEN v.total ELSE 0 END), 0) AS tienda,
                COALESCE(SUM(CASE WHEN v.origen_venta = 'online' THEN v.total ELSE 0 END), 0) AS online
            FROM ventas v
            WHERE v.estado != 'cancelado' 
              AND DATE(v.fecha) BETWEEN ? AND ?
            GROUP BY DATE_FORMAT(v.fecha, '{$formato}')
        ";
        $stmtD = $this->db->prepare($sqlDias);
        $stmtD->execute([$desde, $hasta]);
        $ventasAgrupadas = $stmtD->fetchAll(PDO::FETCH_ASSOC);

        foreach ($ventasAgrupadas as $va) {
            if (isset($calendario[$va['fecha']])) {
                $calendario[$va['fecha']]['tienda'] = (float)$va['tienda'];
                $calendario[$va['fecha']]['online'] = (float)$va['online'];
            }
        }
        $dias = array_values($calendario);

        // 3. Categorías
        $sqlCat = "
            SELECT 
                c.nombre_categoria AS nombre,
                COALESCE(SUM(dv.subtotal), 0) AS vendido,
                COALESCE(SUM((dv.precio_unitario - dv.costo_unitario) * dv.cantidad), 0) AS ganancia
            FROM detalle_ventas dv
            INNER JOIN ventas v ON dv.id_venta = v.id
            INNER JOIN productos p ON dv.id_producto = p.id
            INNER JOIN categorias c ON p.id_categoria = c.id
            WHERE v.estado != 'cancelado' 
              AND DATE(v.fecha) BETWEEN ? AND ?
            GROUP BY c.id, c.nombre_categoria
            ORDER BY ganancia DESC
        ";
        $stmtC = $this->db->prepare($sqlCat);
        $stmtC->execute([$desde, $hasta]);
        $categorias = $stmtC->fetchAll(PDO::FETCH_OBJ);

        // 4. Tab Productos: Top
        $sqlTop = "
            SELECT 
                p.nombre_producto AS nombre,
                COALESCE(c.nombre_categoria, 'Sin categoría') AS categoria,
                SUM(dv.cantidad) AS unidades,
                SUM(dv.subtotal) AS monto,
                SUM((dv.precio_unitario - dv.costo_unitario) * dv.cantidad) AS ganancia
            FROM detalle_ventas dv
            INNER JOIN ventas v ON dv.id_venta = v.id
            INNER JOIN productos p ON dv.id_producto = p.id
            LEFT JOIN categorias c ON p.id_categoria = c.id
            WHERE v.estado != 'cancelado' 
              AND DATE(v.fecha) BETWEEN ? AND ?
            GROUP BY p.id, p.nombre_producto, c.nombre_categoria
            ORDER BY monto DESC
            LIMIT 10
        ";
        $stmtTop = $this->db->prepare($sqlTop);
        $stmtTop->execute([$desde, $hasta]);
        $top = $stmtTop->fetchAll(PDO::FETCH_OBJ);

        // 5. Tab Productos: Sin movimiento
        $sqlSinMov = "
            SELECT 
                p.nombre_producto AS nombre,
                COALESCE(c.nombre_categoria, 'General') AS categoria,
                p.stock,
                DATEDIFF(NOW(), MAX(v.fecha)) AS dias
            FROM productos p
            LEFT JOIN categorias c ON p.id_categoria = c.id
            LEFT JOIN detalle_ventas dv ON dv.id_producto = p.id
            LEFT JOIN ventas v ON dv.id_venta = v.id AND v.estado != 'cancelado'
            WHERE p.estado = 1 AND p.deleted_at IS NULL
            GROUP BY p.id, p.nombre_producto, c.nombre_categoria, p.stock
            HAVING dias >= 30 OR dias IS NULL
            ORDER BY dias DESC, p.stock DESC
            LIMIT 10
        ";
        $sin_movimiento = $this->db->query($sqlSinMov)->fetchAll(PDO::FETCH_OBJ);

        // 6. Tab Dinero: Métodos
        $sqlMet = "
            SELECT 
                COALESCE(mp.nombre, pr.moneda) AS nombre,
                SUM(pr.monto / CASE WHEN pr.moneda = 'VES' AND pr.tasa_cambio > 0 THEN pr.tasa_cambio ELSE 1 END) AS monto
            FROM pagos_registro pr
            INNER JOIN ventas v ON pr.id_venta = v.id
            LEFT JOIN metodos_pago mp ON pr.id_metodo_pago = mp.id
            WHERE pr.estado_verificacion = 'aprobado'
              AND DATE(pr.fecha_pago) BETWEEN ? AND ?
            GROUP BY mp.nombre, pr.moneda
            ORDER BY monto DESC
        ";
        $stmtMet = $this->db->prepare($sqlMet);
        $stmtMet->execute([$desde, $hasta]);
        $metodos = $stmtMet->fetchAll(PDO::FETCH_OBJ);

        // 7. Tab Dinero: Proveedores
        $proveedores = [];
        try {
            $colFecha = 'fecha';
            $checkCols = $this->db->query("SHOW COLUMNS FROM compras LIKE 'fecha'")->fetch();
            if (!$checkCols) {
                $checkColsCompra = $this->db->query("SHOW COLUMNS FROM compras LIKE 'fecha_compra'")->fetch();
                $colFecha = $checkColsCompra ? 'fecha_compra' : 'created_at';
            }

            $sqlProv = "
                SELECT 
                    COALESCE(prv.razon_social, 'Proveedor') AS razon_social,
                    COUNT(c.id) AS compras,
                    COALESCE(SUM(c.total), 0) AS total
                FROM compras c
                LEFT JOIN proveedores prv ON c.id_proveedor = prv.id
                WHERE DATE(c.{$colFecha}) BETWEEN ? AND ?
                GROUP BY prv.id, prv.razon_social
                ORDER BY total DESC
                LIMIT 10
            ";
            $stmtProv = $this->db->prepare($sqlProv);
            $stmtProv->execute([$desde, $hasta]);
            $proveedores = $stmtProv->fetchAll(PDO::FETCH_OBJ);
        } catch (\Exception $e) {
            $proveedores = [];
        }

        $estadisticas = [
            'resumen'        => $resumen,
            'dias'           => $dias,
            'categorias'     => $categorias,
            'top'            => $top,
            'sin_movimiento' => $sin_movimiento,
            'metodos'        => $metodos,
            'proveedores'    => $proveedores
        ];

        if (file_exists('views/seccion_graficos.php')) {
            require_once 'views/seccion_graficos.php';
        } elseif (file_exists('views/admin/seccion_graficos.php')) {
            require_once 'views/admin/seccion_graficos.php';
        }
    }
    public function verReporte() {
        require_once 'models/MetodoPagoModel.php';
        $metodoModel = new MetodoPagoModel($this->db);

        $idMetodo = (int)($_GET['metodo'] ?? 4);
        $metodo = $metodoModel->obtenerPorId($idMetodo);

        if (!$metodo) {
            $_SESSION['aviso'] = 'El método de pago no existe.';
            header("Location: index.php?action=metodos_pago");
            exit();
        }

        $metodoId     = (int)$metodo->id;
        $metodoNombre = $metodo->nombre ?? 'Pago Móvil';

        $desde = $_GET['desde'] ?? date('Y-m-01');
        $hasta = $_GET['hasta'] ?? date('Y-m-d');
        $bancoSel  = trim($_GET['banco'] ?? '');
        $estadoSel = trim($_GET['estado'] ?? '');

        $listaBruta = $metodoModel->obtenerReportePagos($metodoId, '', '', $bancoSel, $estadoSel);

        $ventas = [];
        $tasa = 1;

        foreach ($listaBruta as $p) {
            $tasa = (float)($p->tasa_cambio ?? 1);
            $ventas[] = (object)[
                'id'             => (int)$p->id_venta,
                'fecha'          => $p->fecha_pago,
                'nombre_cliente' => !empty(trim($p->cliente_nombre)) ? $p->cliente_nombre : 'Consumidor final',
                'total'          => (float)($p->monto ?? 0),
                'total_bs'       => (float)($p->monto_bs ?? 0),
                'banco'          => $p->banco ?? '',
                'referencia'     => $p->referencia ?? ''
            ];
        }

        require_once 'views/ventas/cobrado_metodo.php';
    }

    public function confirmarPago() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once 'models/MetodoPagoModel.php';
            $metodoModel = new MetodoPagoModel();

            $idPago = (int)($_POST['id_pago'] ?? 0);
            $idMetodo = (int)($_POST['id_metodo'] ?? 1);
            $idUsuario = $_SESSION['id_usuario'] ?? 1;

            if ($idPago > 0) {
                $metodoModel->confirmarPago($idPago, $idUsuario);
                $this->log('Pagos', 'UPDATE', "Confirmó pago ID: {$idPago}");
                $_SESSION['aviso'] = 'Pago verificado y aprobado correctamente.';
            }

            header("Location: index.php?action=ver_reporte&metodo=" . $idMetodo);
            exit();
        }
    }

    public function metodosPago() {
        require_once 'models/MetodoPagoModel.php';
        $metodos = (new MetodoPagoModel($this->db))->listar();
        require_once 'views/metodos_pago.php';
    }

    public function guardarMetodoPago() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?action=metodos_pago");
            exit();
        }
        require_once 'models/MetodoPagoModel.php';
        $metodoModel = new MetodoPagoModel($this->db);

        $id = (int)($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');

        if ($nombre === '') {
            $_SESSION['aviso'] = 'Escribe el nombre del método.';
        } elseif ($metodoModel->existeNombre($nombre, $id)) {
            $_SESSION['aviso'] = 'Ya existe un método llamado ' . $nombre . '.';
        } else {
            $metodoModel->guardar([
                'id' => $id,
                'nombre' => $nombre,
                'moneda' => ($_POST['moneda'] ?? 'Bs') === 'USD' ? 'USD' : 'Bs',
                'requiere_referencia' => isset($_POST['requiere_referencia']) ? 1 : 0,
                'en_tienda' => isset($_POST['en_tienda']) ? 1 : 0,
                'en_online' => isset($_POST['en_online']) ? 1 : 0,
                'datos' => trim($_POST['datos'] ?? '')
            ]);
            $_SESSION['aviso'] = $id ? 'Método actualizado.' : 'Método creado.';
        }
        header("Location: index.php?action=metodos_pago");
        exit();
    }

    public function cambiarEstadoMetodo() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id'])) {
            require_once 'models/MetodoPagoModel.php';
            $activo = (int)($_POST['activo'] ?? 0) === 1 ? 1 : 0;
            (new MetodoPagoModel($this->db))->cambiarEstado((int)$_POST['id'], $activo);
            $_SESSION['aviso'] = $activo ? 'Método activado.' : 'Método apagado.';
        }
        header("Location: index.php?action=metodos_pago");
        exit();
    }
}