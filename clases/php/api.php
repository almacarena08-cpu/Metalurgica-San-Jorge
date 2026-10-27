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
require_once __DIR__ . '/../daos/ClienteDAO.php';
require_once __DIR__ . '/../daos/ChatDAO.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

function api_db() {
    return Database::getConnection();
}

function api_current_role() {
    $id = (int)($_SESSION['metalgest_user_id'] ?? 0);
    if ($id <= 0) return '';
    $stmt = api_db()->prepare('SELECT r.nombre FROM usuario u JOIN rol r ON r.id_rol=u.id_rol WHERE u.id_usuario=:id AND u.id_cliente IS NULL');
    $stmt->execute([':id' => $id]);
    $role = (string)($stmt->fetchColumn() ?: '');
    $_SESSION['metalgest_role'] = $role;
    return $role;
}

function api_record_config($entity) {
    $configs = [
        'pedido'=>['table'=>'pedido','key'=>'id_pedido','module'=>'admin','fields'=>['descripcion','cantidad','material','fecha_entrega','estado']],
        'orden'=>['table'=>'orden_trabajo','key'=>'id_orden','module'=>'Produccion','fields'=>['fecha_inicio','fecha_prevista','prioridad','estado','observaciones']],
        'produccion'=>['table'=>'produccion','key'=>'id_produccion','module'=>'Produccion','fields'=>['cantidad_producida','avance','observaciones']],
        'mantenimiento'=>['table'=>'mantenimiento','key'=>'id_mantenimiento','module'=>'Mantenimiento','fields'=>['fecha','tipo','problema','reparacion','repuestos','estado']],
        'material'=>['table'=>'material','key'=>'id_material','module'=>'materials','fields'=>['nombre','tipo','unidad','espesor','stock','stock_minimo','stock_maximo']],
        'movimiento'=>['table'=>'material_movimiento','key'=>'id_movimiento','module'=>'Deposito','fields'=>['motivo']],
        'proveedor'=>['table'=>'proveedor','key'=>'id_proveedor','module'=>'Compras','fields'=>['razon_social','cuit','telefono','email']],
        'compra'=>['table'=>'compra','key'=>'id_compra','module'=>'Compras','fields'=>['fecha','estado']],
        'control'=>['table'=>'control_calidad','key'=>'id_control','module'=>'Calidad','fields'=>['fecha','resultado','observaciones']]
    ];
    return $configs[$entity] ?? null;
}

function api_audit_creation($entity, $id) {
    $idUsuario = (int)($_SESSION['metalgest_user_id'] ?? 0);
    if ($idUsuario <= 0 || (int)$id <= 0) return;
    api_db()->prepare('CREATE TABLE IF NOT EXISTS registro_creacion (entidad VARCHAR(40) NOT NULL, id_registro INT NOT NULL, id_usuario INT NOT NULL, fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(entidad,id_registro), FOREIGN KEY(id_usuario) REFERENCES usuario(id_usuario)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4')->execute();
    $stmt=api_db()->prepare('INSERT IGNORE INTO registro_creacion(entidad,id_registro,id_usuario) VALUES(:e,:i,:u)');
    $stmt->execute([':e'=>$entity,':i'=>(int)$id,':u'=>$idUsuario]);
}

function api_is_global_admin($role) {
    return in_array(strtolower(trim((string)$role)), ['administracion', 'gerencia', 'administrador global'], true);
}

function api_is_super_admin($role) {
    return strtolower(trim((string)$role)) === 'administrador global';
}

