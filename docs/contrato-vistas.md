# Contrato entre backend y vistas

Este archivo dice qué recibe y qué envía cada pantalla. Si un nombre cambia, se cambia aquí y en la vista
en el mismo commit. Nadie renombra nada sin pasar por este archivo.

## Reglas

1. **Columnas de la base:** la vista las lee con el mismo nombre que tienen en la base (`numero_orden`,
   `fecha_hora`, `nombre_razon_social`…). No hace falta `AS`.
2. **Datos calculados** (los que salen de un JOIN, una suma o un nombre armado): el backend los entrega con
   el nombre que aparece en este archivo, usando `AS`. Ejemplo: `mp.nombre AS metodo`.
3. **Variable que recibe la vista:** el controlador crea la variable con el nombre de este archivo antes del
   `require` de la vista (`$pagos`, no `$pagosPendientes`).
4. **Ruta de la vista:** la que dice cada sección (por ejemplo `views/detalle_venta.php`, no `views/ventas/…`).
5. **Sin NULL:** los valores de relleno (`'raiz'`, `'SIN_USUARIO'`, `'2000-01-01 00:00:00'`, `''`) las vistas ya
   los tratan como vacío.
6. Antes de subir a main: traer lo del otro, correr la prueba y subir solo si todo pasa.

## Sesión (lo guarda el login)

| Clave | Valor |
|---|---|
| `$_SESSION['cedula']` | cédula del usuario |
| `$_SESSION['rol']` | código del rol en texto: `master`, `administrador`, `cajero`, `vendedor`, `almacen`, `cliente` |
| `$_SESSION['nombre']` | nombre para mostrar |
| `$_SESSION['permisos']` | lista de códigos de permiso (`['*']` para master) |

## Parámetros de las rutas (reemplazan `?id=`)

| Qué | Parámetro |
|---|---|
| producto | `?codigo=AB-001` |
| categoría | `?slug=dama-camisas` |
| venta | `?orden=ORD-000075` |
| pago | `?orden=ORD-000075&pago=1` |
| compra | `?rif=J-12345678-9&factura=001` |
| proveedor | `?rif=J-12345678-9` |
| cliente / usuario | `?cedula=V-12345678` |
| método de pago | `?metodo=pago_movil` |
| ajuste de un producto | `?codigo=AB-001` |

## Campos de formulario que envían las vistas

Mismo nombre que la columna: `codigo`, `codigo_producto`, `codigo_variante`, `slug`, `slug_padre`,
`slug_categoria`, `rif`, `rif_proveedor`, `numero_factura`, `numero_orden`, `numero_pago`, `cedula`,
`cedula_cliente`, `codigo_metodo`, `codigo_rol`, `codigo_permiso`, `codigo_atributo`.

---

## 1. Ventas

### Ventas · `views/ventas/ventas.php` · acción `ventas`
Recibe `$ventas`. Cada `$v`:
- columnas: `numero_orden`, `fecha`, `estado`, `origen_venta`, `total`
- calculados: `nombre_cliente` (cliente o usuario), `nombre_metodo` (método del primer pago)

### Detalle de venta · `views/detalle_venta.php` · acción `detalle_venta&orden=…`
Lee `$_GET['orden']`. Recibe `$venta`, `$detalles`, `$pagos`.
- `$venta` columnas: `numero_orden`, `fecha`, `estado`, `origen_venta`, `total`, `fecha_entrega`, `retirado_por`
- `$venta` calculados: `nombre` (cliente), `correo` (cliente), `vendedor` (nombre de quien registró)
- `$detalles` cada `$d`: columnas `cantidad`, `precio_unitario`, `subtotal`; calculados `producto_nombre`,
  `imagen`, `descuento` (0 si no hay)
- `$pagos` cada `$p`: columnas `moneda`, `monto`, `tasa_cambio`, `banco`, `referencia`, `observacion`,
  `fecha_pago`, `estado`; calculados `metodo` (nombre del método), `fecha_verificacion` y
  `verificado_por_nombre` (de la última fila de `pagos_auditoria`)

### Verificar pagos · `views/ventas/verificar_pagos.php` · acción `verificar_pagos`
Recibe `$pagos` con **todos** los pagos (la vista filtra por pestaña con `?estado=pendiente|aprobado|rechazado`).
Opcional `$conteo = ['pendiente' => n]`. Cada `$p`:
- columnas: `numero_orden`, `numero_pago`, `codigo_metodo`, `moneda`, `monto`, `tasa_cambio`, `banco`,
  `referencia`, `comprobante_img`, `observacion`, `fecha_pago`, `estado`
