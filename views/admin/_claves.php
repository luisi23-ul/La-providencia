<?php
if (defined('CLAVES_NOMBRES')) return;

const CLAVES_NOMBRES = [
    'producto' => 'codigo', 'variante' => 'codigo_variante', 'categoria' => 'slug', 'atributo' => 'codigo_atributo',
    'venta' => 'numero_orden', 'pago' => ['numero_orden', 'numero_pago'], 'compra' => ['rif_proveedor', 'numero_factura'],
    'proveedor' => 'rif', 'cliente' => 'cedula', 'usuario' => 'cedula', 'rol' => 'codigo_rol',
    'permiso' => 'codigo_permiso', 'metodo' => 'codigo_metodo',
    'producto.categoria' => 'slug_categoria', 'categoria.padre' => 'slug_padre',
    'variante.producto' => 'codigo_producto', 'usuario.rol' => 'codigo_rol',
    'pago.venta' => 'numero_orden', 'pago.metodo' => 'codigo_metodo', 'kardex.referencia' => 'referencia', 'proveedor.rif' => 'rif',
    'kardex.fecha' => 'fecha_hora', 'auditoria.fecha' => 'fecha_hora', 'auditoria.descripcion' => 'detalle_cambio', 'auditoria.ip' => 'ip_terminal', 'pago.estado' => 'estado',

    'url.producto' => 'codigo', 'url.producto.ajuste' => 'codigo', 'url.categoria' => 'slug',
    'url.venta' => 'orden', 'url.pago' => ['orden', 'pago'], 'url.compra' => ['rif', 'factura'],
    'url.cliente' => 'cedula', 'url.usuario' => 'cedula', 'url.metodo' => 'metodo',

    'campo.producto' => 'codigo', 'campo.producto.ref' => 'codigo_producto', 'campo.variante' => 'codigo_variante', 'campo.variante.ref' => 'codigo_variante',
    'campo.categoria' => 'slug', 'campo.categoria.ref' => 'slug_categoria', 'campo.categoria.padre' => 'slug_padre',
    'campo.proveedor' => 'rif', 'campo.proveedor.ref' => 'rif_proveedor', 'campo.proveedor.rif' => 'rif',
    'campo.metodo' => 'codigo_metodo', 'campo.metodo.ref' => 'codigo_metodo',
    'campo.cliente' => 'cedula', 'campo.cliente.ref' => 'cedula_cliente',
    'campo.usuario' => 'cedula', 'campo.rol.ref' => 'codigo_rol', 'campo.rol' => 'roles',
    'campo.venta' => 'numero_orden', 'campo.venta.ref' => 'numero_orden',
    'campo.pago' => ['numero_orden', 'numero_pago'], 'campo.pago.ref' => ['numero_orden', 'numero_pago'],
    'campo.compra' => ['rif_proveedor', 'numero_factura'], 'campo.atributo' => 'codigo_atributo',

    'rol.master' => 'master', 'rol.admin' => 'administrador', 'rol.cliente' => 'cliente', 'rol.vendedor' => 'vendedor', 'rol.cajero' => 'cajero',
    'sesion.usuario' => 'cedula', 'sesion.rol' => 'rol',
    'vacio' => ['raiz', 'SIN_USUARIO', '2000-01-01 00:00:00', '2000-01-01'],
];

function nombre_clave(string $k)
{
    if (!array_key_exists($k, CLAVES_NOMBRES)) throw new LogicException("Falta '$k' en _claves.php");
    return CLAVES_NOMBRES[$k];
}

function leer_columna($fila, string $col)
{
    if (is_array($fila)) return $fila[$col] ?? null;
    return $fila->$col ?? null;
}

function clave(string $tipo, $fila): string
{
    $v = [];
    foreach ((array) nombre_clave($tipo) as $c) $v[] = (string) leer_columna($fila, $c);
    return implode('|', $v);
}

function url_clave(string $tipo, $fila, string $uso = ''): string
{
    $k = $uso !== '' && isset(CLAVES_NOMBRES["url.$tipo.$uso"]) ? "url.$tipo.$uso" : "url.$tipo";
    $params = (array) nombre_clave($k);
    $cols = (array) nombre_clave($tipo);
    $q = [];
    foreach ($params as $i => $p) $q[$p] = (string) leer_columna($fila, $cols[$i] ?? $cols[0]);
    return htmlspecialchars(http_build_query($q), ENT_QUOTES, 'UTF-8');
}

function url_valor(string $tipo, $valor): string
{
    $params = (array) nombre_clave("url.$tipo");
    $valores = is_array($valor) ? array_values($valor) : explode('|', (string) $valor);
    $q = [];
    foreach ($params as $i => $p) $q[$p] = $valores[$i] ?? '';
    return htmlspecialchars(http_build_query($q), ENT_QUOTES, 'UTF-8');
}