function api_is_supervisor($role) {
    return preg_match('/^supervisor\s+/i', trim((string)$role)) === 1;
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

function api_ensure_role($nombre) {
    $stmt = api_db()->prepare(
        'INSERT INTO rol (nombre)
         SELECT :nombre
         WHERE NOT EXISTS (SELECT 1 FROM rol WHERE nombre = :nombre_check)'
    );
    $stmt->execute([':nombre' => $nombre, ':nombre_check' => $nombre]);
}

$action = $_GET['action'] ?? null;
$supervisorModules = ['Produccion', 'Mantenimiento', 'Deposito', 'Compras', 'Calidad'];
try {
    foreach ($supervisorModules as $module) api_ensure_role('Supervisor ' . $module);
    api_ensure_role('Administrador Global');
} catch (PDOException $error) {
    error_log('MetalGest database initialization failed: ' . $error->getMessage());
    api_json(['success' => false, 'message' => 'No se pudo conectar con la base de datos.'], 503);
}
$actionScopes = [
    'empleados_list'=>'admin','empleados_create'=>'admin','empleados_update'=>'admin',
    'pedidos_list'=>'admin','pedidos_create'=>'admin','ordenes_list'=>'read_orders','ordenes_create'=>'admin','orden_update_status'=>'Produccion',
    'produccion_list'=>'Produccion','produccion_register'=>'Produccion','maquinas_list'=>'maintenance','mantenimiento_report'=>'Produccion',
    'mantenimientos_list'=>'Mantenimiento','mantenimiento_preventivo_create'=>'Mantenimiento','mantenimiento_update'=>'Mantenimiento',
    'materiales_list'=>'materials','materiales_create'=>'Compras','materiales_update'=>'materials','materiales_delete'=>'Compras',
    'deposito_movimientos_list'=>'Deposito','deposito_movimiento_create'=>'Deposito',
    'proveedores_list'=>'Compras','proveedores_create'=>'Compras','compras_list'=>'Compras','compras_create'=>'Compras',
    'calidad_list'=>'Calidad','calidad_register'=>'Calidad'
];
foreach (['clientes_list','clientes_create','clientes_update','clientes_delete','chat_list','chat_messages','chat_attachments_list','chat_attachment','chat_reply'] as $adminAction) $actionScopes[$adminAction] = 'admin';
$recordConfig = null;
if (in_array($action, ['record_get','record_update'], true)) {
    $recordConfig = api_record_config(api_input('entidad', ''));
    if (!$recordConfig) api_json(['success'=>false,'message'=>'Tipo de registro no valido.'],422);
    $actionScopes[$action] = $recordConfig['module'];
}
if (isset($actionScopes[$action])) {
    $role = api_current_role();
    $admin = api_is_global_admin($role);
    $scope = $actionScopes[$action];
    $moduleRole = preg_replace('/^Supervisor /i', '', $role);
    $allowed = $admin || ($scope === 'admin' ? false :
        ($scope === 'read_orders' ? in_array(strtolower($moduleRole), ['produccion','calidad'], true) :
        ($scope === 'maintenance' ? in_array(strtolower($moduleRole), ['mantenimiento','produccion'], true) :
        ($scope === 'materials' ? in_array(strtolower($moduleRole), ['deposito','compras'], true) : strcasecmp($scope, $moduleRole) === 0))));
    if (!$allowed) api_json(['success'=>false,'message'=>'Inicia sesión con un rol autorizado para este módulo.'],403);
    if (in_array($action, ['record_update','orden_update_status','mantenimiento_update','materiales_update','empleados_update','clientes_update'], true)
        && !api_is_super_admin($role) && !api_is_supervisor($role)) {
        api_json(['success'=>false,'message'=>'Solo el Administrador Global y los supervisores pueden editar registros.'],403);
    }
}
$pedidoDAO = new PedidoDAO();
$ordenTrabajoDAO = new OrdenTrabajoDAO();
$produccionDAO = new ProduccionDAO();
$maquinaDAO = new MaquinaDAO();
$mantenimientoDAO = new MantenimientoDAO();
$preferenciasDAO = new PreferenciasUsuarioDAO();
$depositoDAO = new DepositoDAO();
$comprasDAO = new ComprasDAO();
$calidadDAO = new CalidadDAO();
$clienteDAO = new ClienteDAO();
$chatDAO = new ChatDAO();

if ($action === 'health') {
    api_json(['success' => true, 'app' => 'MetalGest']);
}

if ($action === 'chat_login') {
    try {
        $nombreLogin = api_required('usuario');
        $contrasenaLogin = api_required('contrasena');
        api_ensure_role('Cliente');
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
                id_usuario = id_usuario'
        );
        foreach ($usuariosBase as $usuarioBase) {
            $crearUsuarioBase->execute([
                ':nombre' => $usuarioBase[0], ':apellido' => $usuarioBase[1],
                ':usuario' => $usuarioBase[2], ':rol' => $usuarioBase[3]
            ]);
        }
        $stmt = api_db()->prepare(
            'SELECT u.id_usuario, u.nombre, u.apellido, r.nombre AS rol
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
        if (!$user || !api_is_global_admin($user['rol'])) {
            api_json(['success' => false, 'message' => 'Credenciales incorrectas o sin acceso a Administración.'], 401);
        }
        $_SESSION['metalgest_chat_user'] = [
            'id_usuario' => (int)$user['id_usuario'],
            'rol' => $user['rol']
        ];
        $_SESSION['metalgest_user_id'] = (int)$user['id_usuario'];
        $_SESSION['metalgest_role'] = $user['rol'];
        api_json(['success' => true, 'usuario' => $user]);
    } catch (Throwable $e) {
        api_json(['success' => false, 'message' => 'No se pudo iniciar la sesión del chat.'], 500);
    }
}

if ($action === 'record_get') {
    $id=api_int('id_registro');
    $stmt=api_db()->prepare('SELECT * FROM '.$recordConfig['table'].' WHERE '.$recordConfig['key'].'=:id');
    $stmt->execute([':id'=>$id]); $row=$stmt->fetch();
    if (!$row) api_json(['success'=>false,'message'=>'El registro ya no existe.'],404);
    api_db()->exec('CREATE TABLE IF NOT EXISTS registro_creacion (entidad VARCHAR(40) NOT NULL, id_registro INT NOT NULL, id_usuario INT NOT NULL, fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(entidad,id_registro), FOREIGN KEY(id_usuario) REFERENCES usuario(id_usuario)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    $owner=(int)($row['id_usuario'] ?? 0);
    $audit=api_db()->prepare('SELECT id_usuario FROM registro_creacion WHERE entidad=:e AND id_registro=:i');
    $audit->execute([':e'=>api_input('entidad'),':i'=>$id]); $owner=(int)($audit->fetchColumn() ?: $owner);
    if ($owner>0) { $user=api_db()->prepare('SELECT CONCAT(nombre," ",apellido) FROM usuario WHERE id_usuario=:id'); $user->execute([':id'=>$owner]); $row['creado_por']=$user->fetchColumn() ?: 'Usuario #'.$owner; }
    else $row['creado_por']='Sin registro';
    $row['campos_editables']=$recordConfig['fields'];
    api_json(['success'=>true,'registro'=>$row]);
}

if ($action === 'record_update') {
    $id=api_int('id_registro'); if($id<=0) api_json(['success'=>false,'message'=>'El registro no es valido.'],422);
    $sets=[]; $values=[':id'=>$id];
    foreach($recordConfig['fields'] as $field) if(array_key_exists($field,$_POST)) { $sets[]='`'.$field.'`=:'.$field; $values[':'.$field]=api_input($field,''); }
    if(!$sets) api_json(['success'=>false,'message'=>'No hay cambios para guardar.'],422);
    if (api_input('entidad')==='orden' && isset($_POST['estado']) && !in_array(api_input('estado'),['Pendiente','En produccion','Pausada','Finalizada'],true)) api_json(['success'=>false,'message'=>'Estado de orden no valido.'],422);
    if (api_input('entidad')==='mantenimiento' && isset($_POST['estado']) && !in_array(api_input('estado'),['Pendiente','En proceso','Resuelta'],true)) api_json(['success'=>false,'message'=>'Estado de mantenimiento no valido.'],422);
    if (api_input('entidad')==='control' && isset($_POST['resultado']) && !in_array(api_input('resultado'),['Aprobado','Observado','Rechazado'],true)) api_json(['success'=>false,'message'=>'Resultado de control no valido.'],422);
    if (api_input('entidad')==='produccion' && ((isset($_POST['avance']) && (int)api_input('avance')<0) || (isset($_POST['avance']) && (int)api_input('avance')>100) || (isset($_POST['cantidad_producida']) && (int)api_input('cantidad_producida')<0))) api_json(['success'=>false,'message'=>'El avance o la cantidad no son validos.'],422);
    if (api_input('entidad')==='material' && ((isset($_POST['stock']) && (float)api_input('stock')<0) || (isset($_POST['stock_minimo']) && (float)api_input('stock_minimo')<0) || (isset($_POST['stock_maximo']) && (float)api_input('stock_maximo')<0))) api_json(['success'=>false,'message'=>'El stock no puede ser negativo.'],422);
    try {
        $stmt=api_db()->prepare('UPDATE '.$recordConfig['table'].' SET '.implode(',',$sets).' WHERE '.$recordConfig['key'].'=:id'); $stmt->execute($values);
        if(api_input('entidad')==='orden' && isset($_POST['estado'])) { $p=api_db()->prepare('UPDATE pedido p JOIN orden_trabajo ot ON ot.id_pedido=p.id_pedido SET p.estado=:s WHERE ot.id_orden=:id'); $p->execute([':s'=>api_input('estado'),':id'=>$id]); }
        if(api_input('entidad')==='mantenimiento' && isset($_POST['estado'])) { $m=api_db()->prepare('UPDATE maquina q JOIN mantenimiento m ON m.id_maquina=q.id_maquina SET q.estado=:s WHERE m.id_mantenimiento=:id'); $m->execute([':s'=>api_input('estado')==='Resuelta'?'Operativa':'Con falla',':id'=>$id]); }
        if(api_input('entidad')==='produccion') { $q=api_db()->prepare('SELECT id_orden FROM produccion WHERE id_produccion=:id'); $q->execute([':id'=>$id]); $orderId=(int)$q->fetchColumn(); $latest=api_db()->prepare('SELECT avance FROM produccion WHERE id_orden=:id ORDER BY id_produccion DESC LIMIT 1'); $latest->execute([':id'=>$orderId]); $state=(int)$latest->fetchColumn()>=100?'Finalizada':'En produccion'; $o=api_db()->prepare('UPDATE orden_trabajo SET estado=:s WHERE id_orden=:id'); $o->execute([':s'=>$state,':id'=>$orderId]); $p=api_db()->prepare('UPDATE pedido p JOIN orden_trabajo ot ON ot.id_pedido=p.id_pedido SET p.estado=:s WHERE ot.id_orden=:id'); $p->execute([':s'=>$state,':id'=>$orderId]); }
        api_json(['success'=>true]);
    } catch(Throwable $e) { api_json(['success'=>false,'message'=>$e->getMessage()],422); }
}

if (in_array($action, ['empleados_list', 'empleados_create', 'empleados_update'], true)) {
    if (!api_is_global_admin(api_current_role())) api_json(['success'=>false,'message'=>'Acceso solo para el administrador global.'],403);
    if ($action === 'empleados_list') api_json(api_db()->query('SELECT u.id_usuario,u.nombre,u.apellido,u.usuario,r.nombre rol FROM usuario u JOIN rol r ON r.id_rol=u.id_rol WHERE r.nombre <> "Cliente" ORDER BY u.nombre,u.apellido')->fetchAll());
    $nombre=api_required('nombre'); $apellido=api_input('apellido',''); $usuario=api_required('usuario'); $rol=api_required('rol');
    if ($action === 'empleados_create') {
        $stmt=api_db()->prepare('INSERT INTO usuario(nombre,apellido,usuario,contrasena,id_rol) SELECT :n,:a,:u,:c,id_rol FROM rol WHERE nombre=:r');
        $stmt->execute([':n'=>$nombre,':a'=>$apellido,':u'=>$usuario,':c'=>api_required('contrasena'),':r'=>$rol]);
    } else {
        $stmt=api_db()->prepare('UPDATE usuario SET nombre=:n,apellido=:a,usuario=:u,id_rol=(SELECT id_rol FROM rol WHERE nombre=:r) WHERE id_usuario=:id AND id_cliente IS NULL');
        $stmt->execute([':n'=>$nombre,':a'=>$apellido,':u'=>$usuario,':r'=>$rol,':id'=>api_int('id_empleado')]);
        if (api_input('contrasena','') !== '') { $pw=api_db()->prepare('UPDATE usuario SET contrasena=:p WHERE id_usuario=:id'); $pw->execute([':p'=>api_input('contrasena'),':id'=>api_int('id_empleado')]); }
    }
    api_json(['success'=>true]);
}

function api_chat_upload_directory() {
    return __DIR__ . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . 'chat_uploads';
}

function api_chat_find_file($key) {
    $directories = [
        api_chat_upload_directory(),
        dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'web' . DIRECTORY_SEPARATOR . 'Metalurgica-San-Jorge'
            . DIRECTORY_SEPARATOR . 'clases' . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . 'private'
            . DIRECTORY_SEPARATOR . 'chat_uploads'
    ];
    foreach ($directories as $directory) {
        $realDirectory = realpath($directory);
        $file = $realDirectory ? realpath($realDirectory . DIRECTORY_SEPARATOR . basename($key)) : false;
        if ($file && dirname($file) === $realDirectory && is_file($file)) return $file;
    }
    return false;
}

function api_chat_store_uploads() {
    if (empty($_FILES['adjuntos'])) {
        return [];
    }
    $files = $_FILES['adjuntos'];
    $names = is_array($files['name']) ? $files['name'] : [$files['name']];
    $temporaryPaths = is_array($files['tmp_name']) ? $files['tmp_name'] : [$files['tmp_name']];
    $errors = is_array($files['error']) ? $files['error'] : [$files['error']];
    $sizes = is_array($files['size']) ? $files['size'] : [$files['size']];
    if (count($names) > 5) {
        throw new RuntimeException('Podés adjuntar hasta 5 archivos por mensaje.');
    }
    $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'dxf', 'dwg'];
    $directory = api_chat_upload_directory();
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        throw new RuntimeException('No se pudo preparar el almacenamiento de archivos.');
    }
    $stored = [];
    foreach ($names as $index => $name) {
        if ((int)$errors[$index] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('No se pudo subir uno de los archivos.');
        }
        $size = (int)$sizes[$index];
        $extension = strtolower(pathinfo((string)$name, PATHINFO_EXTENSION));
        if ($size <= 0 || $size > 10 * 1024 * 1024 || !in_array($extension, $allowed, true)
                || !is_uploaded_file($temporaryPaths[$index])) {
            throw new RuntimeException('Adjuntá PDF, JPG, PNG, WebP, DXF o DWG de hasta 10 MB.');
        }
        $key = bin2hex(random_bytes(24)) . '.' . $extension;
        if (!move_uploaded_file($temporaryPaths[$index], $directory . DIRECTORY_SEPARATOR . $key)) {
            throw new RuntimeException('No se pudo guardar uno de los archivos.');
        }
        $stored[] = [
            'nombre_original' => basename(str_replace('\\', '/', (string)$name)),
            'clave_archivo' => $key,
            'tipo_mime' => (new finfo(FILEINFO_MIME_TYPE))->file($directory . DIRECTORY_SEPARATOR . $key),
            'tamano' => $size
        ];
    }
    return $stored;
}

