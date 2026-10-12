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

    // Punto 1: Helper de seguridad para validación y procesamiento de imágenes
    private function procesarImagenUpload($file, $imagenAnterior = null) {
        if (empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return $imagenAnterior;
        }

        if ($file['size'] > 3 * 1024 * 1024) {
            throw new Exception("La imagen supera el límite permitido de 3 MB.");
        }

        // Validar tipo real
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

        // Borrar imagen vieja si existía una diferente
        if (!empty($imagenAnterior) && file_exists($carpeta . $imagenAnterior)) {
            @unlink($carpeta . $imagenAnterior);
        }

        return $nombreLimpio;
    }

    // -------------------------------------------------------------------------
    // VISTAS PRINCIPALES Y CATÁLOGO
    // -------------------------------------------------------------------------

    public function mostrarDashboard() {
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

        $stmtAtr = $this->db->prepare("SELECT id, nombre, opciones FROM categoria_atributos WHERE id_categoria = ?");
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

                // 1. Validar nombre obligatorio
                if (empty($nombre)) {
                    throw new Exception("El nombre del producto es obligatorio.");
                }

                // 2. Validar que el código no exista previamente
                if (!empty($codigo) && $this->productoModelo->existeCodigo($codigo)) {
                    throw new Exception("El código '{$codigo}' ya está registrado en otro producto.");
                }

                // 3. Procesar imagen de forma segura
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

                // 4. Iniciar transacción
                $db->beginTransaction();

                $nuevoId = $this->productoModelo->guardarCompleto($datos, $atributos, $variantes);
                $this->log('Inventario', 'INSERT', "Creó producto: {$nombre} (ID: {$nuevoId})");

                $db->commit();

                $_SESSION['aviso'] = 'Producto creado exitosamente.';
                header("Location: index.php?action=listado_productos");
                exit();

            } catch (Exception $e) {
                // Cancelar cualquier operación pendiente si ocurrió un error
                if ($db && $db->inTransaction()) {
                    $db->rollBack();
                }

                // Guardar el mensaje de error para mostrar el banner rojo
                $_SESSION['aviso_error'] = $e->getMessage();

                // Redirigir de inmediato al mismo formulario para verlo al instante
                header("Location: index.php?action=formulario_producto");
                exit();
            }
        }
    }
    public function editar() {
        $id = (int)($_GET['id'] ?? 0);
        $p = $this->productoModelo->obtenerPorId($id);

        // Punto 4: Si el producto no existe, redirigir a la lista
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

                // PUNTO 3 / D: Validar código
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

                // PUNTO C: Transacción
                $db->beginTransaction();

                $this->productoModelo->actualizarCompleto($id, $datos, $atributos, $variantes);
                $this->log('Inventario', 'UPDATE', "Actualizó producto y variantes: {$nombre} (ID: {$id})");

                $db->commit();

                $_SESSION['aviso'] = 'Producto actualizado correctamente.';
                header("Location: index.php?action=listado_productos");
                exit();

            } catch (Exception $e) {
                // PUNTO C: Rollback
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                // PUNTO D: Enviar aviso rojo
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
    // CATEGORÍAS Y ATRIBUTOS (Con soporte de Subcategorías)
    // -------------------------------------------------------------------------

    public function categorias() {
        $sql = "SELECT c.id, c.id_padre, c.nombre_categoria,
                       p.nombre_categoria AS nombre_padre,
                       COUNT(pr.id) AS total_productos,
                       COALESCE(SUM(pr.stock + pr.stock_online), 0) AS total_unidades
                FROM categorias c
                LEFT JOIN categorias p ON c.id_padre = p.id
                LEFT JOIN productos pr ON pr.id_categoria = c.id AND pr.deleted_at IS NULL
                GROUP BY c.id, c.id_padre, c.nombre_categoria, p.nombre_categoria
                ORDER BY COALESCE(c.id_padre, c.id), c.id_padre IS NOT NULL, c.nombre_categoria ASC";

        $stmt = $this->db->query($sql);
        $categorias = $stmt->fetchAll(PDO::FETCH_OBJ);

        $stmtAtr = $this->db->prepare("SELECT id, nombre, opciones FROM categoria_atributos WHERE id_categoria = ?");
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

                // 1. Manejo de categoría padre
                if ($rawPadre === 'nueva_padre' && !empty($nuevaPadre)) {
                    $stmtNP = $this->db->prepare("INSERT INTO categorias (nombre_categoria, id_padre) VALUES (?, NULL)");
                    $stmtNP->execute([$nuevaPadre]);
                    $idPadre = (int)$this->db->lastInsertId();
                    $this->log('Inventario', 'INSERT', "Creó categoría principal padre: {$nuevaPadre}");
                } elseif (!empty($rawPadre) && is_numeric($rawPadre)) {
                    $idPadre = (int)$rawPadre;
                }

                // 2. Guardar o actualizar la categoría
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

                // 3. Procesamiento y desduplicación de atributos
                $atrIds = $_POST['atributo_id'] ?? [];
                $atrNombres = $_POST['atributo_nombre'] ?? [];
                $atrOpciones = $_POST['atributo_opciones'] ?? [];

                $atributosProcesados = [];

                for ($i = 0; $i < count($atrNombres); $i++) {
                    $nomAtr = trim($atrNombres[$i]);
                    $claveUnica = mb_strtolower($nomAtr, 'UTF-8');

                    if ($nomAtr === '' || isset($atributosProcesados[$claveUnica])) {
                        // Omitir vacíos o atributos que ya se leyeron en este mismo formulario
                        continue;
                    }

                    // Normalizar opciones: quitar repetidas, espacios y comas sueltas
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

                // 4. Sincronizar atributos en la base de datos
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

                $stmtUpd = $this->db->prepare("UPDATE categoria_atributos SET nombre = ?, opciones = ? WHERE id = ? AND id_categoria = ?");
                $stmtIns = $this->db->prepare("INSERT INTO categoria_atributos (id_categoria, nombre, opciones) VALUES (?, ?, ?)");

                foreach ($atributosProcesados as $item) {
                    if ($item['id'] > 0) {
                        $stmtUpd->execute([$item['nombre'], $item['opciones'], $item['id'], $id]);
                    } else {
                        $stmtIns->execute([$id, $item['nombre'], $item['opciones']]);
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
        $stmtP = $this->db->query("SELECT id, codigo, nombre_producto, stock, stock_online, stock_minimo, imagen 
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
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idProducto = (int)($_POST['id_producto'] ?? 0);
            $tipo = $_POST['tipo_movimiento'] ?? '';
            $observacion = trim($_POST['observacion'] ?? '');
            $idUsuario = $_SESSION['id_usuario'] ?? 1;

            $stmtP = $this->db->prepare("SELECT stock, stock_online FROM productos WHERE id = ?");
            $stmtP->execute([$idProducto]);
            $prod = $stmtP->fetch(PDO::FETCH_OBJ);

            if ($prod) {
                $stockTienda = (int)$prod->stock;
                $stockOnline = (int)$prod->stock_online;
                $cantidadMov = 0;
                $canal = 'tienda_fisica';

                if ($tipo === 'ajuste') {
                    $canal = $_POST['canal'] ?? 'tienda_fisica';
                    $contada = (int)$_POST['cantidad_contada'];
                    if ($canal === 'tienda_fisica') {
                        $cantidadMov = $contada - $stockTienda;
                        $stockTienda = $contada;
                    } else {
                        $cantidadMov = $contada - $stockOnline;
                        $stockOnline = $contada;
                    }
                } elseif ($tipo === 'merma') {
                    $canal = $_POST['canal'] ?? 'tienda_fisica';
                    $cant = abs((int)$_POST['cantidad']);
                    $motivo = trim($_POST['motivo'] ?? 'Merma');
                    $observacion = $observacion ? "$motivo: $observacion" : $motivo;
                    $cantidadMov = -$cant;
                    if ($canal === 'tienda_fisica') {
                        $stockTienda = max(0, $stockTienda - $cant);
                    } else {
                        $stockOnline = max(0, $stockOnline - $cant);
                    }
                } elseif ($tipo === 'transferencia') {
                    $dir = $_POST['direccion'] ?? '';
                    $cant = abs((int)$_POST['cantidad']);
                    if ($dir === 'a_online') {
                        $stockTienda = max(0, $stockTienda - $cant);
                        $stockOnline += $cant;
                        $cantidadMov = $cant;
                        $canal = 'online';
                    } else {
                        $stockOnline = max(0, $stockOnline - $cant);
                        $stockTienda += $cant;
                        $cantidadMov = -$cant;
                        $canal = 'tienda_fisica';
                    }
                }

                $this->db->prepare("UPDATE productos SET stock = ?, stock_online = ? WHERE id = ?")
                         ->execute([$stockTienda, $stockOnline, $idProducto]);

                $stmtK = $this->db->prepare("
                    INSERT INTO kardex_inventario 
                    (id_producto, tipo_movimiento, canal, cantidad, stock_tienda_resultante, 
                     stock_online_resultante, observacion, id_usuario)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmtK->execute([$idProducto, $tipo, $canal, $cantidadMov, $stockTienda, $stockOnline, $observacion, $idUsuario]);

                $this->log('Inventario', 'UPDATE', "Ajuste de inventario ({$tipo}) producto ID: {$idProducto}");
            }

            // Punto 6: Aviso verde y redirección si viene volver_producto
            $_SESSION['aviso'] = 'Ajuste de stock registrado.';
            $volverId = (int)($_POST['volver_producto'] ?? 0);
            if ($volverId > 0) {
                header("Location: index.php?action=editar&id=" . $volverId);
                exit();
            }

            header("Location: index.php?action=kardex");
            exit();
        }
    }

    public function kardex() {
        $tipo = $_GET['tipo'] ?? '';
        $desde = $_GET['desde'] ?? '';
        $hasta = $_GET['hasta'] ?? '';
        $q = trim($_GET['q'] ?? '');

        $where = ["1=1"];
        $params = [];
        if (!empty($tipo)) { $where[] = "k.tipo_movimiento = ?"; $params[] = $tipo; }
        if (!empty($desde)) { $where[] = "DATE(k.fecha) >= ?"; $params[] = $desde; }
        if (!empty($hasta)) { $where[] = "DATE(k.fecha) <= ?"; $params[] = $hasta; }
        if (!empty($q)) {
            $where[] = "(p.nombre_producto LIKE ? OR p.codigo LIKE ?)";
            $params[] = "%$q%"; $params[] = "%$q%";
        }

        $sql = "SELECT k.fecha, p.nombre_producto, p.imagen, p.stock_minimo,
                       k.tipo_movimiento, k.canal, k.cantidad, 
                       k.stock_tienda_resultante, k.stock_online_resultante, 
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

        // Fechas
        $desde = $_GET['desde'] ?? date('Y-m-01');
        $hasta = $_GET['hasta'] ?? date('Y-m-d');
        $bancoSel  = trim($_GET['banco'] ?? '');
        $estadoSel = trim($_GET['estado'] ?? '');

        // Consultar los pagos registrados en la BD
        $listaBruta = $metodoModel->obtenerReportePagos($metodoId, '', '', $bancoSel, $estadoSel);

        // Mapear los datos al formato que espera views/ventas/cobrado_metodo.php
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
public function mostrarEstadisticas() {
        $desde = $_GET['desde'] ?? date('Y-m-d', strtotime('-13 days'));
        $hasta = $_GET['hasta'] ?? date('Y-m-d');
        $agrupar = ($_GET['agrupar'] ?? 'dia') === 'mes' ? 'mes' : 'dia';
        $tab = $_GET['tab'] ?? 'ventas';

        // 1. Resumen general (Vendido, Ganancia estimada, Ventas totales, Tienda Física)
        $resumen = ['vendido' => 0, 'ganancia' => 0, 'ventas' => 0, 'tienda' => 0];
        try {
            $sqlResumen = "SELECT 
                            COALESCE(SUM(v.total), 0) AS vendido,
                            COALESCE(SUM(v.total * 0.30), 0) AS ganancia,
                            COUNT(DISTINCT v.id) AS ventas,
                            COALESCE(SUM(CASE WHEN v.origen_venta = 'tienda_fisica' THEN v.total ELSE 0 END), 0) AS tienda
                           FROM ventas v
                           WHERE DATE(v.fecha) >= ? AND DATE(v.fecha) <= ?
                             AND (v.estado IN ('retirado', 'pagado', 'completada', 'aprobada') OR v.estado IS NULL)";
            $stmtRes = $this->db->prepare($sqlResumen);
            $stmtRes->execute([$desde, $hasta]);
            $res = $stmtRes->fetch(PDO::FETCH_ASSOC);
            if ($res) $resumen = $res;
        } catch (Exception $e) {}

        // 2. Ventas agrupadas por día o mes (Tienda vs Online)
        $dias = [];
        try {
            $formatoFecha = $agrupar === 'mes' ? '%Y-%m-01' : '%Y-%m-%d';
            $sqlDias = "SELECT 
                            DATE_FORMAT(v.fecha, '{$formatoFecha}') AS fecha,
                            COALESCE(SUM(CASE WHEN v.origen_venta = 'tienda_fisica' THEN v.total ELSE 0 END), 0) AS tienda,
                            COALESCE(SUM(CASE WHEN v.origen_venta != 'tienda_fisica' THEN v.total ELSE 0 END), 0) AS online
                        FROM ventas v
                        WHERE DATE(v.fecha) >= ? AND DATE(v.fecha) <= ?
                          AND (v.estado IN ('retirado', 'pagado', 'completada', 'aprobada') OR v.estado IS NULL)
                        GROUP BY DATE_FORMAT(v.fecha, '{$formatoFecha}')
                        ORDER BY fecha ASC";
            $stmtDias = $this->db->prepare($sqlDias);
            $stmtDias->execute([$desde, $hasta]);
            $dias = $stmtDias->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}

        // Detección de la columna de categorías
        $colCat = 'nombre';
        try {
            $cols = $this->db->query("SHOW COLUMNS FROM categorias")->fetchAll(PDO::FETCH_COLUMN);
            if (in_array('nombre_categoria', $cols)) {
                $colCat = 'nombre_categoria';
            } elseif (in_array('categoria', $cols)) {
                $colCat = 'categoria';
            }
        } catch (Exception $e) {}

        // 3. Ganancia por Categoría
        $categorias = [];
        try {
            $sqlCat = "SELECT 
                        COALESCE(c.{$colCat}, 'General') AS nombre,
                        COALESCE(SUM(dv.total), 0) AS vendido,
                        COALESCE(SUM(dv.total * 0.30), 0) AS ganancia
                       FROM detalle_ventas dv
                       INNER JOIN ventas v ON dv.id_venta = v.id
                       INNER JOIN productos p ON dv.id_producto = p.id
                       LEFT JOIN categorias c ON p.id_categoria = c.id
                       WHERE DATE(v.fecha) >= ? AND DATE(v.fecha) <= ?
                         AND (v.estado IN ('retirado', 'pagado', 'completada', 'aprobada') OR v.estado IS NULL)
                       GROUP BY p.id_categoria, c.{$colCat}
                       ORDER BY vendido DESC";
            $stmtCat = $this->db->prepare($sqlCat);
            $stmtCat->execute([$desde, $hasta]);
            $categorias = $stmtCat->fetchAll(PDO::FETCH_OBJ);
        } catch (Exception $e) {}

        // 4. Productos más vendidos
        $top = [];
        try {
            $sqlTop = "SELECT 
                        p.nombre_producto AS nombre,
                        COALESCE(c.{$colCat}, 'General') AS categoria,
                        SUM(dv.cantidad) AS unidades,
                        SUM(dv.total) AS monto,
                        SUM(dv.total * 0.30) AS ganancia
                       FROM detalle_ventas dv
                       INNER JOIN ventas v ON dv.id_venta = v.id
                       INNER JOIN productos p ON dv.id_producto = p.id
                       LEFT JOIN categorias c ON p.id_categoria = c.id
                       WHERE DATE(v.fecha) >= ? AND DATE(v.fecha) <= ?
                         AND (v.estado IN ('retirado', 'pagado', 'completada', 'aprobada') OR v.estado IS NULL)
                       GROUP BY p.id, p.nombre_producto, c.{$colCat}
                       ORDER BY monto DESC
                       LIMIT 5";
            $stmtTop = $this->db->prepare($sqlTop);
            $stmtTop->execute([$desde, $hasta]);
            $top = $stmtTop->fetchAll(PDO::FETCH_OBJ);
        } catch (Exception $e) {}

        // 5. Productos sin movimiento
        $sin_movimiento = [];
        try {
            $sqlSinMov = "SELECT 
                            p.nombre_producto AS nombre,
                            COALESCE(c.{$colCat}, 'General') AS categoria,
                            p.stock,
                            DATEDIFF(NOW(), MAX(v.fecha)) AS dias
                          FROM productos p
                          LEFT JOIN categorias c ON p.id_categoria = c.id
                          LEFT JOIN detalle_ventas dv ON p.id = dv.id_producto
                          LEFT JOIN ventas v ON dv.id_venta = v.id
                          GROUP BY p.id, p.nombre_producto, c.{$colCat}, p.stock
                          HAVING dias >= 30 OR dias IS NULL
                          ORDER BY dias DESC
                          LIMIT 6";
            $stmtSinMov = $this->db->prepare($sqlSinMov);
            $stmtSinMov->execute();
            $sin_movimiento = $stmtSinMov->fetchAll(PDO::FETCH_OBJ);
        } catch (Exception $e) {}

        // 6. Cobros por método de pago
        $metodos = [];
        try {
            $sqlMet = "SELECT 
                        COALESCE(mp.nombre, 'Otro') AS nombre,
                        COALESCE(SUM(pr.monto), 0) AS monto
                       FROM pagos_registro pr
                       INNER JOIN metodos_pago mp ON pr.id_metodo_pago = mp.id
                       WHERE DATE(pr.fecha_pago) >= ? AND DATE(pr.fecha_pago) <= ?
                       GROUP BY mp.id, mp.nombre
                       ORDER BY monto DESC";
            $stmtMet = $this->db->prepare($sqlMet);
            $stmtMet->execute([$desde, $hasta]);
            $metodos = $stmtMet->fetchAll(PDO::FETCH_OBJ);
        } catch (Exception $e) {}

        // 7. Compras por proveedor
        $proveedores = [];
        try {
            $sqlProv = "SELECT 
                        COALESCE(pr.razon_social, 'Proveedor') AS razon_social,
                        COUNT(c.id) AS compras,
                        COALESCE(SUM(c.total), 0) AS total
                        FROM compras c
                        INNER JOIN proveedores pr ON c.id_proveedor = pr.id
                        WHERE DATE(c.fecha) >= ? AND DATE(c.fecha) <= ?
                        GROUP BY pr.id, pr.razon_social
                        ORDER BY total DESC";
            $stmtProv = $this->db->prepare($sqlProv);
            $stmtProv->execute([$desde, $hasta]);
            $proveedores = $stmtProv->fetchAll(PDO::FETCH_OBJ);
        } catch (Exception $e) {}

        $estadisticas = [
            'resumen'        => $resumen,
            'dias'           => $dias,
            'categorias'     => $categorias,
            'top'            => $top,
            'sin_movimiento' => $sin_movimiento,
            'metodos'        => $metodos,
            'proveedores'    => $proveedores
        ];

        require_once 'views/seccion_graficos.php';
    }
}