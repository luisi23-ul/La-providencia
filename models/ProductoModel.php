<?php
require_once 'config/db.php';

class ProductoModel {
    private $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    public function getDb() {
        return $this->db;
    }

    public function existeCodigo($codigo, $codigoIgnorar = '') {
        if (empty($codigo)) return false;
        $sql = "SELECT codigo FROM productos WHERE codigo = ? AND codigo != ? AND (deleted_at IS NULL OR deleted_at = '2000-01-01 00:00:00') LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$codigo, (string)$codigoIgnorar]);
        return (bool)$stmt->fetch();
    }

   public function obtenerTodos() {
        $sql = "SELECT p.*, 
                       p.codigo AS id,
                       COALESCE(c.nombre_categoria, 'General') AS nombre_categoria, 
                       COALESCE(c.nombre_categoria, 'General') AS nombre_categoria_padre,
                       (
                           SELECT GROUP_CONCAT(
                               CONCAT_WS(':', 
                                   IFNULL(pv.talla, ''), 
                                   IFNULL(pv.color, ''), 
                                   IFNULL(pv.modelo, ''), 
                                   pv.stock
                               ) 
                               SEPARATOR '|'
                           )
                           FROM producto_variantes pv 
                           WHERE pv.codigo_producto = p.codigo
                       ) AS resumen_variantes
                FROM productos p
                LEFT JOIN categorias c ON p.slug_categoria = c.slug
                WHERE (p.deleted_at IS NULL OR p.deleted_at = '2000-01-01 00:00:00' OR p.deleted_at = '0000-00-00 00:00:00')
                ORDER BY p.created_at DESC, p.codigo DESC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function obtenerPorId($codigo) {
        $stmt = $this->db->prepare("
            SELECT p.*, 
                   p.codigo AS id,
                   COALESCE(c.nombre_categoria, 'General') AS nombre_categoria, 
                   COALESCE(c.nombre_categoria, 'General') AS nombre_categoria_padre
            FROM productos p
            LEFT JOIN categorias c ON p.slug_categoria = c.slug
            WHERE p.codigo = ? AND (p.deleted_at IS NULL OR p.deleted_at = '2000-01-01 00:00:00')
            LIMIT 1
        ");
        $stmt->execute([(string)$codigo]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function buscarProductos($nombre = '', $slug_cat = '') {
        $where = ["(p.deleted_at IS NULL OR p.deleted_at = '2000-01-01 00:00:00')"];
        $params = [];

        if (!empty($nombre)) {
            $where[] = "(p.nombre_producto LIKE ? OR p.codigo LIKE ?)";
            $params[] = "%$nombre%";
            $params[] = "%$nombre%";
        }

        if (!empty($slug_cat)) {
            $where[] = "p.slug_categoria = ?";
            $params[] = $slug_cat;
        }

        $sql = "SELECT p.*, 
                       p.codigo AS id,
                       COALESCE(c.nombre_categoria, 'General') AS nombre_categoria, 
                       COALESCE(c.nombre_categoria, 'General') AS nombre_categoria_padre
                FROM productos p
                LEFT JOIN categorias c ON p.slug_categoria = c.slug
                WHERE " . implode(' AND ', $where) . "
                ORDER BY p.nombre_producto ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function obtenerCategorias() {
        $stmt = $this->db->query("SELECT slug, slug AS id, nombre_categoria FROM categorias ORDER BY nombre_categoria ASC");
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function obtenerAtributosProducto($codigoProducto) {
        try {
            $stmt = $this->db->prepare("
                SELECT pa.*, ca.nombre
                FROM producto_atributos pa
                INNER JOIN categoria_atributos ca ON pa.id_atributo = ca.id
                WHERE pa.codigo_producto = ?
            ");
            $stmt->execute([(string)$codigoProducto]);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (Exception $e) {
            return [];
        }
    }

    public function obtenerFiltrados($busqueda = '', $slugCategoria = '') {
        $where = ["(p.deleted_at IS NULL OR p.deleted_at = '2000-01-01 00:00:00' OR p.deleted_at = '0000-00-00 00:00:00')"];
        $params = [];

        if (!empty($busqueda)) {
            $where[] = "(p.nombre_producto LIKE ? OR p.codigo LIKE ? OR p.descripcion LIKE ?)";
            $term = "%$busqueda%";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        if (!empty($slugCategoria) && $slugCategoria !== 'todas') {
            // Coincidencia exacta o por subcategoría/prefijo (ej: 'abrigos' coincide con 'dama-abrigos')
            $where[] = "(p.slug_categoria = ? OR p.slug_categoria LIKE ?)";
            $params[] = $slugCategoria;
            $params[] = "%$slugCategoria%";
        }

        $sql = "SELECT p.*, 
                       p.codigo AS id,
                       COALESCE(c.nombre_categoria, 'General') AS nombre_categoria, 
                       COALESCE(c.nombre_categoria, 'General') AS nombre_categoria_padre,
                       (
                           SELECT GROUP_CONCAT(
                               CONCAT_WS(':', 
                                   IFNULL(pv.talla, ''), 
                                   IFNULL(pv.color, ''), 
                                   IFNULL(pv.modelo, ''), 
                                   pv.stock
                               ) 
                               SEPARATOR '|'
                           )
                           FROM producto_variantes pv 
                           WHERE pv.codigo_producto = p.codigo
                       ) AS resumen_variantes
                FROM productos p
                LEFT JOIN categorias c ON p.slug_categoria = c.slug
                WHERE " . implode(' AND ', $where) . "
                ORDER BY p.created_at DESC, p.codigo DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function obtenerVariantesProducto($codigoProducto) {
        $stmt = $this->db->prepare("
            SELECT pv.codigo_variante, 
                   pv.codigo_variante AS id, 
                   pv.codigo_producto, 
                   pv.talla, 
                   pv.color, 
                   pv.modelo, 
                   pv.stock, 
                   pv.precio 
            FROM producto_variantes pv 
            WHERE pv.codigo_producto = ? 
            ORDER BY pv.codigo_variante ASC
        ");
        $stmt->execute([(string)$codigoProducto]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function guardarCompleto($datos, $atributos = [], $variantes = []) {
        $codigo = trim((string)($datos['codigo'] ?? ''));
        if (empty($codigo)) {
            $stmtMax = $this->db->query("SELECT MAX(CAST(SUBSTRING(codigo, 5) AS UNSIGNED)) FROM productos WHERE codigo LIKE 'PROD-%'");
            $maxNum = (int)$stmtMax->fetchColumn();
            $codigo = 'PROD-' . str_pad($maxNum + 1, 5, '0', STR_PAD_LEFT);
        }

        $sql = "INSERT INTO productos (
                    codigo, slug_categoria, nombre_producto, descripcion, 
                    precio, costo_compra, stock, stock_minimo, imagen, estado, deleted_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '2000-01-01 00:00:00')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $codigo,
            $datos['slug_categoria'] ?? $datos['id_categoria'] ?? 'general',
            $datos['nombre_producto'] ?? '',
            $datos['descripcion'] ?? '',
            (float)($datos['precio'] ?? 0),
            (float)($datos['costo_compra'] ?? 0),
            (int)($datos['stock'] ?? 0),
            (int)($datos['stock_minimo'] ?? 5),
            $datos['imagen'] ?? null,
            (int)($datos['estado'] ?? 1)
        ]);

        if (!empty($variantes)) {
            $this->sincronizarVariantes($codigo, $variantes);
        }

        return $codigo;
    }

    public function actualizarCompleto($codigo, $datos, $atributos = [], $variantes = []) {
        $sql = "UPDATE productos SET 
                    slug_categoria = ?, 
                    nombre_producto = ?, 
                    descripcion = ?, 
                    precio = ?, 
                    costo_compra = ?, 
                    stock_minimo = ?, 
                    estado = ?, 
                    imagen = COALESCE(?, imagen)
                WHERE codigo = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $datos['slug_categoria'] ?? $datos['id_categoria'] ?? 'general',
            $datos['nombre_producto'],
            $datos['descripcion'],
            (float)$datos['precio'],
            (float)$datos['costo_compra'],
            (int)$datos['stock_minimo'],
            (int)$datos['estado'],
            $datos['imagen'],
            (string)$codigo
        ]);

        if (!empty($variantes)) {
            $this->sincronizarVariantes($codigo, (array)$variantes);
        }

        return true;
    }

    public function sincronizarVariantes($codigoProducto, array $variantes) {
        $codigoProducto = (string)$codigoProducto;

        $stmtPrev = $this->db->prepare("SELECT codigo_variante, stock FROM producto_variantes WHERE codigo_producto = ?");
        $stmtPrev->execute([$codigoProducto]);
        $previas = $stmtPrev->fetchAll(PDO::FETCH_KEY_PAIR);

        $idsMantener = [];
        $totalStock = 0;

        $stmtIns = $this->db->prepare("
            INSERT INTO producto_variantes (codigo_variante, codigo_producto, talla, color, modelo, stock, precio)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmtUpd = $this->db->prepare("
            UPDATE producto_variantes 
            SET talla = ?, color = ?, modelo = ?, stock = ?, precio = ?
            WHERE codigo_variante = ? AND codigo_producto = ?
        ");

        $vIdx = 1;
        foreach ($variantes as $v) {
            $talla  = trim($v['talla'] ?? '');
            $color  = trim($v['color'] ?? '');
            $modelo = trim($v['modelo'] ?? '');
            $stk    = max(0, (int)($v['stock'] ?? 0));
            $varCod = trim((string)($v['codigo_variante'] ?? $v['id'] ?? ''));
            $precio = isset($v['precio']) && $v['precio'] !== '' ? (float)$v['precio'] : 0.00;

            if ($talla === '' && $color === '' && $modelo === '') {
                continue;
            }

            if (!empty($varCod) && isset($previas[$varCod])) {
                $stmtUpd->execute([$talla, $color, $modelo, $stk, $precio, $varCod, $codigoProducto]);
                $idsMantener[] = $varCod;
            } else {
                $nuevoCodVar = $codigoProducto . '-V' . str_pad($vIdx, 2, '0', STR_PAD_LEFT);
                $stmtIns->execute([$nuevoCodVar, $codigoProducto, $talla, $color, $modelo, $stk, $precio]);
                $idsMantener[] = $nuevoCodVar;
                $vIdx++;
            }

            $totalStock += $stk;
        }

        if (!empty($idsMantener)) {
            $placeholders = implode(',', array_fill(0, count($idsMantener), '?'));
            $stmtDel = $this->db->prepare("DELETE FROM producto_variantes WHERE codigo_producto = ? AND codigo_variante NOT IN ($placeholders)");
            $stmtDel->execute(array_merge([$codigoProducto], $idsMantener));

            $this->db->prepare("UPDATE productos SET stock = ? WHERE codigo = ?")->execute([$totalStock, $codigoProducto]);
        }
    }

    public function borrarProducto($codigo) {
        $stmt = $this->db->prepare("UPDATE productos SET deleted_at = NOW(), estado = 0 WHERE codigo = ?");
        return $stmt->execute([(string)$codigo]);
    }

    public function obtenerEstadisticasGenerales() {
        $totalProductos = $this->db->query("SELECT COUNT(*) FROM productos WHERE (deleted_at IS NULL OR deleted_at = '2000-01-01 00:00:00')")->fetchColumn();
        $totalStock = $this->db->query("SELECT COALESCE(SUM(stock), 0) FROM productos WHERE (deleted_at IS NULL OR deleted_at = '2000-01-01 00:00:00')")->fetchColumn();
        return [
            'total_productos' => (int)$totalProductos,
            'total_stock'     => (int)$totalStock
        ];
    }
}