function api_chat_remove_uploads(array $uploads) {
    foreach ($uploads as $upload) {
        $path = api_chat_upload_directory() . DIRECTORY_SEPARATOR . basename($upload['clave_archivo'] ?? '');
        if (is_file($path)) unlink($path);
    }
}

if ($action === 'chat_login_app') {
    try {
        $idUsuario = api_int('id_usuario');
        $rol = api_input('rol', '');
        if ($idUsuario <= 0 || $idUsuario !== (int)($_SESSION['metalgest_user_id'] ?? 0)
            || !api_is_global_admin($rol)
            || strcasecmp(api_current_role(), $rol) !== 0) {
            api_json(['success' => false, 'message' => 'El usuario no tiene acceso al chat.'], 403);
        }
        $stmt = api_db()->prepare(
            'SELECT u.id_usuario, r.nombre AS rol
             FROM usuario u INNER JOIN rol r ON r.id_rol = u.id_rol
             WHERE u.id_usuario = :id_usuario
               AND LOWER(r.nombre) IN ("administracion", "gerencia", "administrador global")
             LIMIT 1'
        );
        $stmt->execute([':id_usuario' => $idUsuario]);
        $user = $stmt->fetch();
        if (!$user || strcasecmp($user['rol'], $rol) !== 0) {
            api_json(['success' => false, 'message' => 'El usuario no tiene acceso al chat.'], 403);
        }
        $_SESSION['metalgest_user_id'] = (int)$user['id_usuario'];
        $_SESSION['metalgest_role'] = $user['rol'];
        api_json(['success' => true]);
    } catch (Throwable $e) {
        api_json(['success' => false, 'message' => 'No se pudo validar el acceso al chat.'], 500);
    }
}