- calculados: `metodo`, `requiere_referencia`, `cliente`, `origen` (= `v.origen_venta`), `total_venta`,
  `pagado_aprobado` (suma aprobada de esa venta), `fecha_verificacion`, `verificado_por_nombre`,
  `registrado_por_nombre` (cajero)
Envía: `aprobar_pago`, `rechazar_pago`, `deshacer_pago` → `numero_orden`, `numero_pago`, `observacion`.
`editar_pago_verificacion` → `numero_orden`, `numero_pago`, `codigo_metodo`, `banco`, `referencia`,
`tasa_cambio`, `estado`, `observacion`, `productos`.

### Pagos por banco · `views/ventas/pagos_banco.php` · acción `pagos_banco`
Recibe `$pagosRecibidos` y `$metodos`. Cada `$p`:
- columnas: `numero_orden`, `moneda`, `monto`, `tasa_cambio`, `banco`, `referencia`, `observacion`, `fecha_pago`
- calculados: `metodo`, `requiere_referencia`, `origen_venta`, `cliente`, `cliente_cedula`,
  `cliente_telefono`, `titular` (= `p.titular_nombre`)
`$metodos` cada `$m`: `codigo_metodo`, `nombre`.

### Retiro de pedidos · `views/retiro_pedidos.php` · acción `retiro_pedidos`
Recibe `$ventas` (pagadas y retiradas; la vista separa por pestaña). Cada `$v`:
- columnas: `numero_orden`, `fecha`, `estado`, `total`, `fecha_entrega`, `retirado_por`
- calculados: `nombre_cliente`, `telefono`, `metodo_pago`, `nombre_metodo`, `pago`, `total_bs`
Envía al entregar: `numero_orden`, `retira`, `retirado_por`, `retirado_cedula`, `revisado`.

### Cobrado por método · `views/ventas/cobrado_metodo.php`
Recibe `$metodoId` (código del método), `$metodoNombre`, `$ventas`. Cada `$v`: `numero_orden`, `fecha`,
`total`; calculados `nombre_cliente`, `total_bs`.

### Caja · `views/ventas/punto_venta.php` · acción `punto_venta`
Recibe `$productos`, `$metodos`, `$clientes`.
- `$productos` cada `$p`: `codigo`, `nombre_producto`, `precio`, `stock`, `stock_minimo`, `imagen`,
  calculados `nombre_categoria`, `variantes` (lista de `producto_variantes`: `codigo_variante`, `talla`,
  `color`, `modelo`, `stock`)
- `$metodos` cada `$m`: `codigo_metodo`, `nombre`, `moneda`, `requiere_referencia`, `en_tienda`, `activo`
- `$clientes` cada `$c`: `cedula`, `nombre_razon_social`, `telefono`
Envía (`guardar_venta_tienda`): `cedula_cliente`, `nuevo_cliente[...]`, `total`, `vuelto`, `vuelto_moneda`;
por línea `codigo_producto[]`, `codigo_variante[]`, `cantidad[]`, `precio_unitario[]`, `descuento[]`;
por pago `codigo_metodo[]`, `monto[]`, `tasa_cambio[]`, `banco[]`, `referencia[]`, `titular_nombre[]`,
`titular_cedula[]`, `titular_telefono[]`.

### Inicio del panel · `views/dashboard.php`
Recibe `$kpis` (`ventas_hoy`, `ventas_hoy_n`, `ventas_ayer`, `pagos_pendientes`, `pagos_pendientes_monto`,
`por_retirar`, `stock_bajo`, `agotados`), `$pagosPendientes` (`cliente`, `metodo`, `moneda`, `monto`,
`referencia`), `$porRetirar` (`numero_orden`, `fecha`, `total`, `nombre_cliente`), `$stockBajo` (`codigo`,
`nombre_producto`, `imagen`, `stock`, `stock_minimo`), `$ventasSemana`.

## 2. Compras y proveedores

