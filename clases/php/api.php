<?php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../daos/PedidoDAO.php';
require_once __DIR__ . '/../daos/OrdenTrabajoDAO.php';
require_once __DIR__ . '/../daos/ProduccionDAO.php';
require_once __DIR__ . '/../daos/MaquinaDAO.php';
require_once __DIR__ . '/../daos/MantenimientoDAO.php';
require_once __DIR__ . '/../daos/PreferenciasUsuarioDAO.php';
require_once __DIR__ . '/../daos/DepositoDAO.php';
require_once __DIR__ . '/../daos/ComprasDAO.php';
require_once __DIR__ . '/../daos/CalidadDAO.php';

function api_db() {
    return Database::getConnection();
}

function api_json($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function api_input($key, $default = null) {
    return isset($_POST[$key]) ? trim((string)$_POST[$key]) : $default;
}

function api_int($key, $default = 0) {
    return isset($_POST[$key]) ? (int)$_POST[$key] : $default;
}

function api_required($key) {
    $value = api_input($key);
    if ($value === null || $value === '') {
        api_json(['success' => false, 'message' => "Falta el campo $key"], 422);
    }
    return $value;
}

function api_date($value) {
    return $value === '' || $value === null ? null : $value;
}

$action = $_GET['action'] ?? null;
$pedidoDAO = new PedidoDAO();
$ordenTrabajoDAO = new OrdenTrabajoDAO();
$produccionDAO = new ProduccionDAO();
$maquinaDAO = new MaquinaDAO();
$mantenimientoDAO = new MantenimientoDAO();
$preferenciasDAO = new PreferenciasUsuarioDAO();
$depositoDAO = new DepositoDAO();
$comprasDAO = new ComprasDAO();
$calidadDAO = new CalidadDAO();

if ($action === 'health') {
    api_json(['success' => true, 'app' => 'MetalGest']);
}

if ($action === 'preferences_get') {
    $idUsuario = api_int('id_usuario');
    if ($idUsuario <= 0) {
        api_json(['success' => false, 'message' => 'Usuario invÃ¡lido'], 422);
    }
    $preferencias = $preferenciasDAO->obtener($idUsuario);
    api_json([
        'success' => true,
        'modo_oscuro' => (int)$preferencias['modo_oscuro'],
        'tamano_texto' => (int)$preferencias['tamano_texto']
    ]);
}

if ($action === 'preferences_save') {
    try {
        $idUsuario = api_int('id_usuario');
        $tamano = api_int('tamano_texto', 12);
        if ($idUsuario <= 0 || $tamano < 10 || $tamano > 20) {
            api_json(['success' => false, 'message' => 'Preferencias invÃ¡lidas'], 422);
        }
        $preferenciasDAO->guardar([
            'id_usuario' => $idUsuario,
            'modo_oscuro' => api_int('modo_oscuro') === 1 ? 1 : 0,
            'tamano_texto' => $tamano
        ]);
        api_json(['success' => true]);
    } catch (Throwable $e) {
        api_json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

if ($action === 'login') {
    $nombreLogin = api_required('nombre');
    $contrasenaLogin = api_required('contrasena');
    $usuariosBase = [
        ['Gerencia', 'San Jorge', 'gerencia', 'Gerencia'],
        ['Administracion', 'San Jorge', 'admin', 'Administracion'],
        ['Produccion', 'San Jorge', 'produccion', 'Produccion'],
        ['Mantenimiento', 'San Jorge', 'mantenimiento', 'Mantenimiento'],
        ['Deposito', 'San Jorge', 'deposito', 'Deposito'],
        ['Compras', 'San Jorge', 'compras', 'Compras'],
        ['Calidad', 'San Jorge', 'calidad', 'Calidad']
    ];
    $crearUsuarioBase = api_db()->prepare(
        'INSERT INTO usuario (nombre, apellido, usuario, contrasena, id_rol)
         SELECT :nombre, :apellido, :usuario, "1234", id_rol
         FROM rol WHERE nombre = :rol
         ON DUPLICATE KEY UPDATE
            nombre = VALUES(nombre),
            apellido = VALUES(apellido),
            contrasena = VALUES(contrasena),
            id_rol = VALUES(id_rol)'
    );
    foreach ($usuariosBase as $usuarioBase) {
        $crearUsuarioBase->execute([
            ':nombre' => $usuarioBase[0],
            ':apellido' => $usuarioBase[1],
            ':usuario' => $usuarioBase[2],
            ':rol' => $usuarioBase[3]
        ]);
    }
    $stmt = api_db()->prepare(
        'SELECT u.id_usuario, u.nombre, u.apellido, u.usuario, r.nombre AS rol
         FROM usuario u
         INNER JOIN rol r ON r.id_rol = u.id_rol
         WHERE (u.usuario = :usuario OR u.nombre = :nombre OR CONCAT(u.nombre, " ", u.apellido) = :nombre_completo)
             AND u.contrasena = :contrasena
         LIMIT 1'
    );
    $stmt->execute([
        ':usuario' => $nombreLogin,
        ':nombre' => $nombreLogin,
        ':nombre_completo' => $nombreLogin,
        ':contrasena' => $contrasenaLogin
    ]);
    $user = $stmt->fetch();
    if (!$user) {
        api_json(['success' => false, 'message' => 'Credenciales incorrectas'], 401);
    }
    api_json(['success' => true, 'usuario' => $user]);
}

if ($action === 'clientes_list') {
    api_json(api_db()->query('SELECT * FROM cliente ORDER BY razon_social')->fetchAll());
}

if ($action === 'pedidos_list') {
    api_json($pedidoDAO->listar());
}

if ($action === 'pedidos_create') {
    try {
        $cantidad = api_int('cantidad', 1);
        if ($cantidad <= 0) {
            api_json(['success' => false, 'message' => 'La cantidad debe ser mayor que cero'], 422);
        }
        $idPedido = $pedidoDAO->crear([
            'id_cliente' => api_int('id_cliente'),
            'razon_social' => api_required('razon_social'),
            'cuit' => api_input('cuit', ''),
            'telefono' => api_input('telefono', ''),
            'email' => api_input('email', ''),
            'direccion' => api_input('direccion', ''),
            'descripcion' => api_required('descripcion'),
            'cantidad' => $cantidad,
            'material' => api_required('material'),
            'fecha_entrega' => api_date(api_input('fecha_entrega'))
        ]);
        api_json(['success' => true, 'id_pedido' => $idPedido]);
    } catch (Throwable $e) {
        api_json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

if ($action === 'ordenes_list') {
    api_json($ordenTrabajoDAO->listar());
}

if ($action === 'ordenes_create') {
    try {
        $idPedido = api_int('id_pedido');
        if ($idPedido <= 0) {
            api_json(['success' => false, 'message' => 'El pedido debe ser vÃ¡lido'], 422);
        }
        $idOrden = $ordenTrabajoDAO->crear([
            'id_pedido' => $idPedido,
            'fecha_inicio' => api_date(api_input('fecha_inicio', date('Y-m-d'))),
            'fecha_prevista' => api_date(api_input('fecha_prevista')),
            'prioridad' => api_input('prioridad', 'Media'),
            'observaciones' => api_input('observaciones', ''),
            'id_usuario' => api_int('id_usuario', 1)
        ]);
        api_json(['success' => true, 'id_orden' => $idOrden]);
    } catch (Throwable $e) {
        api_json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

if ($action === 'orden_update_status') {
    try {
        $idOrden = api_int('id_orden');
        if ($idOrden <= 0) {
            api_json(['success' => false, 'message' => 'La orden debe ser vÃ¡lida'], 422);
        }
        $ordenTrabajoDAO->actualizarEstado([
            'id_orden' => $idOrden,
            'estado' => api_required('estado'),
            'observaciones' => api_input('observaciones', '')
        ]);
        api_json(['success' => true]);
    } catch (Throwable $e) {
        api_json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

if ($action === 'produccion_list') {
    api_json($produccionDAO->listar());
}

if ($action === 'produccion_register') {
    $cantidad = api_int('cantidad_producida');
    $avance = api_int('avance');
    if (api_int('id_orden') <= 0 || $cantidad < 0 || $avance < 0 || $avance > 100) {
        api_json(['success' => false, 'message' => 'Datos de producciÃ³n invÃ¡lidos'], 422);
    }
    try {
        $idProduccion = $produccionDAO->registrar([
            'id_orden' => api_int('id_orden'),
            'cantidad_producida' => $cantidad,
            'avance' => $avance,
            'observaciones' => api_input('observaciones', '')
        ]);
        api_json(['success' => true, 'id_produccion' => $idProduccion]);
    } catch (Throwable $e) {
        api_json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

if ($action === 'maquinas_list') {
    api_json($maquinaDAO->listar());
}

if ($action === 'mantenimiento_report') {
    try {
        $idMaquina = api_int('id_maquina');
        if ($idMaquina <= 0) {
            api_json(['success' => false, 'message' => 'La mÃ¡quina debe ser vÃ¡lida'], 422);
        }
        $idMantenimiento = $maquinaDAO->reportarFalla([
            'id_maquina' => $idMaquina,
            'id_usuario' => api_int('id_usuario', 3),
            'tipo' => api_input('tipo', 'Correctivo'),
            'problema' => api_required('problema')
        ]);
        api_json(['success' => true, 'id_mantenimiento' => $idMantenimiento]);
    } catch (Throwable $e) {
        api_json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

if ($action === 'mantenimientos_list') {
    api_json($mantenimientoDAO->listar());
}

if ($action === 'mantenimiento_preventivo_create') {
    try {
        $idMaquina = api_int('id_maquina');
        if ($idMaquina <= 0) {
            api_json(['success' => false, 'message' => 'La mÃ¡quina debe ser vÃ¡lida'], 422);
        }
        $idMantenimiento = $mantenimientoDAO->crearPreventivo([
            'id_maquina' => $idMaquina,
            'id_usuario' => api_int('id_usuario', 1),
            'fecha' => api_input('fecha', date('Y-m-d')),
            'problema' => api_required('problema')
        ]);
        api_json(['success' => true, 'id_mantenimiento' => $idMantenimiento]);
    } catch (Throwable $e) {
        api_json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

if ($action === 'mantenimiento_update') {
    try {
        $idMantenimiento = api_int('id_mantenimiento');
        if ($idMantenimiento <= 0) {
            api_json(['success' => false, 'message' => 'El mantenimiento debe ser vÃ¡lido'], 422);
        }
        $estado = api_required('estado');
        if (!in_array($estado, ['Pendiente', 'En proceso', 'Resuelta'], true)) {
            api_json(['success' => false, 'message' => 'Estado de mantenimiento invÃ¡lido'], 422);
        }
        $mantenimientoDAO->actualizar([
            'id_mantenimiento' => $idMantenimiento,
            'reparacion' => api_required('reparacion'),
            'repuestos' => api_input('repuestos', ''),
            'estado' => $estado
        ]);
        api_json(['success' => true]);
    } catch (Throwable $e) {
        api_json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

if ($action === 'materiales_list') {
    api_json($depositoDAO->listarMateriales());
}

if ($action === 'deposito_movimientos_list') {
    api_json($depositoDAO->listarMovimientos());
}

if ($action === 'deposito_movimiento_create') {
    try {
        $idMaterial = api_int('id_material');
        $cantidad = (float)api_input('cantidad', '0');
        $tipo = api_required('tipo');
        if ($idMaterial <= 0 || $cantidad <= 0 || !in_array($tipo, ['Ingreso', 'Salida'], true)) {
            api_json(['success' => false, 'message' => 'Movimiento de stock invalido'], 422);
        }
        $idMovimiento = $depositoDAO->registrarMovimiento([
            'id_material' => $idMaterial,
            'tipo' => $tipo,
            'cantidad' => $cantidad,
            'motivo' => api_input('motivo', ''),
            'id_usuario' => api_int('id_usuario', 1)
        ]);
        api_json(['success' => true, 'id_movimiento' => $idMovimiento]);
    } catch (Throwable $e) {
        api_json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

if ($action === 'proveedores_list') {
    api_json($comprasDAO->listarProveedores());
}

if ($action === 'proveedores_create') {
    try {
        $idProveedor = $comprasDAO->crearProveedor([
            'razon_social' => api_required('razon_social'),
            'cuit' => api_input('cuit', ''),
            'telefono' => api_input('telefono', ''),
            'email' => api_input('email', '')
        ]);
        api_json(['success' => true, 'id_proveedor' => $idProveedor]);
    } catch (Throwable $e) {
        api_json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

if ($action === 'compras_list') {
    api_json($comprasDAO->listarCompras());
}

if ($action === 'compras_create') {
    try {
        $idProveedor = api_int('id_proveedor');
        $idMaterial = api_int('id_material');
        $cantidad = (float)api_input('cantidad', '0');
        $precio = (float)api_input('precio', '0');
        if ($idProveedor <= 0 || $idMaterial <= 0 || $cantidad <= 0 || $precio < 0) {
            api_json(['success' => false, 'message' => 'Datos de compra invalidos'], 422);
        }
        $estado = api_input('estado', 'Solicitada');
        if (!in_array($estado, ['Solicitada', 'Recibida', 'Cancelada'], true)) {
            api_json(['success' => false, 'message' => 'Estado de compra invalido'], 422);
        }
        $idCompra = $comprasDAO->crearCompra([
            'id_proveedor' => $idProveedor,
            'id_material' => $idMaterial,
            'cantidad' => $cantidad,
            'precio' => $precio,
            'fecha' => api_input('fecha', date('Y-m-d')),
            'estado' => $estado
        ]);
        api_json(['success' => true, 'id_compra' => $idCompra]);
    } catch (Throwable $e) {
        api_json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

if ($action === 'calidad_list') {
    api_json($calidadDAO->listarControles());
}

if ($action === 'calidad_register') {
    try {
        $idOrden = api_int('id_orden');
        $resultado = api_required('resultado');
        if ($idOrden <= 0 || !in_array($resultado, ['Aprobado', 'Observado', 'Rechazado'], true)) {
            api_json(['success' => false, 'message' => 'Control de calidad invalido'], 422);
        }
        $idControl = $calidadDAO->registrar([
            'id_orden' => $idOrden,
            'id_usuario' => api_int('id_usuario', 1),
            'fecha' => api_input('fecha', date('Y-m-d')),
            'resultado' => $resultado,
            'observaciones' => api_input('observaciones', '')
        ]);
        api_json(['success' => true, 'id_control' => $idControl]);
    } catch (Throwable $e) {
        api_json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

api_json(['success' => false, 'message' => 'AcciÃ³n no reconocida'], 404);