function leer_url(string $tipo, string $uso = ''): string
{
    $k = $uso !== '' && isset(CLAVES_NOMBRES["url.$tipo.$uso"]) ? "url.$tipo.$uso" : "url.$tipo";
    $v = [];
    foreach ((array) nombre_clave($k) as $p) $v[] = trim((string) ($_GET[$p] ?? ''));
    return implode('|', array_filter($v, 'strlen')) === '' ? '' : implode('|', $v);
}

function campo(string $k): string
{
    $n = (array) nombre_clave('campo.' . $k);
    return $n[0];
}

function ocultos(string $k, $fila = null, string $prefijoId = ''): string
{
    $nombres = (array) nombre_clave('campo.' . $k);
    $base = explode('.', $k)[0];
    $cols = (array) nombre_clave($base);
    $html = '';
    foreach ($nombres as $i => $n) {
        $valor = $fila === null ? '' : (string) leer_columna($fila, $cols[$i] ?? $cols[0]);
        $id = $prefijoId !== '' ? ' id="' . $prefijoId . (count($nombres) > 1 ? '-' . $i : '') . '"' : '';
        $html .= '<input type="hidden" name="' . $n . '"' . $id . ' value="' . htmlspecialchars($valor, ENT_QUOTES, 'UTF-8') . '">';
    }
    return $html;
}

function registro_clave(string $k, $fila): array
{
    $nombres = (array) nombre_clave('campo.' . $k);
    $base = explode('.', $k)[0];
    $cols = (array) nombre_clave($base);
    $r = [];
    foreach ($nombres as $i => $n) $r[$n] = (string) leer_columna($fila, $cols[$i] ?? $cols[0]);
    return $r;
}

function ref(string $k, $fila): string
{
    $v = (string) leer_columna($fila, nombre_clave($k));
    return es_vacio($v) ? '' : $v;
}

function es_vacio($valor): bool
{
    return $valor === null || $valor === '' || in_array((string) $valor, CLAVES_NOMBRES['vacio'], true);
}

function categoria_de($producto): string
{
    if (es_vacio(leer_columna($producto, nombre_clave('producto.categoria')))) return '';
    return (string) (leer_columna($producto, 'nombre_categoria') ?? '');
}

function sin_relleno(array $filas, string $tipo): array
{
    return array_values(array_filter($filas, fn($f) => !es_vacio(clave($tipo, $f))));
}

function sql_sin_borrar(string $alias = ''): string
{
    $col = ($alias !== '' ? "$alias." : '') . 'deleted_at';
    return "($col IS NULL OR $col = '2000-01-01 00:00:00')";
}

function sql_principal(string $alias = ''): string
{
    $a = $alias !== '' ? "$alias." : '';
    $padre = $a . nombre_clave('categoria.padre');
    return "($padre IS NULL OR $padre = 'raiz') AND {$a}" . nombre_clave('categoria') . " <> 'raiz'";
}

function numero_doc(string $tipo, $fila): string
{
    if ($tipo === 'compra') return 'Fact. ' . leer_columna($fila, 'numero_factura');
    return clave($tipo, $fila);
}

function numero_ref(string $tipo, $valor): string
{
    return (string) $valor;
}

function es_rol(string $nombre, $valor = null): bool
{
    $valor ??= $_SESSION[nombre_clave('sesion.rol')] ?? '';
    return (string) $valor === (string) nombre_clave('rol.' . $nombre);
}

function rol_valor(string $nombre): string
{
    return (string) nombre_clave('rol.' . $nombre);
}

function usuario_sesion(): string
{
    return (string) ($_SESSION[nombre_clave('sesion.usuario')] ?? '');
}

function claves_js(): string
{
    $salida = ['campo' => [], 'url' => []];
    foreach (CLAVES_NOMBRES as $k => $v) {
        if (str_starts_with($k, 'campo.')) $salida['campo'][substr($k, 6)] = $v;
        if (str_starts_with($k, 'url.')) $salida['url'][substr($k, 4)] = $v;
    }
    return '<script>window.CLAVES = ' . json_encode($salida, JSON_UNESCAPED_UNICODE) . ';'
        . 'window.urlClave = function (tipo, valor) { var p = [].concat(CLAVES.url[tipo]), v = String(valor).split("|");'
        . ' return p.map(function (n, i) { return encodeURIComponent(n) + "=" + encodeURIComponent(v[i] || ""); }).join("&"); };'
        . 'window.campoClave = function (tipo) { return [].concat(CLAVES.campo[tipo])[0]; };'
        . 'window.ponerClave = function (form, tipo, valor) { var v = String(valor).split("|");'
        . ' [].concat(CLAVES.campo[tipo]).forEach(function (n, i) { var el = form.querySelector("[name=\\"" + n + "\\"]"); if (el) el.value = v[i] || ""; }); };</script>';
}