### Compras · `views/compras/compras.php`
Recibe `$compras`. Cada `$c`: columnas `rif_proveedor`, `numero_factura`, `fecha_compra`, `estado`, `total`,
`banco`, `comprado_por`; calculados `razon_social` (proveedor), `metodo_pago` (nombre), `total_productos`.

### Detalle de compra · `views/compras/detalle_compra.php` · `?rif=…&factura=…`
Recibe `$compra` (columnas de `compras` + `referencia_pago`; calculados `razon_social`, `telefono`,
`metodo_pago`, `usuario`) y `$detalles` (`codigo_producto`, `cantidad`, `costo_unitario`, `precio_venta`,
`subtotal`; calculados `codigo`, `nombre_producto`, `imagen`, `ganancia_total_linea`).

### Registrar compra · `views/compras/nueva_compra.php`
Recibe `$proveedores`, `$productos` (con `variantes`), `$metodos`, `$categorias`, `$empleados`
(calculado `nombre_completo`).

### Proveedores · `views/compras/proveedores.php`
Recibe `$proveedores`. Cada `$p`: `rif`, `razon_social`, `telefono`, `correo`, `direccion`, `estado`;
calculado `total_compras`.

## 3. Productos e inventario

### Productos · `views/listado_productos.php`
Recibe `$listaProductos` y `$categorias`. Cada `$p`: columnas de `productos`; calculados
`nombre_categoria`, `nombre_categoria_padre`, `resumen_variantes` (texto `talla:color:stock|…`).

### Formulario de producto · `views/formulario_producto.php`
Recibe `$categorias` (con `atributos`: `nombre`, `opciones`), y al editar `$p` (producto) y
`$variantesGuardadas` (`codigo_variante`, `talla`, `color`, `modelo`, `precio`, `stock`).

### Categorías · `views/inventario/categorias.php`
Recibe `$categorias`. Cada `$c`: `slug`, `slug_padre`, `nombre_categoria`; calculados `nombre_padre`,
`total_productos`, `total_unidades`, `atributos` (lista: `codigo_atributo`, `nombre`, `opciones`).

### Ajustes de stock · `views/inventario/ajustes.php`
Recibe `$productos` (con `variantes`) y `$ultimosAjustes` (kardex: `codigo_producto`, `tipo_movimiento`,
`cantidad`, `observacion`, `fecha_hora`; calculados `nombre_producto`, `usuario`).

### Movimientos · `views/inventario/kardex.php`
Recibe `$movimientos`. Cada `$m`: columnas de `kardex_inventario` (`fecha_hora`, `stock_resultante`…);
calculados `nombre_producto`, `imagen`, `stock_minimo`, `usuario`, `rif_proveedor` (si es compra).

## 4. Métodos de pago
`views/metodos_pago.php` recibe `$metodos`: `codigo_metodo`, `nombre`, `moneda`, `requiere_referencia`,
`en_tienda`, `en_online`, `activo`.

## 5. Usuarios y Master

- Clientes · `views/admin_clientes.php`: `$clientes` con `cedula`, `nombre`, `apellido`, `telefono`, `correo`,
  `direccion`, `estado`; calculados `compras`, `total_compras`.
- Administradores · `views/gestionar_admins.php`: `$admins` (`cedula`, `nombre`, `apellido`, `correo`,
  `codigo_rol`, `estado`, `ultimo_acceso`) y `$roles` (`codigo_rol`, `nombre_rol`).
- Roles y permisos · `views/master/roles_permisos.php`: `$roles` (+ calculado `total_usuarios`),
  `$permisos` (`codigo_permiso`, `nombre`, `modulo`), `$rolPermisos` (`[codigo_rol => [codigo_permiso…]]`).
- Auditoría · `views/master/auditoria.php`: `$registros` (columnas de `auditoria_logs`; calculado `usuario`),
  `$usuarios` (`cedula`, `nombre`, `apellido`).
- Inicio Master · `views/dashboard_master.php`: `$resumen`, `$actividad` (+ calculado `usuario`), `$tasa`.

## 6. Tienda online

- Catálogo · `views/catalogo.php`: `$listaProductos`, `$categorias`, `$tallas`.
- Producto · `views/producto.php`: `$producto` (+ `variantes`), `$atributos` (`nombre`, `opciones`, `valor`),
  `$relacionados`.
- Mis pedidos · `views/mis_pedidos.php`: `$pedidos` (`numero_orden`, `fecha`, `estado`, `total`; calculado `items`).