if ($action === 'chat_logout') {
    unset($_SESSION['metalgest_chat_user']);
    api_json(['success' => true]);
}

if ($action === 'chat_auth_check') {
    $user = $_SESSION['metalgest_chat_user'] ?? null;
    if ($user) $user['rol'] = api_current_role();
    if (!$user && api_is_global_admin(api_current_role()))
        $user = ['id_usuario' => (int)$_SESSION['metalgest_user_id'], 'rol' => $_SESSION['metalgest_role']];
    if (!$user || !api_is_global_admin($user['rol'] ?? '')) {
        api_json(['success' => false, 'message' => 'Iniciá sesión para acceder al chat.'], 401);
    }
    api_json(['success' => true, 'usuario' => $user]);
}

if (in_array($action, ['chat_list', 'chat_messages', 'chat_attachments_list', 'chat_reply'], true)) {
    $user = $_SESSION['metalgest_chat_user'] ?? null;
    if (!$user && api_is_global_admin(api_current_role()))
        $user = ['id_usuario' => (int)$_SESSION['metalgest_user_id'], 'rol' => $_SESSION['metalgest_role']];
    if (!$user || !api_is_global_admin($user['rol'] ?? '')) {
        api_json(['success' => false, 'message' => 'Iniciá sesión con una cuenta autorizada para acceder al chat.'], 401);
    }
    if ($action === 'chat_list') {
        api_json($chatDAO->listar());
    }
    if ($action === 'chat_messages') {
        $idChat = api_int('id_chat');
        if ($idChat <= 0 || !$chatDAO->existe($idChat)) {
            api_json(['success' => false, 'message' => 'La conversación no existe.'], 404);
        }
        api_json($chatDAO->obtenerMensajes($idChat));
    }
    if ($action === 'chat_attachments_list') {
        $idChat = api_int('id_chat');
        if ($idChat <= 0 || !$chatDAO->existe($idChat)) {
            api_json(['success' => false, 'message' => 'La conversación no existe.'], 404);
        }
        api_json($chatDAO->listarAdjuntos($idChat));
    }
    try {
        $idChat = api_int('id_chat');
        if ($idChat <= 0 || !$chatDAO->existe($idChat)) {
            api_json(['success' => false, 'message' => 'La conversación no existe.'], 404);
        }
        $mensaje = api_input('mensaje', '');
        $uploads = api_chat_store_uploads();
        if ($mensaje === '' && !$uploads) {
            api_json(['success' => false, 'message' => 'Escribí un mensaje o adjuntá un archivo.'], 422);
        }
        $chatDAO->enviarRespuesta($idChat, (int)$user['id_usuario'], $mensaje, $uploads);
        api_json(['success' => true]);
    } catch (Throwable $e) {
        api_chat_remove_uploads($uploads ?? []);
        api_json(['success' => false, 'message' => 'No se pudo enviar la respuesta.'], 500);
    }
}

