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

        // 1. Tasa BCV desde sistema
        $tasa = 881.07;
        try {
            $stmtTasa = $this->db->query("SELECT tasa_bcv, tasa_manual, tasa_modo FROM sistema LIMIT 1");
            $resTasa = $stmtTasa->fetch(PDO::FETCH_OBJ);
            if ($resTasa) {
                $tasa = ($resTasa->tasa_modo === 'manual' && (float)$resTasa->tasa_manual > 0) 
                        ? (float)$resTasa->tasa_manual 
                        : (float)($resTasa->tasa_bcv ?: 881.07);
            }
        } catch (Exception $e) {}

        // 2. Ventas de Hoy y de Ayer
        $ventasHoyMonto = 0.0;
        $ventasHoyCantidad = 0;
        $ventasAyerMonto = 0.0;

        try {
            $sqlHoy = "SELECT COALESCE(SUM(total), 0) AS monto, COUNT(numero_orden) AS cantidad 
                       FROM ventas 
                       WHERE DATE(fecha) = ? 
                         AND estado NOT IN ('cancelado', 'anulado')";
            $stmtHoy = $this->db->prepare($sqlHoy);
            $stmtHoy->execute([$hoy]);
            if ($resHoy = $stmtHoy->fetch(PDO::FETCH_ASSOC)) {
                $ventasHoyMonto = (float)$resHoy['monto'];
                $ventasHoyCantidad = (int)$resHoy['cantidad'];
            }

            $sqlAyer = "SELECT COALESCE(SUM(total), 0) AS monto 
                        FROM ventas 
                        WHERE DATE(fecha) = ? 
                          AND estado NOT IN ('cancelado', 'anulado')";
            $stmtAyer = $this->db->prepare($sqlAyer);
            $stmtAyer->execute([$ayer]);
            $ventasAyerMonto = (float)($stmtAyer->fetchColumn() ?: 0.0);
        } catch (Exception $e) {}

        // 3. Pagos por verificar (tabla real: pagos)
        $pagosPendientes = [];
        $pagosPendientesMonto = 0.0;
        try {
            $sqlPagosPend = "SELECT p.*, 
                                    p.numero_pago AS id,
                                    COALESCE(mp.nombre, p.codigo_metodo) AS metodo,
                                    COALESCE(c.nombre_razon_social, u.nombre, 'Cliente') AS cliente
                             FROM pagos p
                             INNER JOIN ventas v ON p.numero_orden = v.numero_orden
                             LEFT JOIN clientes c ON v.cedula_cliente = c.cedula
                             LEFT JOIN usuarios u ON v.cedula_usuario = u.cedula
                             LEFT JOIN metodos_pago mp ON p.codigo_metodo = mp.codigo_metodo
                             WHERE p.estado = 'pendiente'
                             ORDER BY p.fecha_pago DESC";
            $stmtPP = $this->db->query($sqlPagosPend);
            $todosPagosPend = $stmtPP ? $stmtPP->fetchAll(PDO::FETCH_OBJ) : [];
            foreach ($todosPagosPend as $p) {
                $pagosPendientesMonto += (float)($p->monto ?? 0);
            }
            $pagosPendientes = array_slice($todosPagosPend, 0, 5);
        } catch (Exception $e) {}

        // 4. Pedidos por retirar
        $porRetirar = [];
        $totalPorRetirar = 0;
        try {
            $sqlRetirar = "SELECT v.*, 
                                  v.numero_orden AS id,
                                  COALESCE(c.nombre_razon_social, u.nombre, 'Consumidor Final') AS nombre_cliente
                           FROM ventas v
                           LEFT JOIN clientes c ON v.cedula_cliente = c.cedula
                           LEFT JOIN usuarios u ON v.cedula_usuario = u.cedula
                           WHERE v.estado IN ('pagado', 'aprobado', 'verificado', 'completado', 'por_retirar')
                             AND (v.fecha_entrega IS NULL OR v.fecha_entrega = '2000-01-01 00:00:00')
                           ORDER BY v.fecha DESC";
            $stmtRet = $this->db->query($sqlRetirar);
            $todosRetirar = $stmtRet ? $stmtRet->fetchAll(PDO::FETCH_OBJ) : [];
            $totalPorRetirar = count($todosRetirar);
            $porRetirar = array_slice($todosRetirar, 0, 5);
        } catch (Exception $e) {}

        // 5. Stock bajo y agotados
        $stockBajo = [];
        $totalStockBajo = 0;
        $totalAgotados = 0;
        try {
            $sqlStock = "SELECT p.*, 
                                p.codigo AS id,
                                COALESCE(c.nombre_categoria, 'General') AS categoria
                         FROM productos p
                         LEFT JOIN categorias c ON p.slug_categoria = c.slug
                         WHERE p.stock <= p.stock_minimo 
                           AND (p.deleted_at IS NULL OR p.deleted_at = '2000-01-01 00:00:00')
                         ORDER BY p.stock ASC";
            $stmtStk = $this->db->query($sqlStock);
            $todosStock = $stmtStk ? $stmtStk->fetchAll(PDO::FETCH_OBJ) : [];
            $totalStockBajo = count($todosStock);
            foreach ($todosStock as $prod) {
                if ((int)$prod->stock <= 0) $totalAgotados++;
            }
            $stockBajo = array_slice($todosStock, 0, 5);
        } catch (Exception $e) {}

        // 6. Matriz de KPIs para la cabecera
        $kpis = [
            'ventas_hoy'             => $ventasHoyMonto,
            'ventas_hoy_n'           => $ventasHoyCantidad,
            'ventas_ayer'            => $ventasAyerMonto,
            'pagos_pendientes'       => count($todosPagosPend ?? []),
            'pagos_pendientes_monto' => $pagosPendientesMonto,
            'por_retirar'            => $totalPorRetirar,
            'stock_bajo'             => $totalStockBajo,
            'agotados'               => $totalAgotados
        ];
        $k = $kpis;

        // 7. Gráfica de los últimos 7 días
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
                            AND estado NOT IN ('cancelado', 'anulado')
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
        $busqueda  = trim((string)($_GET['q'] ?? $_GET['busqueda'] ?? ''));
        $categoria = trim((string)($_GET['categoria'] ?? $_GET['slug_categoria'] ?? ''));

        // Obtener la lista filtrada o completa según los parámetros
        $listaProductos = $this->productoModelo->obtenerFiltrados($busqueda, $categoria);

        // Listar categorías para el selector desplegable
        $stmtCat = $this->db->query("
            SELECT slug, slug AS id, nombre_categoria 
            FROM categorias 
            WHERE slug != 'raiz' 
            ORDER BY nombre_categoria ASC
        ");
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
        $stmtCat = $this->db->query("
            SELECT slug, 
                   slug AS id, 
                   nombre_categoria 
            FROM categorias 
            WHERE slug != 'raiz'
            ORDER BY nombre_categoria ASC
        ");
        $cats = $stmtCat->fetchAll(PDO::FETCH_OBJ);

        // Si existe la tabla de atributos por categoría
        try {
            $stmtAtr = $this->db->prepare("SELECT id, nombre FROM categoria_atributos WHERE id_categoria = ?");
            foreach ($cats as $cat) {
                $stmtAtr->execute([$cat->slug]);
                $cat->atributos = $stmtAtr->fetchAll(PDO::FETCH_OBJ);
            }
        } catch (Exception $e) {
            foreach ($cats as $cat) {
                $cat->atributos = [];
            }
        }

        return $cats;
    }

    public function agregar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $db = $this->productoModelo->getDb();
            try {
                $nombre = trim((string)($_POST['nombre_producto'] ?? $_POST['nombre'] ?? ''));
                $codigo = trim((string)($_POST['codigo'] ?? ''));
                $desc   = trim((string)($_POST['descripcion'] ?? $_POST['desc'] ?? ''));
                $precio = (float)($_POST['precio'] ?? 0);
                $costo  = (float)($_POST['costo_compra'] ?? 0);
                $stock  = (int)($_POST['stock'] ?? 0);
                $stockMinimo = (int)($_POST['stock_minimo'] ?? 5);
                
                // Categoría por slug
                $slugCat = !empty($_POST['slug_categoria']) ? trim((string)$_POST['slug_categoria']) : (!empty($_POST['categoria']) ? trim((string)$_POST['categoria']) : (!empty($_POST['id_categoria']) ? trim((string)$_POST['id_categoria']) : 'general'));
                
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
                    'stock_minimo'    => $stockMinimo,
                    'slug_categoria'  => $slugCat,
                    'estado'          => $estado,
                    'imagen'          => $nombreImagen
                ];

                $db->beginTransaction();

                $nuevoCodigo = $this->productoModelo->guardarCompleto($datos, $atributos, $variantes);
                $this->log('Inventario', 'INSERT', "Creó producto: {$nombre} (Código: {$nuevoCodigo})");

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
    $codigo = trim((string)($_GET['id'] ?? $_GET['codigo'] ?? ''));
    $p = $this->productoModelo->obtenerPorId($codigo);

    if (!$p) {
        $_SESSION['aviso'] = 'El producto solicitado no existe.';
        header("Location: index.php?action=listado_productos");
        exit();
    }

    $p->variantes = $this->productoModelo->obtenerVariantesProducto($codigo);
    $categorias = $this->productoModelo->obtenerCategorias();

    $aviso = $_SESSION['aviso'] ?? null;
    unset($_SESSION['aviso']);

    require_once 'views/editar_producto.php';
}

  public function actualizar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $db = $this->productoModelo->getDb();
            // Tomar el código alfanumérico (ej: A-000236)
            $codigoOriginal = trim((string)($_POST['codigo_original'] ?? $_POST['id'] ?? $_POST['codigo'] ?? ''));

            try {
                if (empty($codigoOriginal)) {
                    throw new Exception("Identificador de producto no recibido.");
                }

                $pActual = $this->productoModelo->obtenerPorId($codigoOriginal);

                if (!$pActual) {
                    throw new Exception("El producto a actualizar no existe.");
                }

                $codigo = trim((string)($_POST['codigo'] ?? $pActual->codigo));

                // Si se cambió el código, verificar que no colisione con otro
                if (!empty($codigo) && $codigo !== $codigoOriginal && $this->productoModelo->existeCodigo($codigo, $codigoOriginal)) {
                    throw new Exception("El código '{$codigo}' ya está asignado a otro producto.");
                }

                $nombreImagen = $pActual->imagen;
                if (!empty($_FILES['imagen']['tmp_name'])) {
                    $nombreImagen = $this->procesarImagenUpload($_FILES['imagen'], $pActual->imagen);
                }

                $nombre = trim((string)($_POST['nombre_producto'] ?? $_POST['nombre'] ?? $pActual->nombre_producto));
                $desc   = trim((string)($_POST['descripcion'] ?? $_POST['desc'] ?? $pActual->descripcion));
                $precio = isset($_POST['precio']) && $_POST['precio'] !== '' ? (float)$_POST['precio'] : (float)$pActual->precio;
                $costo  = isset($_POST['costo_compra']) && $_POST['costo_compra'] !== '' ? (float)$_POST['costo_compra'] : (float)($pActual->costo_compra ?? 0);
                
                $stockMinimo = isset($_POST['stock_minimo']) && $_POST['stock_minimo'] !== '' 
                                ? (int)$_POST['stock_minimo'] 
                                : (int)($pActual->stock_minimo ?? 5);

                $slugCat = !empty($_POST['slug_categoria']) ? trim((string)$_POST['slug_categoria']) : (!empty($_POST['categoria']) ? trim((string)$_POST['categoria']) : (!empty($_POST['id_categoria']) ? trim((string)$_POST['id_categoria']) : $pActual->slug_categoria));
                
                $estado = isset($_POST['estado']) ? (int)$_POST['estado'] : 1;
                $atributos = $_POST['atributo'] ?? [];
                $variantes = $_POST['variantes'] ?? [];

                $datos = [
                    'codigo'          => $codigo,
                    'nombre_producto' => $nombre,
                    'descripcion'     => $desc,
                    'precio'          => $precio,
                    'costo_compra'    => $costo,
                    'stock_minimo'    => $stockMinimo,
                    'slug_categoria'  => $slugCat,
                    'estado'          => $estado,
                    'imagen'          => $nombreImagen
                ];

                $db->beginTransaction();

                $this->productoModelo->actualizarCompleto($codigoOriginal, $datos, $atributos, $variantes);

                $this->log('Inventario', 'UPDATE', "Actualizó producto: {$nombre} (Código: {$codigoOriginal})");

                $db->commit();

                $_SESSION['aviso'] = 'Producto actualizado correctamente.';
                header("Location: index.php?action=listado_productos");
                exit();

            } catch (Exception $e) {
                if ($db && $db->inTransaction()) {
                    $db->rollBack();
                }
                $_SESSION['aviso_error'] = $e->getMessage();
                header("Location: index.php?action=editar&codigo=" . urlencode($codigoOriginal));
                exit();
            }
        }
    }


    public function eliminar() {
    $codigo = trim((string)($_GET['id'] ?? $_GET['codigo'] ?? ''));
    if (!empty($codigo)) {
        $this->productoModelo->borrarProducto($codigo);
        $this->log('Inventario', 'DELETE', "Eliminó producto (borrado lógico) Código: {$codigo}");
        $_SESSION['aviso'] = 'Producto eliminado correctamente.';
    }
    header("Location: index.php?action=listado_productos");
    exit();
}

    // -------------------------------------------------------------------------
    // CATEGORÍAS Y ATRIBUTOS
    // -------------------------------------------------------------------------

    public function categorias() {
        $sql = "SELECT c.slug, 
                       c.slug AS id, 
                       c.nombre_categoria,
                       '' AS nombre_padre,
                       COUNT(pr.codigo) AS total_productos,
                       COALESCE(SUM(pr.stock), 0) AS total_unidades
                FROM categorias c
                LEFT JOIN productos pr ON pr.slug_categoria = c.slug 
                     AND (pr.deleted_at IS NULL OR pr.deleted_at = '2000-01-01 00:00:00' OR pr.deleted_at = '0000-00-00 00:00:00')
                WHERE c.slug != 'raiz'
                GROUP BY c.slug, c.nombre_categoria
                ORDER BY c.nombre_categoria ASC";

        $stmt = $this->db->query($sql);
        $categorias = $stmt->fetchAll(PDO::FETCH_OBJ);

        // Atributos de cada categoría si existen en la tabla auxiliar
        try {
            $stmtAtr = $this->db->prepare("SELECT id, nombre FROM categoria_atributos WHERE id_categoria = ?");
            foreach ($categorias as $cat) {
                $stmtAtr->execute([$cat->slug]);
                $cat->atributos = $stmtAtr->fetchAll(PDO::FETCH_OBJ);
            }
        } catch (Exception $e) {
            foreach ($categorias as $cat) {
                $cat->atributos = [];
            }
        }

        if (file_exists('views/categorias.php')) {
            require_once 'views/categorias.php';
        } elseif (file_exists('views/inventario/categorias.php')) {
            require_once 'views/inventario/categorias.php';
        }
    }

   public function guardarCategoria() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $slugOriginal = trim((string)($_POST['slug'] ?? $_POST['id'] ?? ''));
            $nombre = trim((string)($_POST['nombre_categoria'] ?? $_POST['nombre'] ?? ''));

            if (empty($nombre)) {
                $_SESSION['aviso_error'] = "El nombre de la categoría es obligatorio.";
                header("Location: index.php?action=categorias");
                exit();
            }

            // Generar slug limpio si es nueva
            $slug = !empty($slugOriginal) ? $slugOriginal : preg_replace('/[^a-z0-9]+/i', '-', strtolower(trim($nombre)));

            try {
                $this->db->beginTransaction();

                if (!empty($slugOriginal)) {
                    // Editar categoría existente
                    $stmt = $this->db->prepare("UPDATE categorias SET nombre_categoria = ? WHERE slug = ?");
                    $stmt->execute([$nombre, $slugOriginal]);
                    $this->log('Inventario', 'UPDATE', "Editó categoría: {$nombre} ({$slugOriginal})");
                } else {
                    // Crear categoría nueva
                    // Verificar si el slug ya existe para evitar duplicado
                    $stmtCheck = $this->db->prepare("SELECT COUNT(*) FROM categorias WHERE slug = ?");
                    $stmtCheck->execute([$slug]);
                    if ((int)$stmtCheck->fetchColumn() > 0) {
                        $slug .= '-' . time();
                    }

                    $stmt = $this->db->prepare("INSERT INTO categorias (slug, nombre_categoria) VALUES (?, ?)");
                    $stmt->execute([$slug, $nombre]);
                    $this->log('Inventario', 'INSERT', "Creó categoría: {$nombre} ({$slug})");
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
            $slug = trim((string)($_POST['slug'] ?? $_POST['id'] ?? ''));

            if (!empty($slug)) {
                // Verificar si tiene productos asignados
                $stmtCheck = $this->db->prepare("
                    SELECT COUNT(*) 
                    FROM productos 
                    WHERE slug_categoria = ? 
                      AND (deleted_at IS NULL OR deleted_at = '2000-01-01 00:00:00' OR deleted_at = '0000-00-00 00:00:00')
                ");
                $stmtCheck->execute([$slug]);
                $totalAsociados = (int)$stmtCheck->fetchColumn();

                if ($totalAsociados > 0) {
                    $_SESSION['aviso_error'] = "No se puede eliminar la categoría porque tiene {$totalAsociados} producto(s) asociado(s).";
                } else {
                    $this->db->prepare("DELETE FROM categorias WHERE slug = ?")->execute([$slug]);
                    $this->log('Inventario', 'DELETE', "Eliminó categoría: {$slug}");
                    $_SESSION['aviso'] = "Categoría eliminada correctamente.";
                }
            }

            header("Location: index.php?action=categorias");
            exit();
        }
    }
// -------------------------------------------------------------------------
    // AJUSTES DE STOCK Y KARDEX
    // -------------------------------------------------------------------------

    // -------------------------------------------------------------------------
    // AJUSTES DE STOCK Y KARDEX
    // -------------------------------------------------------------------------

    public function ajustesInventario() {
        $stmtP = $this->db->query("
            SELECT codigo, codigo AS id, nombre_producto, stock, stock_minimo 
            FROM productos 
            WHERE (deleted_at IS NULL OR deleted_at = '2000-01-01 00:00:00' OR deleted_at = '0000-00-00 00:00:00')
            ORDER BY nombre_producto ASC
        ");
        $productos = $stmtP->fetchAll(PDO::FETCH_OBJ);

        $stmtAjustes = $this->db->query("
            SELECT p.nombre_producto, 
                   k.tipo_movimiento, 
                   k.observacion, 
                   COALESCE(k.fecha_hora, NOW()) AS fecha,
                   COALESCE(u.nombre, 'Admin') AS usuario, 
                   k.cantidad
            FROM kardex_inventario k
            INNER JOIN productos p ON k.codigo_producto = p.codigo
            LEFT JOIN usuarios u ON k.cedula_usuario = u.cedula
            WHERE k.tipo_movimiento IN ('ajuste', 'merma', 'transferencia', 'ajuste_inventario')
            ORDER BY k.fecha_hora DESC LIMIT 5
        ");
        $ultimosAjustes = $stmtAjustes ? $stmtAjustes->fetchAll(PDO::FETCH_OBJ) : [];

        $rutas = [
            'views/inventario/ajustes.php',
            'views/ajustes_inventario.php',
            'views/ajustes.php'
        ];

        foreach ($rutas as $vista) {
            if (file_exists($vista)) {
                require_once $vista;
                return;
            }
        }
    }

    public function guardarAjuste() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?action=ajustes_inventario");
            exit();
        }

        $codigoProducto = trim((string)($_POST['id_producto'] ?? $_POST['codigo'] ?? $_POST['codigo_producto'] ?? ''));
        $tipo           = trim((string)($_POST['tipo_movimiento'] ?? 'ajuste'));
        $cantidad       = (int)($_POST['cantidad'] ?? 0);
        $observacion    = trim((string)($_POST['observacion'] ?? 'Ajuste manual de inventario'));
        $cedulaUsuario  = $_SESSION['cedula'] ?? $_SESSION['id_usuario'] ?? 'SIN_USUARIO';

        if (empty($codigoProducto) || $cantidad <= 0) {
            header("Location: index.php?action=ajustes_inventario");
            exit();
        }

        try {
            $this->db->beginTransaction();

            $stmtP = $this->db->prepare("SELECT stock FROM productos WHERE codigo = ?");
            $stmtP->execute([$codigoProducto]);
            $stockActual = (int)$stmtP->fetchColumn();

            // Determinar si descuenta (merma) o suma (ajuste de entrada)
            if ($tipo === 'merma') {
                $nuevoStock = max(0, $stockActual - $cantidad);
                $cantKardex = -$cantidad;
            } else {
                $nuevoStock = $stockActual + $cantidad;
                $cantKardex = $cantidad;
            }

            // Actualizar stock del producto
            $stmtUpd = $this->db->prepare("UPDATE productos SET stock = ? WHERE codigo = ?");
            $stmtUpd->execute([$nuevoStock, $codigoProducto]);

            // Obtener siguiente movimiento para este producto
            $stmtSec = $this->db->prepare("SELECT COALESCE(MAX(numero_movimiento), 0) + 1 FROM kardex_inventario WHERE codigo_producto = ?");
            $stmtSec->execute([$codigoProducto]);
            $numMov = (int)$stmtSec->fetchColumn();

            // Insertar en kardex_inventario
            $stmtK = $this->db->prepare("
                INSERT INTO kardex_inventario 
                (codigo_producto, numero_movimiento, tipo_movimiento, codigo_variante, cantidad, stock_resultante, referencia, observacion, cedula_usuario, fecha_hora)
                VALUES (?, ?, ?, '', ?, ?, 'AJUSTE', ?, ?, NOW())
            ");
            $stmtK->execute([$codigoProducto, $numMov, $tipo, $cantKardex, $nuevoStock, $observacion, $cedulaUsuario]);

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

        if (!empty($tipo)) {
            $where[] = "k.tipo_movimiento = ?";
            $params[] = $tipo;
        }
        if (!empty($desde)) {
            $where[] = "DATE(k.fecha_hora) >= ?";
            $params[] = $desde;
        }
        if (!empty($hasta)) {
            $where[] = "DATE(k.fecha_hora) <= ?";
            $params[] = $hasta;
        }
        if (!empty($q)) {
            $where[] = "(p.nombre_producto LIKE ? OR p.codigo LIKE ? OR k.referencia LIKE ?)";
            $params[] = "%$q%"; 
            $params[] = "%$q%";
            $params[] = "%$q%";
        }

        $sql = "SELECT k.fecha_hora,
                       k.fecha_hora AS fecha,
                       p.nombre_producto, 
                       p.imagen, 
                       p.stock_minimo,
                       k.tipo_movimiento, 
                       k.cantidad, 
                       k.stock_resultante,
                       k.referencia AS id_referencia, 
                       k.referencia,
                       k.observacion, 
                       COALESCE(u.nombre, 'Sistema') AS usuario
                FROM kardex_inventario k
                INNER JOIN productos p ON k.codigo_producto = p.codigo
                LEFT JOIN usuarios u ON k.cedula_usuario = u.cedula
                WHERE " . implode(' AND ', $where) . "
                ORDER BY k.fecha_hora DESC";
                
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $movimientos = $stmt->fetchAll(PDO::FETCH_OBJ);

        $rutas = [
            'views/inventario/kardex.php',
            'views/kardex.php',
            'views/admin/kardex.php'
        ];

        foreach ($rutas as $vista) {
            if (file_exists($vista)) {
                require_once $vista;
                return;
            }
        }
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

        // 1. Resumen General (KPIs)
        $sqlResumen = "
            SELECT 
                COALESCE(SUM(v.total), 0) AS vendido,
                COUNT(v.numero_orden) AS ventas,
                COALESCE(SUM(CASE WHEN v.origen_venta IN ('tienda', 'tienda_fisica') THEN v.total ELSE 0 END), 0) AS tienda,
                COALESCE(SUM(
                    (SELECT COALESCE(SUM((dv.precio_unitario - dv.costo_unitario) * dv.cantidad), 0)
                     FROM detalle_ventas dv 
                     WHERE dv.numero_orden = v.numero_orden)
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
                COALESCE(SUM(CASE WHEN v.origen_venta IN ('tienda', 'tienda_fisica') THEN v.total ELSE 0 END), 0) AS tienda,
                COALESCE(SUM(CASE WHEN v.origen_venta NOT IN ('tienda', 'tienda_fisica') THEN v.total ELSE 0 END), 0) AS online
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
                COALESCE(c.nombre_categoria, 'Sin categoría') AS nombre,
                COALESCE(SUM(dv.subtotal), 0) AS vendido,
                COALESCE(SUM((dv.precio_unitario - dv.costo_unitario) * dv.cantidad), 0) AS ganancia
            FROM detalle_ventas dv
            INNER JOIN ventas v ON dv.numero_orden = v.numero_orden
            INNER JOIN productos p ON dv.codigo_producto = p.codigo
            LEFT JOIN categorias c ON p.slug_categoria = c.slug
            WHERE v.estado != 'cancelado' 
              AND DATE(v.fecha) BETWEEN ? AND ?
            GROUP BY c.slug, c.nombre_categoria
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
            INNER JOIN ventas v ON dv.numero_orden = v.numero_orden
            INNER JOIN productos p ON dv.codigo_producto = p.codigo
            LEFT JOIN categorias c ON p.slug_categoria = c.slug
            WHERE v.estado != 'cancelado' 
              AND DATE(v.fecha) BETWEEN ? AND ?
            GROUP BY p.codigo, p.nombre_producto, c.nombre_categoria
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
            LEFT JOIN categorias c ON p.slug_categoria = c.slug
            LEFT JOIN detalle_ventas dv ON dv.codigo_producto = p.codigo
            LEFT JOIN ventas v ON dv.numero_orden = v.numero_orden AND v.estado != 'cancelado'
            WHERE p.estado = 1 AND (p.deleted_at IS NULL OR p.deleted_at = '2000-01-01 00:00:00')
            GROUP BY p.codigo, p.nombre_producto, c.nombre_categoria, p.stock
            HAVING dias >= 30 OR dias IS NULL
            ORDER BY dias DESC, p.stock DESC
            LIMIT 10
        ";
        $sin_movimiento = $this->db->query($sqlSinMov)->fetchAll(PDO::FETCH_OBJ);

        // 6. Tab Dinero: Métodos
        $sqlMet = "
            SELECT 
                COALESCE(mp.nombre, pr.codigo_metodo) AS nombre,
                SUM(pr.monto / CASE WHEN UPPER(pr.moneda) = 'VES' AND pr.tasa_cambio > 0 THEN pr.tasa_cambio ELSE 1 END) AS monto
            FROM pagos pr
            INNER JOIN ventas v ON pr.numero_orden = v.numero_orden
            LEFT JOIN metodos_pago mp ON pr.codigo_metodo = mp.codigo_metodo
            WHERE pr.estado IN ('aprobado', 'verificado')
              AND DATE(pr.fecha_pago) BETWEEN ? AND ?
            GROUP BY mp.nombre, pr.codigo_metodo
            ORDER BY monto DESC
        ";
        $stmtMet = $this->db->prepare($sqlMet);
        $stmtMet->execute([$desde, $hasta]);
        $metodos = $stmtMet->fetchAll(PDO::FETCH_OBJ);

        // 7. Tab Dinero: Proveedores
        $proveedores = [];
        try {
            $sqlProv = "
                SELECT 
                    COALESCE(prv.razon_social, 'Proveedor') AS razon_social,
                    COUNT(c.numero_orden) AS compras,
                    COALESCE(SUM(c.total), 0) AS total
                FROM compras c
                LEFT JOIN proveedores prv ON c.rif_proveedor = prv.rif
                WHERE DATE(c.fecha) BETWEEN ? AND ?
                GROUP BY prv.rif, prv.razon_social
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

        // Recibir el identificador alfanumérico (ej: efectivo_usd, pago_movil)
        $idMetodo = trim((string)($_GET['metodo'] ?? 'pago_movil'));
        $metodo = $metodoModel->obtenerPorId($idMetodo);

        $metodoId     = $metodo->codigo_metodo ?? $idMetodo;
        $metodoNombre = $metodo->nombre ?? 'Método de pago';

        // Fechas enviadas por los filtros de la vista
        $desde = trim((string)($_GET['desde'] ?? ''));
        $hasta = trim((string)($_GET['hasta'] ?? ''));
        $bancoSel  = trim((string)($_GET['banco'] ?? ''));
        $estadoSel = trim((string)($_GET['estado'] ?? ''));

        // Obtener pagos reales
        $pagos = $metodoModel->obtenerReportePagos($metodoId, $desde, $hasta, $bancoSel, $estadoSel);
        $ventas = $pagos;

        // Calcular resumen de totales para las tarjetas superiores
        $resumen = $metodoModel->obtenerResumenTotales($metodoId, $desde, $hasta, $bancoSel, $estadoSel);
        $totales = $resumen;

        // Bancos para el filtro desplegable
        $bancos = $metodoModel->obtenerBancosDisponibles();

        // Tasa BCV
        $tasa = 881.07;
        try {
            $stmtSis = $this->db->query("SELECT tasa_bcv FROM sistema LIMIT 1");
            if ($sis = $stmtSis->fetch(PDO::FETCH_OBJ)) {
                $tasa = (float)($sis->tasa_bcv ?: 881.07);
            }
        } catch (Exception $e) {}

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

        $codigo = trim((string)($_POST['codigo_metodo'] ?? $_POST['id'] ?? ''));
        $nombre = trim((string)($_POST['nombre'] ?? ''));

        if ($nombre === '') {
            $_SESSION['aviso'] = 'Escribe el nombre del método.';
        } elseif ($metodoModel->existeNombre($nombre, $codigo)) {
            $_SESSION['aviso'] = 'Ya existe un método llamado ' . $nombre . '.';
        } else {
            $metodoModel->guardar([
                'codigo_metodo'       => $codigo,
                'id'                  => $codigo,
                'nombre'              => $nombre,
                'moneda'              => ($_POST['moneda'] ?? 'Bs') === 'USD' ? 'USD' : 'Bs',
                'requiere_referencia' => isset($_POST['requiere_referencia']) ? 1 : 0,
                'en_tienda'           => isset($_POST['en_tienda']) ? 1 : 0,
                'en_online'           => isset($_POST['en_online']) ? 1 : 0
            ]);
            $_SESSION['aviso'] = !empty($codigo) ? 'Método actualizado.' : 'Método creado.';
        }
        header("Location: index.php?action=metodos_pago");
        exit();
    }

    public function cambiarEstadoMetodo() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!empty($_POST['codigo_metodo']) || !empty($_POST['id']))) {
            require_once 'models/MetodoPagoModel.php';
            $codigo = trim((string)($_POST['codigo_metodo'] ?? $_POST['id']));
            $activo = (int)($_POST['activo'] ?? 0) === 1 ? 1 : 0;
            (new MetodoPagoModel($this->db))->cambiarEstado($codigo, $activo);
            $_SESSION['aviso'] = $activo ? 'Método activado.' : 'Método desactivado.';
        }
        header("Location: index.php?action=metodos_pago");
        exit();
    }
}