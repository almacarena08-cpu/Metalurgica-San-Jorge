<?php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../daos/PedidoDAO.php';
require_once __DIR__ . '/../daos/OrdenTrabajoDAO.php';
require_once __DIR__ . '/../daos/ProduccionDAO.php';
require_once __DIR__ . '/../daos/MaquinaDAO.php';
require_once __DIR__ . '/../daos/MantenimientoDAO.php';
require_once __DIR__ . '/../daos/PreferenciasUsuarioDAO.php';

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

if ($action === 'health') {
    api_json(['success' => true, 'app' => 'MetalGest']);
}

if ($action === 'preferences_get') {
    $idUsuario = api_int('id_usuario');
    if ($idUsuario <= 0) {
        api_json(['success' => false, 'message' => 'Usuario inválido'], 422);
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
            api_json(['success' => false, 'message' => 'Preferencias inválidas'], 422);
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
    $rolEsperado = api_input('rol', '');
    $contrasenasModulo = [
        'Administracion' => 'admin',
        'Produccion' => 'produccion',
        'Mantenimiento' => 'mantenimiento'
    ];
    if ($rolEsperado !== '' && isset($contrasenasModulo[$rolEsperado])) {
        if (!hash_equals($contrasenasModulo[$rolEsperado], $contrasenaLogin)) {
            api_json(['success' => false, 'message' => 'Contraseña incorrecta'], 401);
        }
        $partesNombre = preg_split('/\s+/', $nombreLogin, 2);
        $nombre = $partesNombre[0];
        $apellido = $partesNombre[1] ?? '';
        $stmt = api_db()->prepare(
            'SELECT u.id_usuario, u.nombre, u.apellido, u.usuario, r.nombre AS rol
             FROM usuario u
             INNER JOIN rol r ON r.id_rol = u.id_rol
             WHERE u.nombre = :nombre AND (u.apellido = :apellido OR :apellido_filtro = "") AND r.nombre = :rol
             LIMIT 1'
        );
        $stmt->execute([
            ':nombre' => $nombre,
            ':apellido' => $apellido,
            ':apellido_filtro' => $apellido,
            ':rol' => $rolEsperado
        ]);
        $user = $stmt->fetch();
        if (!$user) {
            $usuario = 'empleado_' . strtolower(preg_replace('/[^a-z0-9]+/i', '_', $nombreLogin)) . '_' . bin2hex(random_bytes(3));
            $crear = api_db()->prepare(
                'INSERT INTO usuario (nombre, apellido, usuario, contrasena, id_rol)
                 SELECT :nombre, :apellido, :usuario, :contrasena, id_rol
                 FROM rol WHERE nombre = :rol'
            );
            $crear->execute([
                ':nombre' => $nombre,
                ':apellido' => $apellido,
                ':usuario' => $usuario,
                ':contrasena' => $contrasenaLogin,
                ':rol' => $rolEsperado
            ]);
            $id = (int)api_db()->lastInsertId();
            $user = [
                'id_usuario' => $id,
                'nombre' => $nombre,
                'apellido' => $apellido,
                'usuario' => $usuario,
                'rol' => $rolEsperado
            ];
        }
        api_json(['success' => true, 'usuario' => $user]);
    }
    $stmt = api_db()->prepare(
        'SELECT u.id_usuario, u.nombre, u.apellido, u.usuario, r.nombre AS rol
         FROM usuario u
         INNER JOIN rol r ON r.id_rol = u.id_rol
                 WHERE (u.nombre = :nombre OR CONCAT(u.nombre, " ", u.apellido) = :nombre_completo)
                     AND u.contrasena = :contrasena'
    );
        $stmt->execute([
                ':nombre' => $nombreLogin,
                ':nombre_completo' => $nombreLogin,
            ':contrasena' => $contrasenaLogin
        ]);
    $user = $stmt->fetch();
    $rolesValidos = [
        'Administracion' => ['Administracion', 'Gerencia'],
        'Produccion' => ['Produccion'],
        'Mantenimiento' => ['Mantenimiento']
    ];
    if (!$user || ($rolEsperado !== '' && (!isset($rolesValidos[$rolEsperado]) || !in_array($user['rol'], $rolesValidos[$rolEsperado], true)))) {
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
            api_json(['success' => false, 'message' => 'El pedido debe ser válido'], 422);
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
            api_json(['success' => false, 'message' => 'La orden debe ser válida'], 422);
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
        api_json(['success' => false, 'message' => 'Datos de producción inválidos'], 422);
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
            api_json(['success' => false, 'message' => 'La máquina debe ser válida'], 422);
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
            api_json(['success' => false, 'message' => 'La máquina debe ser válida'], 422);
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
            api_json(['success' => false, 'message' => 'El mantenimiento debe ser válido'], 422);
        }
        $estado = api_required('estado');
        if (!in_array($estado, ['Pendiente', 'En proceso', 'Resuelta'], true)) {
            api_json(['success' => false, 'message' => 'Estado de mantenimiento inválido'], 422);
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

api_json(['success' => false, 'message' => 'Acción no reconocida'], 404);