if ($action === 'chat_attachment') {
    $idUsuario = isset($_GET['id_usuario']) ? (int)$_GET['id_usuario'] : 0;
    $stmt = api_db()->prepare(
        'SELECT u.id_usuario FROM usuario u INNER JOIN rol r ON r.id_rol = u.id_rol
         WHERE u.id_usuario = :id_usuario AND LOWER(r.nombre) IN ("administracion", "gerencia", "administrador global") LIMIT 1'
    );
    $stmt->execute([':id_usuario' => $idUsuario]);
    if (!$stmt->fetch()) {
        api_json(['success' => false, 'message' => 'No tenés acceso a este archivo.'], 403);
    }
    $idAdjunto = isset($_GET['id_adjunto']) ? (int)$_GET['id_adjunto'] : 0;
    $adjunto = $idAdjunto > 0 ? $chatDAO->obtenerAdjunto($idAdjunto) : false;
    $file = $adjunto ? api_chat_find_file($adjunto['clave_archivo']) : false;
    if (!$adjunto || !$file) {
        api_json(['success' => false, 'message' => 'El archivo no está disponible.'], 404);
    }
    header('Content-Type: ' . $adjunto['tipo_mime']);
    header('Content-Length: ' . filesize($file));
    header('Content-Disposition: attachment; filename*=UTF-8\'\'' . rawurlencode($adjunto['nombre_original']));
    readfile($file);
    exit;
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
            'modo_oscuro' => 1,
            'tamano_texto' => $tamano
        ]);
        api_json(['success' => true]);
    } catch (Throwable $e) {
        api_json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

if ($action === 'login') {
    $nombreLogin = api_input('usuario');
    if ($nombreLogin === null || $nombreLogin === '') {
        $nombreLogin = api_required('nombre');
    }
    $contrasenaLogin = api_required('contrasena');
    api_ensure_role('Cliente');
    $usuariosBase = [
        ['Gerencia', 'San Jorge', 'gerencia', 'Gerencia'],
        ['Administracion', 'San Jorge', 'admin', 'Administracion'],
        ['Produccion', 'San Jorge', 'produccion', 'Produccion'],
        ['Mantenimiento', 'San Jorge', 'mantenimiento', 'Mantenimiento'],
        ['Deposito', 'San Jorge', 'deposito', 'Deposito'],
        ['Compras', 'San Jorge', 'compras', 'Compras'],
        ['Calidad', 'San Jorge', 'calidad', 'Calidad'],
        ['Administrador', 'Global', 'admin_global', 'Administrador Global'],
        ['Supervisor', 'Produccion', 'supervisor_produccion', 'Supervisor Produccion'],
        ['Supervisor', 'Mantenimiento', 'supervisor_mantenimiento', 'Supervisor Mantenimiento'],
        ['Supervisor', 'Deposito', 'supervisor_deposito', 'Supervisor Deposito'],
        ['Supervisor', 'Compras', 'supervisor_compras', 'Supervisor Compras'],
        ['Supervisor', 'Calidad', 'supervisor_calidad', 'Supervisor Calidad']
    ];
    $crearUsuarioBase = api_db()->prepare(
        'INSERT INTO usuario (nombre, apellido, usuario, contrasena, id_rol)
         SELECT :nombre, :apellido, :usuario, "1234", id_rol
         FROM rol WHERE nombre = :rol
         ON DUPLICATE KEY UPDATE
            id_usuario = id_usuario'
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
        'SELECT u.id_usuario, u.nombre, u.apellido, u.usuario, u.contrasena, u.id_cliente, r.nombre AS rol
         FROM usuario u
         INNER JOIN rol r ON r.id_rol = u.id_rol
         WHERE (u.usuario = :usuario OR u.nombre = :nombre OR CONCAT(u.nombre, " ", u.apellido) = :nombre_completo)
         LIMIT 1'
    );
    $stmt->execute([
        ':usuario' => $nombreLogin,
        ':nombre' => $nombreLogin,
        ':nombre_completo' => $nombreLogin
    ]);
    $user = $stmt->fetch();
    if (!$user || (!password_verify($contrasenaLogin, $user['contrasena'])
        && !hash_equals((string)$user['contrasena'], $contrasenaLogin))) {
        api_json(['success' => false, 'message' => 'Credenciales incorrectas'], 401);
    }
    if (password_get_info($user['contrasena'])['algo'] === null) {
        $updatePassword = api_db()->prepare('UPDATE usuario SET contrasena = :contrasena WHERE id_usuario = :id_usuario');
        $updatePassword->execute([
            ':contrasena' => password_hash($contrasenaLogin, PASSWORD_DEFAULT),
            ':id_usuario' => (int)$user['id_usuario']
        ]);
    }
    session_regenerate_id(true);
    $_SESSION['metalgest_user_id'] = (int)$user['id_usuario'];
    $_SESSION['metalgest_role'] = $user['rol'];
    $_SESSION['metalgest_user'] = [
        'tipo' => 'empleado',
        'id_usuario' => (int)$user['id_usuario'],
        'rol' => $user['rol']
    ];
    unset($user['contrasena']);
    api_json(['success' => true, 'usuario' => $user]);
}

if ($action === 'logout') {
    $_SESSION = [];
    session_destroy();
    api_json(['success'=>true]);
}

if ($action === 'cliente_register') {
    try {
        api_ensure_role('Cliente');
        $usuario = api_required('usuario');
        $contrasena = api_required('contrasena');
        $razonSocial = api_required('razon_social');
        $email = api_required('email');
        $pdo = api_db();
        $pdo->beginTransaction();

        $cliente = $pdo->prepare(
            'INSERT INTO cliente (razon_social, cuit, telefono, email, direccion)
             VALUES (:razon_social, :cuit, :telefono, :email, :direccion)'
        );
        $cliente->execute([
            ':razon_social' => $razonSocial,
            ':cuit' => api_input('cuit', ''),
            ':telefono' => api_input('telefono', ''),
            ':email' => $email,
            ':direccion' => api_input('direccion', '')
        ]);
        $idCliente = (int)$pdo->lastInsertId();

        $usuarioStmt = $pdo->prepare(
            'INSERT INTO usuario (nombre, apellido, usuario, contrasena, id_rol, id_cliente)
             SELECT :nombre, "", :usuario, :contrasena, id_rol, :id_cliente
             FROM rol
             WHERE nombre = "Cliente"'
        );
        $usuarioStmt->execute([
            ':nombre' => $razonSocial,
            ':usuario' => $usuario,
            ':contrasena' => $contrasena,
            ':id_cliente' => $idCliente
        ]);
        $idUsuario = (int)$pdo->lastInsertId();
        $pdo->commit();
        api_json([
            'success' => true,
            'usuario' => [
                'id_usuario' => $idUsuario,
                'nombre' => $razonSocial,
                'apellido' => '',
                'usuario' => $usuario,
                'id_cliente' => $idCliente,
                'rol' => 'Cliente'
            ]
        ]);
    } catch (Throwable $e) {
        if (api_db()->inTransaction()) {
            api_db()->rollBack();
        }
        $message = strpos($e->getMessage(), 'Duplicate') !== false ? 'El usuario ya existe' : $e->getMessage();
        api_json(['success' => false, 'message' => $message], 500);
    }
}

if ($action === 'clientes_list') {
    api_json($clienteDAO->listar());
}

if ($action === 'clientes_create') {
    try {
        $idCliente = $clienteDAO->crear([
            'razon_social' => api_required('razon_social'),
            'cuit' => api_input('cuit', ''),
            'telefono' => api_input('telefono', ''),
            'email' => api_input('email', ''),
            'direccion' => api_input('direccion', '')
        ]);
        api_json(['success' => true, 'id_cliente' => $idCliente]);
    } catch (Throwable $e) {
        api_json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

if ($action === 'clientes_update') {
    try {
        $idCliente = api_int('id_cliente');
        if ($idCliente <= 0) {
            api_json(['success' => false, 'message' => 'El cliente debe ser valido'], 422);
        }
        $clienteDAO->actualizar([
            'id_cliente' => $idCliente,
            'razon_social' => api_required('razon_social'),
            'cuit' => api_input('cuit', ''),
            'telefono' => api_input('telefono', ''),
            'email' => api_input('email', ''),
            'direccion' => api_input('direccion', '')
        ]);
        api_json(['success' => true]);
    } catch (Throwable $e) {
        api_json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

if ($action === 'clientes_delete') {
    try {
        $idCliente = api_int('id_cliente');
        if ($idCliente <= 0) {
            api_json(['success' => false, 'message' => 'El cliente debe ser valido'], 422);
        }
        $clienteDAO->eliminar($idCliente);
        api_json(['success' => true]);
    } catch (Throwable $e) {
        api_json(['success' => false, 'message' => 'No se puede eliminar un cliente con pedidos asociados'], 409);
    }
}

if ($action === 'chat_list') {
    $idCliente = isset($_POST['id_cliente']) ? api_int('id_cliente') : 0;
    api_json($idCliente > 0 ? $chatDAO->listarPorCliente($idCliente) : $chatDAO->listar());
}

if ($action === 'chat_messages') {
    $idChat = api_int('id_chat');
    if ($idChat <= 0) {
        api_json(['success' => false, 'message' => 'El chat debe ser valido'], 422);
    }
    api_json($chatDAO->obtenerMensajes($idChat));
}

if ($action === 'chat_send') {
    try {
        $idChat = api_int('id_chat');
        $idUsuario = api_int('id_usuario');
        $idCliente = api_int('id_cliente');
        $autorTipo = api_input('autor_tipo', 'Cliente');
        if ($idUsuario <= 0 || !in_array($autorTipo, ['Cliente', 'Administracion'], true)) {
            api_json(['success' => false, 'message' => 'Usuario o autor invalido'], 422);
        }
        if ($idChat > 0) {
            $chatDAO->enviarMensaje([
                'id_chat' => $idChat,
                'id_usuario' => $idUsuario,
                'autor_tipo' => $autorTipo,
                'mensaje' => api_required('mensaje')
            ]);
            api_json(['success' => true, 'id_chat' => $idChat]);
        }
        if ($idCliente <= 0) {
            api_json(['success' => false, 'message' => 'El cliente debe ser valido'], 422);
        }
        $nuevoChat = $chatDAO->crearConversacionCliente([
            'id_cliente' => $idCliente,
            'id_usuario' => $idUsuario,
            'asunto' => api_input('asunto', 'Consulta a administracion'),
            'mensaje' => api_required('mensaje')
        ]);
        api_json(['success' => true, 'id_chat' => $nuevoChat]);
    } catch (Throwable $e) {
        api_json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

if ($action === 'chat_reply') {
    try {
        $idChat = api_int('id_chat');
        if ($idChat <= 0) {
            api_json(['success' => false, 'message' => 'El chat debe ser valido'], 422);
        }
        $chatDAO->enviarMensaje([
            'id_chat' => $idChat,
            'id_usuario' => api_int('id_usuario', 1),
            'autor_tipo' => 'Administracion',
            'mensaje' => api_required('mensaje')
        ]);
        api_json(['success' => true]);
    } catch (Throwable $e) {
        api_json(['success' => false, 'message' => $e->getMessage()], 500);
    }
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
        $idCliente = api_int('id_cliente');
        $idPedido = $pedidoDAO->crear([
            'id_cliente' => $idCliente,
            'razon_social' => $idCliente > 0 ? api_input('razon_social', '') : api_required('razon_social'),
            'cuit' => api_input('cuit', ''),
            'telefono' => api_input('telefono', ''),
            'email' => api_input('email', ''),
            'direccion' => api_input('direccion', ''),
            'descripcion' => api_required('descripcion'),
            'cantidad' => $cantidad,
            'material' => api_required('material'),
            'fecha_entrega' => api_date(api_input('fecha_entrega'))
        ]);
        api_audit_creation('pedido', $idPedido);
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
            'id_usuario' => (int)($_SESSION['metalgest_user_id'] ?? 1)
        ]);
        api_audit_creation('orden', $idOrden);
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
        api_audit_creation('produccion', $idProduccion);
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
            'id_usuario' => (int)($_SESSION['metalgest_user_id'] ?? 3),
            'tipo' => api_input('tipo', 'Correctivo'),
            'problema' => api_required('problema')
        ]);
        api_audit_creation('mantenimiento', $idMantenimiento);
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
            'id_usuario' => (int)($_SESSION['metalgest_user_id'] ?? 1),
            'fecha' => api_input('fecha', date('Y-m-d')),
            'problema' => api_required('problema')
        ]);
        api_audit_creation('mantenimiento', $idMantenimiento);
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
    api_json($comprasDAO->listarMateriales());
}

if ($action === 'materiales_create') {
    try {
        $stockMinimo = (float)api_input('stock_minimo', '0');
        $stockMaximo = (float)api_input('stock_maximo', '0');
        if ($stockMinimo < 0 || $stockMaximo < $stockMinimo) {
            api_json(['success' => false, 'message' => 'Los limites de stock no son validos'], 422);
        }
        $idMaterial = $comprasDAO->crearMaterial([
            'nombre' => api_required('nombre'), 'tipo' => api_input('tipo', ''), 'unidad' => api_input('unidad', ''),
            'espesor' => api_input('espesor', ''), 'stock' => (float)api_input('stock', '0'),
            'stock_minimo' => $stockMinimo, 'stock_maximo' => $stockMaximo
        ]);
        api_audit_creation('material', $idMaterial);
        api_json(['success' => true, 'id_material' => $idMaterial]);
    } catch (Throwable $e) {
        api_json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

if ($action === 'materiales_update') {
    try {
        $idMaterial = api_int('id_material');
        $stockMinimo = (float)api_input('stock_minimo', '0');
        $stockMaximo = (float)api_input('stock_maximo', '0');
        if ($idMaterial <= 0 || $stockMinimo < 0 || $stockMaximo < $stockMinimo) {
            api_json(['success' => false, 'message' => 'Los limites de stock no son validos'], 422);
        }
        $comprasDAO->actualizarMaterial([
            'id_material' => $idMaterial, 'nombre' => api_required('nombre'), 'tipo' => api_input('tipo', ''),
            'unidad' => api_input('unidad', ''), 'espesor' => api_input('espesor', ''),
            'stock_minimo' => $stockMinimo, 'stock_maximo' => $stockMaximo
        ]);
        api_json(['success' => true]);
    } catch (Throwable $e) {
        api_json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

if ($action === 'materiales_delete') {
    try {
        $idMaterial = api_int('id_material');
        if ($idMaterial <= 0) {
            api_json(['success' => false, 'message' => 'El material no es valido'], 422);
        }
        $comprasDAO->eliminarMaterial($idMaterial);
        api_json(['success' => true]);
    } catch (Throwable $e) {
        api_json(['success' => false, 'message' => 'No se puede eliminar un material que ya tiene movimientos u ordenes asociadas'], 409);
    }
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
            'id_usuario' => (int)($_SESSION['metalgest_user_id'] ?? 1)
        ]);
        api_audit_creation('movimiento', $idMovimiento);
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
        api_audit_creation('proveedor', $idProveedor);
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
        api_audit_creation('compra', $idCompra);
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
            'id_usuario' => (int)($_SESSION['metalgest_user_id'] ?? 1),
            'fecha' => api_input('fecha', date('Y-m-d')),
            'resultado' => $resultado,
            'observaciones' => api_input('observaciones', '')
        ]);
        api_audit_creation('control', $idControl);
        api_json(['success' => true, 'id_control' => $idControl]);
    } catch (Throwable $e) {
        api_json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

api_json(['success' => false, 'message' => 'AcciÃ³n no reconocida'], 404);
