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

                // Punto 3: Validar que el código no exista
                if (!empty($codigo) && $this->productoModelo->existeCodigo($codigo)) {
                    throw new Exception("El código '{$codigo}' ya está registrado en otro producto.");
                }

                // Punto 1: Procesar imagen
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

                $nuevoId = $this->productoModelo->guardarCompleto($datos, $atributos, $variantes);
                $this->log('Inventario', 'INSERT', "Creó producto: {$nombre} (ID: {$nuevoId})");

                // Punto 5: Aviso al guardar
                $_SESSION['aviso'] = 'Producto creado exitosamente.';
                header("Location: index.php?action=listado_productos");
                exit();

            } catch (Exception $e) {
                echo "<script>alert('" . addslashes($e->getMessage()) . "'); window.history.back();</script>";
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
            try {
                $id = (int)$_POST['id'];
                $pActual = $this->productoModelo->obtenerPorId($id);

                if (!$pActual) {
                    $_SESSION['aviso'] = 'El producto no existe.';
                    header("Location: index.php?action=listado_productos");
                    exit();
                }

                $codigo = trim($_POST['codigo'] ?? $pActual->codigo);

                // Punto 3: Validar que el código no pertenezca a otro producto
                if (!empty($codigo) && $this->productoModelo->existeCodigo($codigo, $id)) {
                    throw new Exception("El código '{$codigo}' ya está asignado a otro producto.");
                }

                // Punto 1: Procesar imagen y borrar la vieja
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

                // Punto 2: No se envían ni stock ni stock_online para no alterarlos desde edición
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

                $this->productoModelo->actualizarCompleto($id, $datos, $atributos, $variantes);
                $this->log('Inventario', 'UPDATE', "Actualizó producto y variantes: {$nombre} (ID: {$id})");

                // Punto 5: Aviso verde
                $_SESSION['aviso'] = 'Producto actualizado correctamente.';
                header("Location: index.php?action=listado_productos");
                exit();

            } catch (Exception $e) {
                echo "<script>alert('" . addslashes($e->getMessage()) . "'); window.history.back();</script>";
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

            if ($rawPadre === 'nueva_padre' && !empty($nuevaPadre)) {
                $stmtNP = $this->db->prepare("INSERT INTO categorias (nombre_categoria, id_padre) VALUES (?, NULL)");
                $stmtNP->execute([$nuevaPadre]);
                $idPadre = (int)$this->db->lastInsertId();
                $this->log('Inventario', 'INSERT', "Creó categoría principal padre: {$nuevaPadre}");
            } elseif (!empty($rawPadre) && is_numeric($rawPadre)) {
                $idPadre = (int)$rawPadre;
            }

            $atrIds = $_POST['atributo_id'] ?? [];
            $atrNombres = $_POST['atributo_nombre'] ?? [];
            $atrOpciones = $_POST['atributo_opciones'] ?? [];

            if (!empty($nombre)) {
                if ($id) {
                    if ($idPadre === $id) $idPadre = null;
                    $stmt = $this->db->prepare("UPDATE categorias SET nombre_categoria = ?, id_padre = ? WHERE id = ?");
                    $stmt->execute([$nombre, $idPadre, $id]);
                    $this->log('Inventario', 'UPDATE', "Editó categoría: {$nombre}");
                } else {
                    $stmt = $this->db->prepare("INSERT INTO categorias (nombre_categoria, id_padre) VALUES (?, ?)");
                    $stmt->execute([$nombre, $idPadre]);
                    $id = $this->db->lastInsertId();
                    $this->log('Inventario', 'INSERT', "Creó categoría: {$nombre}");
                }

                $idsMantener = array_filter(array_map('intval', $atrIds));
                if (!empty($idsMantener)) {
                    $in = implode(',', $idsMantener);
                    $this->db->prepare("DELETE FROM categoria_atributos WHERE id_categoria = ? AND id NOT IN ($in)")->execute([$id]);
                } else {
                    $this->db->prepare("DELETE FROM categoria_atributos WHERE id_categoria = ?")->execute([$id]);
                }

                $stmtUpd = $this->db->prepare("UPDATE categoria_atributos SET nombre = ?, opciones = ? WHERE id = ? AND id_categoria = ?");
                $stmtIns = $this->db->prepare("INSERT INTO categoria_atributos (id_categoria, nombre, opciones) VALUES (?, ?, ?)");

                for ($i = 0; $i < count($atrNombres); $i++) {
                    $nomAtr = trim($atrNombres[$i]);
                    $opcAtr = trim($atrOpciones[$i] ?? '');
                    $atrId = !empty($atrIds[$i]) ? (int)$atrIds[$i] : 0;

                    if (!empty($nomAtr)) {
                        if ($atrId > 0) {
                            $stmtUpd->execute([$nomAtr, $opcAtr, $atrId, $id]);
                        } else {
                            $stmtIns->execute([$id, $nomAtr, $opcAtr]);
                        }
                    }
                }
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
}