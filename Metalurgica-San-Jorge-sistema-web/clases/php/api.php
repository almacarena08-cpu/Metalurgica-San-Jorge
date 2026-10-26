<?php
$apiOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedApiOrigins = [
    'http://127.0.0.1:5500',
    'http://localhost:5500',
    'http://127.0.0.1:5501',
    'http://localhost:5501'
];
if (in_array($apiOrigin, $allowedApiOrigins, true)) {
    header('Access-Control-Allow-Origin: ' . $apiOrigin);
    header('Vary: Origin');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    header('Access-Control-Allow-Credentials: true');
}
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}
session_start();

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
require_once __DIR__ . '/../daos/PedidoSeguimientoDAO.php';

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

function api_base_url() {
    $configured = getenv('METALGEST_BASE_URL');
    if ($configured) {
        return rtrim($configured, '/');
    }
    $host = preg_replace('/[^a-zA-Z0-9.\-:\[\]]/', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/clases/php/api.php');
    $projectPath = dirname(dirname(dirname($script)));
    return ($https ? 'https://' : 'http://') . $host . rtrim($projectPath, '/');
}

const CLIENT_CODE_RESEND_COOLDOWN_SECONDS = 30;

function api_mail_smtp_settings() {
    $sendmailPath = ini_get('sendmail_path');
    $sendmailExecutable = '';
    if (preg_match('/"([^"]+\.exe)"/i', $sendmailPath, $matches)) {
        $sendmailExecutable = $matches[1];
    } elseif ($sendmailPath) {
        $sendmailExecutable = strtok($sendmailPath, " \t");
    }

    $sendmailConfig = [];
    if ($sendmailExecutable && is_file($sendmailExecutable)) {
        $configPath = dirname($sendmailExecutable) . DIRECTORY_SEPARATOR . 'sendmail.ini';
        if (is_file($configPath)) {
            $parsed = parse_ini_file($configPath, true, INI_SCANNER_RAW);
            if (is_array($parsed)) {
                $sendmailConfig = $parsed['sendmail'] ?? [];
            }
        }
    }

    return [
        'host' => getenv('METALGEST_SMTP_HOST') ?: ($sendmailConfig['smtp_server'] ?? ''),
        'port' => (int)(getenv('METALGEST_SMTP_PORT') ?: ($sendmailConfig['smtp_port'] ?? 587)),
        'secure' => strtolower(getenv('METALGEST_SMTP_SECURE') ?: ($sendmailConfig['smtp_ssl'] ?? 'tls')),
        'user' => getenv('METALGEST_SMTP_USER') ?: ($sendmailConfig['auth_username'] ?? ''),
        'password' => getenv('METALGEST_SMTP_PASS') ?: ($sendmailConfig['auth_password'] ?? ''),
        'from' => getenv('METALGEST_MAIL_FROM')
            ?: ($sendmailConfig['force_sender'] ?? ($sendmailConfig['auth_username'] ?? ini_get('sendmail_from')))
    ];
}

function api_send_client_code_email($email, $code, $purpose, $resetToken = null) {
    $smtp = api_mail_smtp_settings();
    if (!$smtp['host'] || !$smtp['user'] || !$smtp['password']
        || !$smtp['from'] || !filter_var($smtp['from'], FILTER_VALIDATE_EMAIL)
        || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        error_log('MetalGest email delivery failed: SMTP configuration is incomplete or the address is invalid.');
        return false;
    }
    $registration = $purpose === 'registro';
    $heading = $registration ? 'Verificá tu correo' : 'Código para recuperar tu contraseña';
    $message = $registration
        ? 'Ingresá este código en MetalGest para confirmar tu correo y completar el registro.'
        : 'Usá este código para cambiar tu contraseña. Podés tocar el botón Crear contraseña nueva para abrir el formulario; allí también se te pedirá este código de seis dígitos.';
    $safeHeading = htmlspecialchars($heading, ENT_QUOTES, 'UTF-8');
    $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    $safeCode = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
    $resetButton = '';
    if ($purpose === 'recuperar' && $resetToken) {
        $resetUrl = api_base_url() . '/?restablecer_cliente=' . rawurlencode($resetToken);
        $safeResetUrl = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');
        $resetButton = '<p style="margin:24px 0"><a href="' . $safeResetUrl . '" style="display:inline-block;padding:13px 20px;background:#d55c27;color:#fff;text-decoration:none;font-weight:bold">Crear contraseña nueva</a></p>';
    }
    $html = '<div style="max-width:560px;margin:24px auto;background:#fff;border-top:5px solid #d55c27;font-family:Arial,sans-serif;color:#202426">'
        . '<div style="padding:22px 28px;background:#15191b;color:#fff;font-weight:bold">METALÚRGICA SAN JORGE / METALGEST</div>'
        . '<div style="padding:28px"><h1 style="font-size:23px">' . $safeHeading . '</h1>'
        . '<p style="line-height:1.6">' . $safeMessage . '</p>'
        . '<p style="margin:26px 0;padding:16px;background:#f4f5f5;color:#d55c27;font-size:32px;letter-spacing:6px;font-weight:bold;text-align:center">' . $safeCode . '</p>'
        . $resetButton
        . '<p style="font-size:12px;color:#687276">El código vence en 10 minutos y se usa una sola vez. Si no pediste esta acción, ignorá este correo.</p></div>'
        . '<div style="padding:15px 28px;background:#f4f5f5;color:#687276;font-size:11px">MetalGest · Gestión industrial</div></div>';
    $plainSubject = $heading . ' | MetalGest';
    require_once __DIR__ . '/../../vendor/phpmailer/phpmailer/src/Exception.php';
    require_once __DIR__ . '/../../vendor/phpmailer/phpmailer/src/PHPMailer.php';
    require_once __DIR__ . '/../../vendor/phpmailer/phpmailer/src/SMTP.php';
    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->CharSet = 'UTF-8';
        $mail->isSMTP();
        $mail->Host = $smtp['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $smtp['user'];
        $mail->Password = $smtp['password'];
        $mail->Port = $smtp['port'];
        if ($smtp['secure'] === 'none') {
            $mail->SMTPAutoTLS = false;
            $mail->SMTPSecure = false;
        } else {
            $mail->SMTPSecure = $smtp['secure'];
        }
        $mail->Timeout = 15;
        $mail->setFrom($smtp['from'], 'MetalGest');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = $plainSubject;
        $mail->Body = $html;
        $mail->AltBody = trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html)));
        return $mail->send();
    } catch (Throwable $e) {
        $error = str_replace([$smtp['password'], $smtp['user']], '[redacted]', $mail->ErrorInfo ?? $e->getMessage());
        error_log('MetalGest email delivery failed: ' . $error);
        return false;
    }
}

function api_send_new_chat_message_email($email) {
    $smtp = api_mail_smtp_settings();
    if (!$smtp['host'] || !$smtp['user'] || !$smtp['password'] || !$smtp['from']
        || !filter_var($smtp['from'], FILTER_VALIDATE_EMAIL) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        error_log('MetalGest chat notification not sent: SMTP configuration is incomplete or the address is invalid.');
        return false;
    }
    require_once __DIR__ . '/../../vendor/phpmailer/phpmailer/src/Exception.php';
    require_once __DIR__ . '/../../vendor/phpmailer/phpmailer/src/PHPMailer.php';
    require_once __DIR__ . '/../../vendor/phpmailer/phpmailer/src/SMTP.php';
    $mail = null;
    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->CharSet = 'UTF-8';
        $mail->isSMTP();
        $mail->Host = $smtp['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $smtp['user'];
        $mail->Password = $smtp['password'];
        $mail->Port = $smtp['port'];
        if ($smtp['secure'] === 'none') {
            $mail->SMTPAutoTLS = false;
            $mail->SMTPSecure = false;
        } else {
            $mail->SMTPSecure = $smtp['secure'];
        }
        $mail->Timeout = 15;
        $mail->setFrom($smtp['from'], 'MetalGest');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Tienes un nuevo mensaje | MetalGest';
        $mail->Body = '<div style="max-width:560px;margin:24px auto;background:#17191b;border-top:4px solid #ff6e20;font-family:Arial,sans-serif;color:#f1f3f5">'
            . '<div style="padding:22px 28px;background:#101213;color:#fff;font-weight:bold">METALÚRGICA SAN JORGE / METALGEST</div>'
            . '<div style="padding:28px"><p style="font-size:17px;line-height:1.6">Tienes un nuevo mensaje. Entra a la página para verlo</p></div></div>';
        $mail->AltBody = 'Tienes un nuevo mensaje. Entra a la página para verlo';
        return $mail->send();
    } catch (Throwable $e) {
        $error = str_replace([$smtp['password'], $smtp['user']], '[redacted]', $mail instanceof \PHPMailer\PHPMailer\PHPMailer ? $mail->ErrorInfo : $e->getMessage());
        error_log('MetalGest chat notification failed: ' . $error);
        return false;
    }
}

function api_date($value) {
    return $value === '' || $value === null ? null : $value;
}

function api_chat_require_staff() {
    $user = $_SESSION['metalgest_user'] ?? null;
    $role = api_normalize_role($user['rol'] ?? '');
    $allowedRoles = ['administrador', 'administrador_global', 'gerencia', 'administracion', 'supervisor_administracion'];
    if (!$user || !in_array($role, $allowedRoles, true)) {
        api_json(['success' => false, 'message' => 'Iniciá sesión con una cuenta autorizada para acceder al chat.'], 401);
    }
    return $user;
}

function api_chat_require_deleter() {
    $user = $_SESSION['metalgest_user'] ?? null;
    $role = api_normalize_role($user['rol'] ?? '');
    if (!$user || ($user['tipo'] ?? '') === 'cliente' || $role !== 'administrador_global') {
        api_json(['success' => false, 'message' => 'Solo el administrador global puede eliminar chats.'], 403);
    }
    return $user;
}

function api_chat_require_user() {
    $user = $_SESSION['metalgest_user'] ?? null;
    if (!$user) {
        api_json(['success' => false, 'message' => 'Iniciá sesión para acceder al chat.'], 401);
    }
    if (($user['tipo'] ?? '') === 'cliente') {
        // Un cliente baneado o desactivado pierde el acceso aunque su sesión siga abierta.
        $stmt = api_db()->prepare('SELECT activo, eliminado FROM cliente WHERE id_cliente = :id_cliente LIMIT 1');
        $stmt->execute([':id_cliente' => (int)($user['id_cliente'] ?? 0)]);
        $cliente = $stmt->fetch();
        if (!$cliente || (int)$cliente['eliminado'] === 1 || (int)$cliente['activo'] !== 1) {
            api_clear_session();
            api_json(['success' => false, 'message' => 'Tu cuenta no tiene acceso. Contactá a la empresa.'], 401);
        }
    }
    return $user;
}

function api_require_client_banner() {
    $user = $_SESSION['metalgest_user'] ?? null;
    $role = api_normalize_role($user['rol'] ?? '');
    if (!$user || ($user['tipo'] ?? '') !== 'empleado') {
        api_json(['success' => false, 'message' => 'Iniciá sesión con una cuenta de empleado.'], 401);
    }
    if (!in_array($role, ['administrador', 'administrador_global'], true)) {
        api_json(['success' => false, 'message' => 'Solo el administrador y el administrador global pueden banear clientes.'], 403);
    }
    return $user;
}

function api_require_employee_roles(array $roles) {
    $user = $_SESSION['metalgest_user'] ?? null;
    if (!$user || ($user['tipo'] ?? '') !== 'empleado') {
        api_json(['success' => false, 'message' => 'Iniciá sesión con una cuenta de empleado.'], 401);
    }
    global $action;
    $role = api_normalize_role($user['rol'] ?? '');
    if ($role === 'supervisor_administracion') {
        return $user;
    }
    $supervisorActions = [
        'clientes_list', 'clientes_create', 'clientes_update', 'clientes_delete',
        'pedidos_list', 'pedidos_create',
        'ordenes_list', 'ordenes_create', 'orden_update_status',
        'produccion_list', 'produccion_register', 'maquinas_list', 'mantenimiento_report',
        'mantenimientos_list', 'mantenimiento_preventivo_create', 'mantenimiento_update',
        'materiales_list', 'materiales_create', 'materiales_update', 'materiales_delete',
        'deposito_movimientos_list', 'deposito_movimiento_create',
        'proveedores_list', 'proveedores_create', 'compras_list', 'compras_create',
        'calidad_list', 'calidad_register',
        'supervisor_record_update', 'supervisor_record_delete'
    ];
    if (preg_match('/^supervisor_(produccion|mantenimiento|deposito|compras|calidad)$/', $role)
        && in_array($action, $supervisorActions, true)) {
        return $user;
    }
    $allowedRoles = array_map('api_normalize_role', $roles);
    $isAdministrator = in_array($role, ['administrador', 'administrador_global'], true);
    $isSupervisorForAllowedModule = preg_match('/^supervisor_(.+)$/', $role, $matches)
        && in_array($matches[1], $allowedRoles, true);
    if (!$isAdministrator && !$isSupervisorForAllowedModule && !in_array($role, $allowedRoles, true)) {
        api_json(['success' => false, 'message' => 'Tu cuenta no tiene permiso para esta acción.'], 403);
    }
    return $user;
}

function api_normalize_role($role) {
    $role = strtr((string)$role, [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n',
        'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ñ' => 'N'
    ]);
    $asciiRole = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $role);
    $normalizedRole = strtolower($asciiRole === false ? (string)$role : $asciiRole);
    return trim(preg_replace('/[^a-z0-9]+/', '_', $normalizedRole), '_');
}

function api_employee_role($idRole) {
    $statement = api_db()->prepare("SELECT id_rol, nombre FROM rol WHERE id_rol = :id_rol AND LOWER(nombre) <> 'cliente'");
    $statement->execute([':id_rol' => $idRole]);
    $role = $statement->fetch();
    if (!$role) {
        api_json(['success' => false, 'message' => 'Seleccioná un rol válido para el empleado.'], 422);
    }
    return $role;
}

function api_employee_is_administrator($roleName) {
    return in_array(api_normalize_role($roleName), ['administrador', 'administrador_global'], true);
}

function api_is_administration_supervisor() {
    $user = $_SESSION['metalgest_user'] ?? null;
    return api_normalize_role($user['rol'] ?? '') === 'supervisor_administracion';
}

function api_validate_supervisor_employee_role($roleName) {
    if (api_is_administration_supervisor() && api_employee_role_is_protected($roleName)) {
        api_json(['success' => false, 'message' => 'El supervisor de Administración no puede asignar roles de Administrador o Gerencia.'], 403);
    }
}

function api_employee_role_is_protected($roleName) {
    return in_array(
        api_normalize_role($roleName),
        ['administrador', 'administrador_global', 'gerencia'],
        true
    );
}

function api_department_supervisor_module() {
    $user = $_SESSION['metalgest_user'] ?? null;
    if (!$user || ($user['tipo'] ?? '') !== 'empleado') {
        api_json(['success' => false, 'message' => 'Iniciá sesión con una cuenta de empleado.'], 401);
    }
    $role = api_normalize_role($user['rol'] ?? '');
    if (!preg_match('/^supervisor_(administracion|produccion|mantenimiento|deposito|compras|calidad)$/', $role, $matches)) {
        api_json(['success' => false, 'message' => 'Esta acción está reservada a supervisores de área.'], 403);
    }
    return $matches[1];
}

function api_sync_production_order(PDO $pdo, int $idOrden) {
    $latest = $pdo->prepare('SELECT avance FROM produccion WHERE id_orden = :id_orden ORDER BY id_produccion DESC LIMIT 1');
    $latest->execute([':id_orden' => $idOrden]);
    $avance = $latest->fetchColumn();
    $estado = $avance === false ? 'Pendiente' : ((int)$avance >= 100 ? 'Finalizada' : 'En produccion');
    $updateOrder = $pdo->prepare(
        'UPDATE orden_trabajo
         SET estado = :estado, fecha_finalizacion = CASE WHEN :estado_fin = "Finalizada" THEN COALESCE(fecha_finalizacion, CURDATE()) ELSE NULL END
         WHERE id_orden = :id_orden'
    );
    $updateOrder->execute([':estado' => $estado, ':estado_fin' => $estado, ':id_orden' => $idOrden]);
    $updatePedido = $pdo->prepare(
        'UPDATE pedido p INNER JOIN orden_trabajo ot ON ot.id_pedido = p.id_pedido
         SET p.estado = :estado WHERE ot.id_orden = :id_orden'
    );
    $updatePedido->execute([':estado' => $estado, ':id_orden' => $idOrden]);
}

function api_adjust_stock(PDO $pdo, int $idMaterial, float $delta) {
    $statement = $pdo->prepare('SELECT stock FROM material WHERE id_material = :id_material FOR UPDATE');
    $statement->execute([':id_material' => $idMaterial]);
    $stock = $statement->fetchColumn();
    if ($stock === false) {
        throw new InvalidArgumentException('No se encontró el material relacionado.');
    }
    $updatedStock = (float)$stock + $delta;
    if ($updatedStock < 0) {
        throw new InvalidArgumentException('El cambio dejaría el stock en negativo; revisá los movimientos asociados.');
    }
    $update = $pdo->prepare('UPDATE material SET stock = :stock WHERE id_material = :id_material');
    $update->execute([':stock' => $updatedStock, ':id_material' => $idMaterial]);
}

function api_require_client() {
    $user = api_chat_require_user();
    if (($user['tipo'] ?? '') !== 'cliente' || (int)($user['id_cliente'] ?? 0) <= 0) {
        api_json(['success' => false, 'message' => 'Esta función es exclusiva de una cuenta de cliente.'], 403);
    }
    return $user;
}

function api_clear_session() {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $cookie = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $cookie['path'], $cookie['domain'], $cookie['secure'], $cookie['httponly']);
    }
    session_destroy();
}

function api_chat_upload_directory() {
    return __DIR__ . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . 'chat_uploads';
}

function api_chat_store_uploads() {
    if (empty($_FILES['adjuntos'])) {
        return [];
    }
    $files = $_FILES['adjuntos'];
    if (!isset($files['name'], $files['tmp_name'], $files['error'], $files['size'])) {
        api_json(['success' => false, 'message' => 'No se pudieron leer los archivos adjuntos.'], 400);
    }
    $names = is_array($files['name']) ? $files['name'] : [$files['name']];
    $temporaryPaths = is_array($files['tmp_name']) ? $files['tmp_name'] : [$files['tmp_name']];
    $errors = is_array($files['error']) ? $files['error'] : [$files['error']];
    $sizes = is_array($files['size']) ? $files['size'] : [$files['size']];
    if (count($names) > 5) {
        api_json(['success' => false, 'message' => 'Podés adjuntar hasta 5 archivos por mensaje.'], 413);
    }
    $allowed = [
        'pdf' => ['application/pdf'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'webp' => ['image/webp'],
        'dxf' => ['application/dxf', 'application/x-dxf', 'image/vnd.dxf', 'text/plain', 'application/octet-stream'],
        'dwg' => ['application/acad', 'application/x-acad', 'application/x-dwg', 'image/vnd.dwg', 'application/octet-stream']
    ];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $validated = [];
    $totalSize = 0;
    foreach ($names as $index => $name) {
        $error = (int)$errors[$index];
        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($error !== UPLOAD_ERR_OK) {
            api_json(['success' => false, 'message' => 'Hubo un error al subir uno de los archivos.'], 400);
        }
        $size = (int)$sizes[$index];
        $extension = strtolower(pathinfo((string)$name, PATHINFO_EXTENSION));
        if ($size <= 0 || $size > 10 * 1024 * 1024) {
            api_json(['success' => false, 'message' => 'Cada archivo debe pesar como máximo 10 MB.'], 413);
        }
        $totalSize += $size;
        if ($totalSize > 25 * 1024 * 1024) {
            api_json(['success' => false, 'message' => 'El total de archivos por mensaje no puede superar 25 MB.'], 413);
        }
        if (!isset($allowed[$extension]) || !is_uploaded_file($temporaryPaths[$index])) {
            api_json(['success' => false, 'message' => 'Tipo de archivo no permitido. Usá PDF, JPG, PNG, WebP, DXF o DWG.'], 415);
        }
        $mime = $finfo->file($temporaryPaths[$index]);
        if (!in_array($mime, $allowed[$extension], true)) {
            api_json(['success' => false, 'message' => 'El contenido de uno de los archivos no coincide con su extensión.'], 415);
        }
        $validated[] = [
            'path' => $temporaryPaths[$index],
            'nombre_original' => basename(str_replace('\\', '/', (string)$name)),
            'extension' => $extension,
            'tipo_mime' => $mime,
            'tamano' => $size
        ];
    }
    $directory = api_chat_upload_directory();
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        throw new RuntimeException('No se pudo preparar el almacenamiento de archivos.');
    }
    if (!is_writable($directory)) {
        throw new RuntimeException('El almacenamiento de archivos no tiene permisos de escritura.');
    }
    $stored = [];
    try {
        foreach ($validated as $file) {
            $key = bin2hex(random_bytes(24)) . '.' . $file['extension'];
            $destination = $directory . DIRECTORY_SEPARATOR . $key;
            if (!move_uploaded_file($file['path'], $destination)) {
                throw new RuntimeException('No se pudo guardar uno de los archivos adjuntos.');
            }
            $stored[] = [
                'nombre_original' => $file['nombre_original'],
                'clave_archivo' => $key,
                'tipo_mime' => $file['tipo_mime'],
                'tamano' => $file['tamano']
            ];
        }
    } catch (Throwable $e) {
        api_chat_remove_uploads($stored);
        throw $e;
    }
    return $stored;
}

function api_chat_remove_uploads(array $uploads) {
    $directory = api_chat_upload_directory();
    foreach ($uploads as $upload) {
        $key = $upload['clave_archivo'] ?? '';
        if (preg_match('/\A[a-f0-9]{48}\.(pdf|jpg|jpeg|png|webp|dxf|dwg)\z/', $key)) {
            $path = $directory . DIRECTORY_SEPARATOR . $key;
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
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
$clienteDAO = new ClienteDAO();
$chatDAO = new ChatDAO();
$pedidoSeguimientoDAO = new PedidoSeguimientoDAO();

if ($action === 'health') {
    api_json(['success' => true, 'app' => 'MetalGest']);
}

if ($action === 'logout') {
    api_clear_session();
    api_json(['success' => true]);
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
    $usuarioLogin = api_required('usuario');
    $contrasenaLogin = api_required('contrasena');
    $stmt = api_db()->prepare(
        'SELECT u.id_usuario, u.nombre, u.apellido, u.usuario, u.contrasena, u.id_cliente, r.nombre AS rol
         FROM usuario u
         INNER JOIN rol r ON r.id_rol = u.id_rol
         WHERE u.usuario = :usuario AND u.id_cliente IS NULL
         LIMIT 1'
    );
    $stmt->execute([':usuario' => $usuarioLogin]);
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
    $_SESSION['metalgest_user'] = [
        'tipo' => 'empleado',
        'id_usuario' => (int)$user['id_usuario'],
        'rol' => $user['rol']
    ];
    unset($user['contrasena']);
    api_json(['success' => true, 'usuario' => $user]);
}

if ($action === 'empleados_roles_list') {
    api_require_employee_roles(['administrador', 'gerencia', 'administracion']);
    $roles = api_db()->query("SELECT id_rol, nombre FROM rol WHERE LOWER(nombre) <> 'cliente' ORDER BY nombre")->fetchAll();
    if (api_is_administration_supervisor()) {
        $roles = array_values(array_filter($roles, static function ($role) {
            return !in_array(api_normalize_role($role['nombre']), ['administrador', 'administrador_global', 'gerencia'], true);
        }));
    }
    api_json($roles);
}

if ($action === 'empleados_list') {
    api_require_employee_roles(['administrador', 'gerencia', 'administracion']);
    $employees = api_db()->query(
        "SELECT u.id_usuario, u.nombre, u.apellido, u.usuario, u.id_rol, r.nombre AS rol
         FROM usuario u
         INNER JOIN rol r ON r.id_rol = u.id_rol
         WHERE u.id_cliente IS NULL
         ORDER BY u.apellido, u.nombre, u.id_usuario"
    )->fetchAll();
    api_json($employees);
}

if ($action === 'empleados_create') {
    api_require_employee_roles(['administrador', 'gerencia', 'administracion']);
    try {
        $nombre = api_required('nombre');
        $apellido = api_input('apellido', '');
        $usuario = api_required('usuario');
        $contrasena = api_required('contrasena');
        if (strlen($contrasena) < 4) {
            api_json(['success' => false, 'message' => 'La contraseña debe tener al menos 4 caracteres.'], 422);
        }
        $role = api_employee_role(api_int('id_rol'));
        api_validate_supervisor_employee_role($role['nombre']);
        $statement = api_db()->prepare(
            'INSERT INTO usuario (nombre, apellido, usuario, contrasena, id_rol, id_cliente)
             VALUES (:nombre, :apellido, :usuario, :contrasena, :id_rol, NULL)'
        );
        $statement->execute([
            ':nombre' => $nombre,
            ':apellido' => $apellido,
            ':usuario' => $usuario,
            ':contrasena' => password_hash($contrasena, PASSWORD_DEFAULT),
            ':id_rol' => (int)$role['id_rol']
        ]);
        api_json(['success' => true, 'id_usuario' => (int)api_db()->lastInsertId()]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            api_json(['success' => false, 'message' => 'Ese nombre de usuario ya está en uso.'], 409);
        }
        error_log('MetalGest employee creation failed: ' . $e->getMessage());
        api_json(['success' => false, 'message' => 'No se pudo crear el empleado.'], 500);
    } catch (Throwable $e) {
        api_json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

if ($action === 'empleados_update') {
    api_require_employee_roles(['administrador', 'gerencia', 'administracion']);
    $idUsuario = api_int('id_usuario');
    if ($idUsuario <= 0) {
        api_json(['success' => false, 'message' => 'Seleccioná un empleado válido.'], 422);
    }
    try {
        $role = api_employee_role(api_int('id_rol'));
        api_validate_supervisor_employee_role($role['nombre']);
        $fields = [
            'nombre' => api_required('nombre'),
            'apellido' => api_input('apellido', ''),
            'usuario' => api_required('usuario'),
            'id_rol' => (int)$role['id_rol'],
            'id_usuario' => $idUsuario
        ];
        $password = api_input('contrasena', '');
        if ($password !== '' && strlen($password) < 4) {
            api_json(['success' => false, 'message' => 'La contraseña debe tener al menos 4 caracteres.'], 422);
        }
        $pdo = api_db();
        $pdo->beginTransaction();
        $currentStatement = $pdo->prepare(
            'SELECT u.id_usuario, u.id_rol, r.nombre AS rol
             FROM usuario u INNER JOIN rol r ON r.id_rol = u.id_rol
             WHERE u.id_usuario = :id_usuario AND u.id_cliente IS NULL
             FOR UPDATE'
        );
        $currentStatement->execute([':id_usuario' => $idUsuario]);
        $currentEmployee = $currentStatement->fetch();
        if (!$currentEmployee) {
            $pdo->rollBack();
            api_json(['success' => false, 'message' => 'No se encontró el empleado.'], 404);
        }
        if (api_is_administration_supervisor() && api_employee_role_is_protected($currentEmployee['rol'])) {
            $pdo->rollBack();
            api_json(['success' => false, 'message' => 'El supervisor de Administración no puede modificar cuentas de Administrador o Gerencia.'], 403);
        }
        $isSelf = $idUsuario === (int)($_SESSION['metalgest_user']['id_usuario'] ?? 0);
        if (api_is_administration_supervisor() && $isSelf && (int)$role['id_rol'] !== (int)$currentEmployee['id_rol']) {
            $pdo->rollBack();
            api_json(['success' => false, 'message' => 'No podés cambiar tu propio rol.'], 409);
        }
        $isChangingOwnRole = $isSelf && (int)$role['id_rol'] !== (int)$currentEmployee['id_rol'];
        if ($isChangingOwnRole && !api_employee_is_administrator($role['nombre'])) {
            $pdo->rollBack();
            api_json(['success' => false, 'message' => 'No podés quitarte el rol de administrador de tu propia cuenta.'], 409);
        }
        if (api_employee_is_administrator($currentEmployee['rol']) && !api_employee_is_administrator($role['nombre'])) {
            $countStatement = $pdo->query(
                "SELECT COUNT(*) FROM usuario u INNER JOIN rol r ON r.id_rol = u.id_rol
                 WHERE u.id_cliente IS NULL AND LOWER(r.nombre) IN ('administrador', 'administrador global')"
            );
            if ((int)$countStatement->fetchColumn() <= 1) {
                $pdo->rollBack();
                api_json(['success' => false, 'message' => 'No se puede quitar el rol al último administrador.'], 409);
            }
        }
        $query = 'UPDATE usuario SET nombre = :nombre, apellido = :apellido, usuario = :usuario, id_rol = :id_rol';
        if ($password !== '') {
            $query .= ', contrasena = :contrasena';
            $fields['contrasena'] = password_hash($password, PASSWORD_DEFAULT);
        }
        $query .= ' WHERE id_usuario = :id_usuario AND id_cliente IS NULL';
        $statement = $pdo->prepare($query);
        $statement->execute($fields);
        $pdo->commit();
        api_json(['success' => true]);
    } catch (PDOException $e) {
        if (api_db()->inTransaction()) {
            api_db()->rollBack();
        }
        if ($e->getCode() === '23000') {
            api_json(['success' => false, 'message' => 'Ese nombre de usuario ya está en uso.'], 409);
        }
        error_log('MetalGest employee update failed: ' . $e->getMessage());
        api_json(['success' => false, 'message' => 'No se pudo actualizar el empleado.'], 500);
    } catch (Throwable $e) {
        if (api_db()->inTransaction()) {
            api_db()->rollBack();
        }
        api_json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

if ($action === 'empleados_delete') {
    api_require_employee_roles(['administrador', 'gerencia', 'administracion']);
    $idUsuario = api_int('id_usuario');
    if ($idUsuario <= 0) {
        api_json(['success' => false, 'message' => 'Seleccioná un empleado válido.'], 422);
    }
    if ($idUsuario === (int)($_SESSION['metalgest_user']['id_usuario'] ?? 0)) {
        api_json(['success' => false, 'message' => 'No podés eliminar tu propia cuenta.'], 409);
    }
    try {
        $pdo = api_db();
        $pdo->beginTransaction();
        $employeeStatement = $pdo->prepare(
            'SELECT r.nombre AS rol FROM usuario u INNER JOIN rol r ON r.id_rol = u.id_rol
             WHERE u.id_usuario = :id_usuario AND u.id_cliente IS NULL FOR UPDATE'
        );
        $employeeStatement->execute([':id_usuario' => $idUsuario]);
        $employee = $employeeStatement->fetch();
        if (!$employee) {
            $pdo->rollBack();
            api_json(['success' => false, 'message' => 'No se encontró el empleado.'], 404);
        }
        if (api_is_administration_supervisor() && api_employee_role_is_protected($employee['rol'])) {
            $pdo->rollBack();
            api_json(['success' => false, 'message' => 'El supervisor de Administración no puede eliminar cuentas de Administrador o Gerencia.'], 403);
        }
        if (api_employee_is_administrator($employee['rol'])) {
            $administratorCount = (int)$pdo->query(
                "SELECT COUNT(*) FROM usuario u INNER JOIN rol r ON r.id_rol = u.id_rol
                 WHERE u.id_cliente IS NULL AND LOWER(r.nombre) IN ('administrador', 'administrador global')"
            )->fetchColumn();
            if ($administratorCount <= 1) {
                $pdo->rollBack();
                api_json(['success' => false, 'message' => 'No se puede eliminar al último administrador.'], 409);
            }
        }
        $statement = $pdo->prepare('DELETE FROM usuario WHERE id_usuario = :id_usuario AND id_cliente IS NULL');
        $statement->execute([':id_usuario' => $idUsuario]);
        $pdo->commit();
        api_json(['success' => true]);
    } catch (PDOException $e) {
        if (api_db()->inTransaction()) {
            api_db()->rollBack();
        }
        error_log('MetalGest employee deletion failed: ' . $e->getMessage());
        api_json(['success' => false, 'message' => 'No se puede eliminar un empleado con registros asociados.'], 409);
    } catch (Throwable $e) {
        if (api_db()->inTransaction()) {
            api_db()->rollBack();
        }
        api_json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

if ($action === 'cliente_login') {
    $email = strtolower(api_required('email'));
    $contrasena = api_required('contrasena');
    $stmt = api_db()->prepare(
        'SELECT id_cliente, razon_social, email, contrasena, activo, eliminado
         FROM cliente
         WHERE LOWER(email) = :email
         LIMIT 1'
    );
    $stmt->execute([':email' => $email]);
    $cliente = $stmt->fetch();
    if (!$cliente) {
        api_json(['success' => false, 'message' => 'Correo o contraseña incorrectos.'], 401);
    }
    if ((int)$cliente['eliminado'] === 1) {
        api_json(['success' => false, 'message' => 'Esta cuenta fue eliminada.'], 403);
    }
    if ((int)$cliente['activo'] !== 1) {
        api_json(['success' => false, 'message' => 'Esta cuenta está desactivada.'], 403);
    }
    if (!$cliente['contrasena'] || !password_verify($contrasena, $cliente['contrasena'])) {
        api_json(['success' => false, 'message' => 'Correo o contraseña incorrectos.'], 401);
    }
    session_regenerate_id(true);
    $_SESSION['metalgest_user'] = [
        'tipo' => 'cliente',
        'id_cliente' => (int)$cliente['id_cliente']
    ];
    api_json([
        'success' => true,
        'cliente' => [
            'tipo' => 'cliente',
            'id_cliente' => (int)$cliente['id_cliente'],
            'razon_social' => $cliente['razon_social'],
            'email' => $cliente['email']
        ]
    ]);
}

if ($action === 'cliente_account_action') {
    $user = api_chat_require_user();
    $idCliente = api_int('id_cliente');
    $accion = api_required('accion');
    $contrasena = api_required('contrasena');
    if (($user['tipo'] ?? '') !== 'cliente' || (int)$user['id_cliente'] !== $idCliente) {
        api_json(['success' => false, 'message' => 'No tenés acceso a esta cuenta.'], 403);
    }
    if ($idCliente <= 0 || !in_array($accion, ['desactivar', 'eliminar'], true)) {
        api_json(['success' => false, 'message' => 'Acción de cuenta inválida.'], 422);
    }
    $pdo = api_db();
    $stmt = $pdo->prepare('SELECT contrasena, eliminado FROM cliente WHERE id_cliente = :id_cliente LIMIT 1');
    $stmt->execute([':id_cliente' => $idCliente]);
    $cliente = $stmt->fetch();
    if (!$cliente || (int)$cliente['eliminado'] === 1 || !$cliente['contrasena'] || !password_verify($contrasena, $cliente['contrasena'])) {
        api_json(['success' => false, 'message' => 'No se pudo confirmar la contraseña.'], 401);
    }
    if ($accion === 'desactivar') {
        $update = $pdo->prepare(
            'UPDATE cliente
             SET activo = 0, token_verificacion = NULL, token_verificacion_vence = NULL,
                 token_proposito = NULL, token_cambio_contrasena = NULL, token_cambio_contrasena_vence = NULL
             WHERE id_cliente = :id_cliente'
        );
        $update->execute([':id_cliente' => $idCliente]);
        api_clear_session();
        api_json(['success' => true, 'message' => 'Cuenta desactivada.']);
    }
    $update = $pdo->prepare(
        'UPDATE cliente
         SET activo = 0, eliminado = 1, email = NULL, contrasena = NULL,
             email_verificado = 0, token_verificacion = NULL, token_verificacion_vence = NULL, token_proposito = NULL,
             token_cambio_contrasena = NULL, token_cambio_contrasena_vence = NULL
         WHERE id_cliente = :id_cliente'
    );
    $update->execute([':id_cliente' => $idCliente]);
    api_clear_session();
    api_json(['success' => true, 'message' => 'Acceso de la cuenta eliminado. Se conserva su historial asociado.']);
}

if ($action === 'cliente_register') {
    try {
        $razonSocial = api_required('razon_social');
        $email = strtolower(api_required('email'));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            api_json(['success' => false, 'message' => 'Ingresá una dirección de correo válida.'], 422);
        }
        $contrasena = api_required('contrasena');
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $contrasena)) {
            api_json(['success' => false, 'message' => 'La contraseña debe tener 8 caracteres como mínimo, una mayúscula, una minúscula y un número.'], 422);
        }
        $pdo = api_db();
        $existingStmt = $pdo->prepare('SELECT id_cliente, activo, eliminado FROM cliente WHERE LOWER(email) = :email LIMIT 1');
        $existingStmt->execute([':email' => $email]);
        $existing = $existingStmt->fetch();
        if ($existing) {
            if ((int)$existing['activo'] !== 1 || (int)$existing['eliminado'] === 1) {
                api_json(['success' => false, 'message' => 'Esta cuenta está desactivada. Contactá a la empresa.'], 403);
            }
            api_json(['success' => false, 'message' => 'Ese correo ya está registrado. Iniciá sesión o recuperá la contraseña si no la recordás.'], 409);
        }

        $cliente = $pdo->prepare(
            'INSERT INTO cliente (razon_social, cuit, email, direccion, contrasena, email_verificado, token_verificacion, token_verificacion_vence, token_proposito)
             VALUES (:razon_social, :cuit, :email, :direccion, :contrasena, 0, NULL, NULL, NULL)'
        );
        $cliente->execute([
            ':razon_social' => $razonSocial,
            ':cuit' => api_input('cuit', ''),
            ':email' => $email,
            ':direccion' => api_input('direccion', ''),
            ':contrasena' => password_hash($contrasena, PASSWORD_DEFAULT)
        ]);
        $idCliente = (int)$pdo->lastInsertId();
        api_json([
            'success' => true,
            'id_cliente' => $idCliente,
            'message' => 'El registro quedó guardado. Ya podés iniciar sesión con tu correo y contraseña.'
        ]);
    } catch (Throwable $e) {
        if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        api_json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

if ($action === 'cliente_verify') {
    $token = api_required('token');
    $stmt = api_db()->prepare(
        'UPDATE cliente
         SET email_verificado = 1, token_verificacion = NULL, token_verificacion_vence = NULL, token_proposito = NULL,
             token_cambio_contrasena = NULL, token_cambio_contrasena_vence = NULL
         WHERE token_verificacion = :token AND token_verificacion_vence > NOW() AND email_verificado = 0'
    );
    $stmt->execute([':token' => hash('sha256', $token)]);
    if ($stmt->rowCount() !== 1) {
        api_json(['success' => false, 'message' => 'El enlace venció o ya fue utilizado.'], 410);
    }
    api_json(['success' => true, 'message' => 'Correo verificado. El registro quedó confirmado.']);
}

if ($action === 'cliente_verify_code') {
    $email = strtolower(api_required('email'));
    $code = api_required('codigo');
    if (!preg_match('/^\d{6}$/', $code)) {
        api_json(['success' => false, 'message' => 'Ingresá el código de seis dígitos.'], 422);
    }
    $stmt = api_db()->prepare(
        'UPDATE cliente
         SET email_verificado = 1, token_verificacion = NULL, token_verificacion_vence = NULL, token_proposito = NULL
         WHERE LOWER(email) = :email AND token_verificacion = :token AND token_proposito = "registro"
           AND token_verificacion_vence > NOW() AND email_verificado = 0 AND activo = 1 AND eliminado = 0'
    );
    $stmt->execute([':email' => $email, ':token' => hash('sha256', $code)]);
    if ($stmt->rowCount() !== 1) {
        api_json(['success' => false, 'message' => 'El código no es válido o venció.'], 410);
    }
    api_json(['success' => true, 'message' => 'Correo verificado. Ya podés ingresar.']);
}

if ($action === 'cliente_resend_code') {
    $email = strtolower(api_required('email'));
    $purpose = api_required('proposito');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($purpose, ['registro', 'recuperar'], true)) {
        api_json(['success' => false, 'message' => 'No se pudo solicitar un nuevo código.'], 422);
    }

    $pdo = api_db();
    $pdo->beginTransaction();
    $stmt = $pdo->prepare(
        'SELECT id_cliente, email, email_verificado, token_proposito, token_verificacion_vence,
                GREATEST(0, TIMESTAMPDIFF(SECOND, NOW(), token_verificacion_vence)) AS segundos_restantes
         FROM cliente
         WHERE LOWER(email) = :email AND activo = 1 AND eliminado = 0
         LIMIT 1 FOR UPDATE'
    );
    $stmt->execute([':email' => $email]);
    $cliente = $stmt->fetch();
    $isExpectedAccountState = $cliente
        && ($purpose === 'recuperar' || (int)$cliente['email_verificado'] === 0);

    if (!$isExpectedAccountState) {
        $pdo->rollBack();
        if ($purpose === 'recuperar') {
            api_json(['success' => true, 'message' => 'Si existe una cuenta activa con ese correo, enviaremos un nuevo código.']);
        }
        api_json(['success' => false, 'message' => 'No hay un registro pendiente de verificación para ese correo.'], 404);
    }

    $resendThreshold = 600 - CLIENT_CODE_RESEND_COOLDOWN_SECONDS;
    if ($cliente['token_proposito'] === $purpose && (int)$cliente['segundos_restantes'] > $resendThreshold) {
        $retryAfter = (int)$cliente['segundos_restantes'] - $resendThreshold;
        $pdo->rollBack();
        api_json([
            'success' => false,
            'retry_after' => $retryAfter,
            'message' => "Podés pedir otro código en {$retryAfter} segundos."
        ], 429);
    }

    $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $resetToken = $purpose === 'recuperar' ? bin2hex(random_bytes(32)) : null;
    $update = $pdo->prepare(
        'UPDATE cliente
         SET token_verificacion = :token, token_verificacion_vence = DATE_ADD(NOW(), INTERVAL 10 MINUTE), token_proposito = :proposito,
             token_cambio_contrasena = :reset_token, token_cambio_contrasena_vence = IF(:reset_token_check IS NULL, NULL, DATE_ADD(NOW(), INTERVAL 10 MINUTE))
         WHERE id_cliente = :id_cliente'
    );
    $update->execute([
        ':token' => hash('sha256', $code),
        ':proposito' => $purpose,
        ':reset_token' => $resetToken ? hash('sha256', $resetToken) : null,
        ':reset_token_check' => $resetToken,
        ':id_cliente' => $cliente['id_cliente']
    ]);

    if (!api_send_client_code_email($cliente['email'], $code, $purpose, $resetToken)) {
        $pdo->rollBack();
        api_json(['success' => false, 'message' => 'No se pudo enviar el nuevo código. Revisá la configuración del correo.'], 503);
    }

    $pdo->commit();
    api_json([
        'success' => true,
        'message' => $purpose === 'registro'
            ? 'Te enviamos un nuevo código a tu correo.'
            : 'Si existe una cuenta activa con ese correo, enviaremos un nuevo código.'
    ]);
}

if ($action === 'cliente_password_request') {
    $email = strtolower(api_required('email'));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        api_json(['success' => false, 'message' => 'Ingresá un correo válido.'], 422);
    }
    $pdo = api_db();
    $stmt = $pdo->prepare(
           'SELECT id_cliente, email, email_verificado FROM cliente
            WHERE LOWER(email) = :email AND activo = 1 AND eliminado = 0
         LIMIT 1'
    );
    $stmt->execute([':email' => $email]);
    $cliente = $stmt->fetch();
    if (!$cliente) {
        error_log('MetalGest password recovery: no active account matched; no email attempted.');
    }
    if ($cliente) {
        error_log(
            'MetalGest password recovery: active account matched; verification='
            . ((int)$cliente['email_verificado'] === 1 ? 'verified' : 'pending')
            . '.'
        );
        $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $purpose = 'recuperar';
        $resetToken = bin2hex(random_bytes(32));
        $pdo->beginTransaction();
        $update = $pdo->prepare(
            'UPDATE cliente
             SET token_verificacion = :token, token_verificacion_vence = DATE_ADD(NOW(), INTERVAL 10 MINUTE), token_proposito = :proposito,
                 token_cambio_contrasena = :reset_token, token_cambio_contrasena_vence = IF(:reset_token_check IS NULL, NULL, DATE_ADD(NOW(), INTERVAL 10 MINUTE))
             WHERE id_cliente = :id_cliente'
        );
        $update->execute([
            ':token' => hash('sha256', $code),
            ':proposito' => $purpose,
            ':reset_token' => $resetToken ? hash('sha256', $resetToken) : null,
            ':reset_token_check' => $resetToken,
            ':id_cliente' => $cliente['id_cliente']
        ]);
        if (!api_send_client_code_email($cliente['email'], $code, $purpose, $resetToken)) {
            $pdo->rollBack();
            api_json(['success' => false, 'message' => 'No se pudo enviar el código. Revisá la configuración del correo.'], 503);
        }
        $pdo->commit();
        error_log('MetalGest password recovery: SMTP accepted the message; inbox delivery is not confirmed.');
    }
    api_json(['success' => true, 'message' => 'Si existe una cuenta activa con ese correo, le enviaremos un código de recuperación.']);
}

if ($action === 'cliente_password_reset') {
    $email = strtolower(api_required('email'));
    $code = api_required('codigo');
    $contrasena = api_required('contrasena');
    if (!preg_match('/^\d{6}$/', $code)) {
        api_json(['success' => false, 'message' => 'Ingresá el código de seis dígitos.'], 422);
    }
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $contrasena)) {
        api_json(['success' => false, 'message' => 'La contraseña debe tener 8 caracteres como mínimo, una mayúscula, una minúscula y un número.'], 422);
    }
    $pdo = api_db();
    $pdo->beginTransaction();
    $stmt = $pdo->prepare(
        'SELECT id_cliente FROM cliente
                 WHERE LOWER(email) = :email AND token_verificacion = :token AND token_proposito IN ("recuperar", "registro")
                     AND token_verificacion_vence > NOW() AND activo = 1 AND eliminado = 0
         LIMIT 1 FOR UPDATE'
    );
    $stmt->execute([':email' => $email, ':token' => hash('sha256', $code)]);
    $cliente = $stmt->fetch();
    if (!$cliente) {
        $pdo->rollBack();
        api_json(['success' => false, 'message' => 'El código no es válido o venció.'], 410);
    }
    $update = $pdo->prepare(
        'UPDATE cliente
         SET contrasena = :contrasena, email_verificado = 1, token_verificacion = NULL, token_verificacion_vence = NULL, token_proposito = NULL,
             token_cambio_contrasena = NULL, token_cambio_contrasena_vence = NULL
         WHERE id_cliente = :id_cliente'
    );
    $update->execute([':contrasena' => password_hash($contrasena, PASSWORD_DEFAULT), ':id_cliente' => $cliente['id_cliente']]);
    $pdo->commit();
    api_json(['success' => true, 'message' => 'Contraseña actualizada. Ya podés ingresar.']);
}

if ($action === 'cliente_password_reset_link') {
    $token = api_required('token');
    $code = api_required('codigo');
    $contrasena = api_required('contrasena');
    if (!preg_match('/^\d{6}$/', $code)) {
        api_json(['success' => false, 'message' => 'Ingresa el codigo de seis digitos.'], 422);
    }
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $contrasena)) {
        api_json(['success' => false, 'message' => 'La contraseña debe tener 8 caracteres como mínimo, una mayúscula, una minúscula y un número.'], 422);
    }
    $pdo = api_db();
    $pdo->beginTransaction();
    $stmt = $pdo->prepare(
        'SELECT id_cliente FROM cliente
         WHERE token_cambio_contrasena = :token AND token_cambio_contrasena_vence > NOW()
           AND token_verificacion = :codigo AND token_proposito = "recuperar" AND token_verificacion_vence > NOW()
           AND activo = 1 AND eliminado = 0
         LIMIT 1 FOR UPDATE'
    );
    $stmt->execute([':token' => hash('sha256', $token), ':codigo' => hash('sha256', $code)]);
    $cliente = $stmt->fetch();
    if (!$cliente) {
        $pdo->rollBack();
        api_json(['success' => false, 'message' => 'El enlace venció o ya fue usado.'], 410);
    }
    $update = $pdo->prepare(
        'UPDATE cliente
         SET contrasena = :contrasena, email_verificado = 1, token_verificacion = NULL, token_verificacion_vence = NULL, token_proposito = NULL,
             token_cambio_contrasena = NULL, token_cambio_contrasena_vence = NULL
         WHERE id_cliente = :id_cliente'
    );
    $update->execute([':contrasena' => password_hash($contrasena, PASSWORD_DEFAULT), ':id_cliente' => $cliente['id_cliente']]);
    $pdo->commit();
    api_json(['success' => true, 'message' => 'Contraseña actualizada. Ya podés ingresar.']);
}

if ($action === 'clientes_list') {
    api_require_employee_roles(['gerencia', 'administracion']);
    api_json($clienteDAO->listar());
}

if ($action === 'clientes_create') {
    api_require_employee_roles(['gerencia', 'administracion']);
    try {
        $idCliente = $clienteDAO->crear([
            'razon_social' => api_required('razon_social'),
            'cuit' => api_input('cuit', ''),
            'email' => api_input('email', ''),
            'direccion' => api_input('direccion', '')
        ]);
        api_json(['success' => true, 'id_cliente' => $idCliente]);
    } catch (Throwable $e) {
        api_json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

if ($action === 'clientes_update') {
    api_require_employee_roles(['gerencia', 'administracion']);
    try {
        $idCliente = api_int('id_cliente');
        if ($idCliente <= 0) {
            api_json(['success' => false, 'message' => 'El cliente debe ser valido'], 422);
        }
        $clienteDAO->actualizar([
            'id_cliente' => $idCliente,
            'razon_social' => api_required('razon_social'),
            'cuit' => api_input('cuit', ''),
            'email' => api_input('email', ''),
            'direccion' => api_input('direccion', '')
        ]);
        api_json(['success' => true]);
    } catch (Throwable $e) {
        api_json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

if ($action === 'clientes_delete') {
    api_require_employee_roles(['gerencia', 'administracion']);
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

if ($action === 'clientes_ban') {
    api_require_client_banner();
    $idCliente = api_int('id_cliente');
    if ($idCliente <= 0) {
        api_json(['success' => false, 'message' => 'El cliente debe ser valido'], 422);
    }
    try {
        if (!$clienteDAO->banear($idCliente)) {
            api_json(['success' => false, 'message' => 'No se encontró el cliente o ya estaba baneado.'], 404);
        }
        api_json(['success' => true]);
    } catch (Throwable $e) {
        error_log('MetalGest client ban failed: ' . $e->getMessage());
        api_json(['success' => false, 'message' => 'No se pudo banear al cliente. Intentá nuevamente.'], 500);
    }
}

if ($action === 'chat_list') {
    $user = api_chat_require_user();
    if (($user['tipo'] ?? '') === 'cliente') {
        api_json($chatDAO->listarPorCliente((int)$user['id_cliente']));
    }
    api_chat_require_staff();
    api_json($chatDAO->listar());
}

if ($action === 'chat_messages') {
    $user = api_chat_require_user();
    $idChat = api_int('id_chat');
    if ($idChat <= 0) {
        api_json(['success' => false, 'message' => 'El chat debe ser valido'], 422);
    }
    if (($user['tipo'] ?? '') === 'cliente' && !$chatDAO->perteneceACliente($idChat, (int)$user['id_cliente'])) {
        api_json(['success' => false, 'message' => 'No tenés acceso a esta conversación.'], 403);
    }
    if (($user['tipo'] ?? '') !== 'cliente') {
        api_chat_require_staff();
    }
    api_json($chatDAO->obtenerMensajes($idChat));
}

if ($action === 'chat_attachment') {
    $user = api_chat_require_user();
    $idAdjunto = filter_input(INPUT_GET, 'id_adjunto', FILTER_VALIDATE_INT);
    $adjunto = $idAdjunto ? $chatDAO->obtenerAdjunto((int)$idAdjunto) : false;
    if (!$adjunto) {
        api_json(['success' => false, 'message' => 'No se encontró el archivo adjunto.'], 404);
    }
    if (($user['tipo'] ?? '') === 'cliente') {
        if ((int)$user['id_cliente'] !== (int)$adjunto['id_cliente']) {
            api_json(['success' => false, 'message' => 'No tenés acceso a este archivo.'], 403);
        }
    } else {
        api_chat_require_staff();
    }
    $directory = realpath(api_chat_upload_directory());
    $filePath = $directory
        ? realpath($directory . DIRECTORY_SEPARATOR . basename($adjunto['clave_archivo']))
        : false;
    if (!$directory || !$filePath || dirname($filePath) !== $directory || !is_file($filePath)) {
        api_json(['success' => false, 'message' => 'El archivo adjunto no está disponible.'], 404);
    }
    $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $adjunto['nombre_original']);
    header('Content-Type: ' . $adjunto['tipo_mime']);
    header('Content-Length: ' . filesize($filePath));
    header('Content-Disposition: attachment; filename="' . ($safeName ?: 'adjunto') . '"; filename*=UTF-8\'\'' . rawurlencode($adjunto['nombre_original']));
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-store');
    readfile($filePath);
    exit;
}

if ($action === 'chat_send') {
    try {
        $user = api_chat_require_user();
        if (($user['tipo'] ?? '') !== 'cliente') {
            api_json(['success' => false, 'message' => 'Solo las cuentas de cliente pueden iniciar consultas.'], 403);
        }
        $mensaje = api_input('mensaje', '');
        $uploads = api_chat_store_uploads();
        if (trim($mensaje) === '' && !$uploads) {
            api_json(['success' => false, 'message' => 'Escribí un mensaje o adjuntá al menos un archivo.'], 422);
        }
        $idChat = api_int('id_chat');
        if ($idChat > 0) {
            if (!$chatDAO->perteneceACliente($idChat, (int)$user['id_cliente'])) {
                api_chat_remove_uploads($uploads);
                api_json(['success' => false, 'message' => 'No tenés acceso a esta conversación.'], 403);
            }
            $chatDAO->enviarMensaje([
                'id_chat' => $idChat,
                'id_usuario' => null,
                'autor_tipo' => 'Cliente',
                'mensaje' => $mensaje,
                'adjuntos' => $uploads
            ]);
            api_json(['success' => true, 'id_chat' => $idChat]);
        }
        $nuevoChat = $chatDAO->crearConversacionCliente([
            'id_cliente' => (int)$user['id_cliente'],
            'asunto' => api_input('asunto', 'Consulta a administracion'),
            'mensaje' => $mensaje,
            'adjuntos' => $uploads
        ]);
        api_json(['success' => true, 'id_chat' => $nuevoChat]);
    } catch (Throwable $e) {
        api_chat_remove_uploads($uploads ?? []);
        error_log('MetalGest chat message save failed: ' . $e->getMessage());
        api_json(['success' => false, 'message' => 'No se pudo guardar el mensaje. Intentá nuevamente.'], 500);
    }
}

if ($action === 'chat_reply') {
    try {
        $user = api_chat_require_staff();
        $idChat = api_int('id_chat');
        if ($idChat <= 0) {
            api_json(['success' => false, 'message' => 'El chat debe ser valido'], 422);
        }
        $mensaje = api_input('mensaje', '');
        $uploads = api_chat_store_uploads();
        if (trim($mensaje) === '' && !$uploads) {
            api_json(['success' => false, 'message' => 'Escribí una respuesta o adjuntá al menos un archivo.'], 422);
        }
        $contacto = $chatDAO->obtenerContacto($idChat);
        if (!$contacto) {
            api_chat_remove_uploads($uploads);
            api_json(['success' => false, 'message' => 'No se encontró la conversación.'], 404);
        }
        $chatDAO->enviarMensaje([
            'id_chat' => $idChat,
            'id_usuario' => (int)$user['id_usuario'],
            'autor_tipo' => 'Administracion',
            'mensaje' => $mensaje,
            'adjuntos' => $uploads
        ]);
        $notificacionEnviada = filter_var($contacto['email'] ?? '', FILTER_VALIDATE_EMAIL)
            ? api_send_new_chat_message_email($contacto['email'])
            : false;
        api_json(['success' => true, 'notificacion_enviada' => $notificacionEnviada]);
    } catch (Throwable $e) {
        api_chat_remove_uploads($uploads ?? []);
        error_log('MetalGest chat reply save failed: ' . $e->getMessage());
        api_json(['success' => false, 'message' => 'No se pudo guardar la respuesta. Intentá nuevamente.'], 500);
    }
}

if ($action === 'chat_delete') {
    api_chat_require_deleter();
    $idChat = api_int('id_chat');
    if ($idChat <= 0) {
        api_json(['success' => false, 'message' => 'El chat debe ser valido'], 422);
    }
    try {
        $adjuntos = $chatDAO->eliminar($idChat);
    } catch (Throwable $e) {
        error_log('MetalGest chat delete failed: ' . $e->getMessage());
        api_json(['success' => false, 'message' => 'No se pudo eliminar la conversación. Intentá nuevamente.'], 500);
    }
    if ($adjuntos === null) {
        api_json(['success' => false, 'message' => 'No se encontró la conversación.'], 404);
    }
    api_chat_remove_uploads($adjuntos);
    api_json(['success' => true]);
}

if ($action === 'pedido_create_from_chat') {
    api_chat_require_staff();
    api_json([
        'success' => false,
        'message' => 'El esquema actual no permite vincular un pedido con una conversación. Registralo desde Administración > Pedidos; el vínculo requiere una columna que no existe en la base actual.'
    ], 409);
}

if ($action === 'pedidos_tracking') {
    $user = api_require_client();
    api_json($pedidoSeguimientoDAO->listarParaCliente((int)$user['id_cliente']));
}

if ($action === 'pedidos_list') {
    api_require_employee_roles(['gerencia', 'administracion']);
    api_json($pedidoDAO->listar());
}

if ($action === 'pedidos_create') {
    try {
        $user = api_require_employee_roles(['gerencia', 'administracion']);
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
            'fecha_entrega' => api_date(api_input('fecha_entrega')),
            'id_usuario' => (int)($user['id_usuario'] ?? 0)
        ]);
        api_json(['success' => true, 'id_pedido' => $idPedido]);
    } catch (Throwable $e) {
        api_json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

if ($action === 'ordenes_list') {
    api_require_employee_roles(['gerencia', 'administracion', 'produccion', 'calidad', 'mantenimiento', 'deposito', 'compras']);
    api_json($ordenTrabajoDAO->listar());
}

if ($action === 'ordenes_create') {
    try {
        $user = api_require_employee_roles(['gerencia', 'administracion']);
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
            'id_usuario' => (int)($user['id_usuario'] ?? 0)
        ]);
        api_json(['success' => true, 'id_orden' => $idOrden]);
    } catch (Throwable $e) {
        if ($e instanceof InvalidArgumentException) {
            api_json(['success' => false, 'message' => $e->getMessage()], 409);
        }
        api_json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

if ($action === 'orden_update_status') {
    try {
        $user = api_require_employee_roles(['gerencia', 'administracion', 'produccion']);
        $idOrden = api_int('id_orden');
        if ($idOrden <= 0) {
            api_json(['success' => false, 'message' => 'La orden debe ser vÃ¡lida'], 422);
        }
        $estado = api_required('estado');
        if (!in_array($estado, ['Pendiente', 'En produccion', 'En producción', 'Pausada', 'Demorada', 'Pendiente por inconveniente', 'Lista para entrega', 'Finalizada'], true)) {
            api_json(['success' => false, 'message' => 'El estado del pedido no es válido.'], 422);
        }
        $observaciones = api_input('observaciones', '');
        $actorRole = preg_replace('/^supervisor_/', '', api_normalize_role($user['rol'] ?? ''));
        $ordenTrabajoDAO->actualizarEstado([
            'id_orden' => $idOrden,
            'estado' => $estado,
            'observaciones' => $observaciones,
            'seguimiento_estado' => $estado === 'Finalizada' ? 'Pedido finalizado' : ($estado === 'Lista para entrega' ? 'Listo para su entrega' : $estado),
            'seguimiento_descripcion' => $observaciones !== '' ? $observaciones : 'El área de ' . ucfirst($actorRole) . ' actualizó el estado del pedido a ' . $estado . '.',
            'area' => $actorRole === 'produccion' ? 'Producción' : 'Administración',
            'id_usuario' => (int)($user['id_usuario'] ?? 0)
        ]);
        api_json(['success' => true]);
    } catch (Throwable $e) {
        api_json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

if ($action === 'produccion_list') {
    api_require_employee_roles(['gerencia', 'administracion', 'produccion']);
    api_json($produccionDAO->listar());
}

if ($action === 'produccion_register') {
    $user = api_require_employee_roles(['gerencia', 'produccion']);
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
            'observaciones' => api_input('observaciones', ''),
            'id_usuario' => (int)($user['id_usuario'] ?? 0)
        ]);
        api_json(['success' => true, 'id_produccion' => $idProduccion]);
    } catch (Throwable $e) {
        api_json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

if ($action === 'maquinas_list') {
    api_require_employee_roles(['gerencia', 'produccion', 'mantenimiento']);
    api_json($maquinaDAO->listar());
}

if ($action === 'mantenimiento_report') {
    try {
        $user = api_require_employee_roles(['gerencia', 'produccion', 'mantenimiento']);
        $idMaquina = api_int('id_maquina');
        if ($idMaquina <= 0) {
            api_json(['success' => false, 'message' => 'La mÃ¡quina debe ser vÃ¡lida'], 422);
        }
        $afectaPlazo = api_input('afecta_plazo') === '1';
        $idOrden = api_int('id_orden');
        if ($afectaPlazo && $idOrden <= 0) {
            api_json(['success' => false, 'message' => 'Seleccioná el pedido afectado para informarle una demora.'], 422);
        }
        $problema = api_required('problema');
        $idMantenimiento = $maquinaDAO->reportarFalla([
            'id_maquina' => $idMaquina,
            'id_usuario' => (int)($user['id_usuario'] ?? 0),
            'tipo' => api_input('tipo', 'Correctivo'),
            'problema' => $problema,
            'id_orden' => $idOrden,
            'afecta_plazo' => $afectaPlazo,
            'seguimiento_descripcion' => 'Se informó un problema con una máquina que afecta el plazo del pedido: ' . $problema,
            'area' => preg_replace('/^supervisor_/', '', api_normalize_role($user['rol'] ?? '')) === 'mantenimiento' ? 'Mantenimiento' : 'Producción'
        ]);
        api_json(['success' => true, 'id_mantenimiento' => $idMantenimiento]);
    } catch (Throwable $e) {
        if ($e instanceof InvalidArgumentException) {
            api_json(['success' => false, 'message' => $e->getMessage()], 409);
        }
        api_json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

if ($action === 'mantenimientos_list') {
    api_require_employee_roles(['gerencia', 'mantenimiento']);
    api_json($mantenimientoDAO->listar());
}

if ($action === 'mantenimiento_preventivo_create') {
    try {
        $user = api_require_employee_roles(['gerencia', 'mantenimiento']);
        $idMaquina = api_int('id_maquina');
        if ($idMaquina <= 0) {
            api_json(['success' => false, 'message' => 'La mÃ¡quina debe ser vÃ¡lida'], 422);
        }
        $idMantenimiento = $mantenimientoDAO->crearPreventivo([
            'id_maquina' => $idMaquina,
            'id_usuario' => (int)($user['id_usuario'] ?? 0),
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
        $user = api_require_employee_roles(['gerencia', 'mantenimiento']);
        $idMantenimiento = api_int('id_mantenimiento');
        if ($idMantenimiento <= 0) {
            api_json(['success' => false, 'message' => 'El mantenimiento debe ser vÃ¡lido'], 422);
        }
        $estado = api_required('estado');
        if (!in_array($estado, ['Pendiente', 'En proceso', 'Resuelta'], true)) {
            api_json(['success' => false, 'message' => 'Estado de mantenimiento invÃ¡lido'], 422);
        }
        $detalleMantenimiento = $mantenimientoDAO->actualizar([
            'id_mantenimiento' => $idMantenimiento,
            'reparacion' => api_required('reparacion'),
            'repuestos' => api_input('repuestos', ''),
            'estado' => $estado,
            'id_usuario' => (int)($user['id_usuario'] ?? 0)
        ]);
        api_json(['success' => true]);
    } catch (Throwable $e) {
        api_json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

if ($action === 'materiales_list') {
    api_require_employee_roles(['gerencia', 'administracion', 'compras', 'deposito']);
    api_json($comprasDAO->listarMateriales());
}

if ($action === 'materiales_create') {
    try {
        api_require_employee_roles(['gerencia', 'compras']);
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
        api_json(['success' => true, 'id_material' => $idMaterial]);
    } catch (Throwable $e) {
        api_json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

if ($action === 'materiales_update') {
    try {
        api_require_employee_roles(['gerencia', 'compras']);
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
        api_require_employee_roles(['gerencia', 'compras']);
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
    api_require_employee_roles(['gerencia', 'deposito']);
    api_json($depositoDAO->listarMovimientos());
}

if ($action === 'deposito_movimiento_create') {
    try {
        $user = api_require_employee_roles(['gerencia', 'deposito']);
        $idMaterial = api_int('id_material');
        $cantidad = (float)api_input('cantidad', '0');
        $tipo = api_required('tipo');
        if ($idMaterial <= 0 || $cantidad <= 0 || !in_array($tipo, ['Ingreso', 'Salida'], true)) {
            api_json(['success' => false, 'message' => 'Movimiento de stock invalido'], 422);
        }
        $motivo = api_input('motivo', '');
        $seguimientoEstado = api_input('seguimiento_estado', '');
        $idPedidoRelacionado = api_int('id_pedido');
        $seguimientoDescripcion = '';
        if ($seguimientoEstado !== '') {
            if ($idPedidoRelacionado <= 0 || !in_array($seguimientoEstado, ['Falta de materiales', 'Material disponible'], true)
                || ($seguimientoEstado === 'Falta de materiales' && $tipo !== 'Salida')
                || ($seguimientoEstado === 'Material disponible' && $tipo !== 'Ingreso')) {
                api_json(['success' => false, 'message' => 'Elegí un pedido y un movimiento compatible con la novedad.'], 422);
            }
            $materiales = $comprasDAO->listarMateriales();
            $material = null;
            foreach ($materiales as $item) {
                if ((int)$item['id_material'] === $idMaterial) { $material = $item; break; }
            }
            $materialNombre = $material['nombre'] ?? 'material';
            $seguimientoDescripcion = $seguimientoEstado === 'Falta de materiales'
                ? 'Depósito informó una falta de ' . $materialNombre . ' que afecta este pedido.'
                : 'Depósito confirmó disponibilidad de ' . $materialNombre . ' para este pedido.';
            if ($motivo !== '') $seguimientoDescripcion .= ' ' . $motivo;
        }
        $idMovimiento = $depositoDAO->registrarMovimiento([
            'id_material' => $idMaterial,
            'tipo' => $tipo,
            'cantidad' => $cantidad,
            'motivo' => $motivo,
            'id_usuario' => (int)($user['id_usuario'] ?? 0),
            'id_pedido' => $idPedidoRelacionado,
            'seguimiento_estado' => $seguimientoEstado,
            'seguimiento_descripcion' => $seguimientoDescripcion
        ]);
        api_json(['success' => true, 'id_movimiento' => $idMovimiento]);
    } catch (Throwable $e) {
        api_json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

if ($action === 'proveedores_list') {
    api_require_employee_roles(['gerencia', 'compras']);
    api_json($comprasDAO->listarProveedores());
}

if ($action === 'proveedores_create') {
    try {
        api_require_employee_roles(['gerencia', 'compras']);
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
    api_require_employee_roles(['gerencia', 'compras']);
    api_json($comprasDAO->listarCompras());
}

if ($action === 'compras_create') {
    try {
        $user = api_require_employee_roles(['gerencia', 'compras']);
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
        $seguimientoEstado = api_input('seguimiento_estado', '');
        $idPedidoRelacionado = api_int('id_pedido');
        $seguimientoDescripcion = '';
        if ($seguimientoEstado !== '') {
            if ($idPedidoRelacionado <= 0 || !in_array($seguimientoEstado, ['Falta de materiales', 'Material disponible'], true)
                || ($seguimientoEstado === 'Falta de materiales' && $estado === 'Cancelada')
                || ($seguimientoEstado === 'Material disponible' && $estado !== 'Recibida')) {
                api_json(['success' => false, 'message' => 'La novedad debe asociarse a un pedido y coincidir con el estado de compra.'], 422);
            }
            $materiales = $comprasDAO->listarMateriales();
            $material = null;
            foreach ($materiales as $item) {
                if ((int)$item['id_material'] === $idMaterial) { $material = $item; break; }
            }
            $materialNombre = $material['nombre'] ?? 'material';
            $seguimientoDescripcion = $seguimientoEstado === 'Material disponible'
                ? 'Compras recibió ' . $materialNombre . ' y confirmó su disponibilidad para este pedido.'
                : 'Compras informó una demora por falta de ' . $materialNombre . ' para este pedido.';
        }
        $idCompra = $comprasDAO->crearCompra([
            'id_proveedor' => $idProveedor,
            'id_material' => $idMaterial,
            'cantidad' => $cantidad,
            'precio' => $precio,
            'fecha' => api_input('fecha', date('Y-m-d')),
            'estado' => $estado,
            'id_pedido' => $idPedidoRelacionado,
            'seguimiento_estado' => $seguimientoEstado,
            'seguimiento_descripcion' => $seguimientoDescripcion,
            'id_usuario' => (int)($user['id_usuario'] ?? 0)
        ]);
        api_json(['success' => true, 'id_compra' => $idCompra]);
    } catch (Throwable $e) {
        api_json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

if ($action === 'calidad_list') {
    api_require_employee_roles(['gerencia', 'administracion', 'calidad']);
    api_json($calidadDAO->listarControles());
}

if ($action === 'calidad_register') {
    try {
        $user = api_require_employee_roles(['gerencia', 'calidad']);
        $idOrden = api_int('id_orden');
        $resultado = api_required('resultado');
        if ($idOrden <= 0 || !in_array($resultado, ['Aprobado', 'Observado', 'Rechazado'], true)) {
            api_json(['success' => false, 'message' => 'Control de calidad invalido'], 422);
        }
        $idControl = $calidadDAO->registrar([
            'id_orden' => $idOrden,
            'id_usuario' => (int)($user['id_usuario'] ?? 0),
            'fecha' => api_input('fecha', date('Y-m-d')),
            'resultado' => $resultado,
            'observaciones' => api_input('observaciones', '')
        ]);
        api_json(['success' => true, 'id_control' => $idControl]);
    } catch (Throwable $e) {
        api_json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

if ($action === 'supervisor_record_update' || $action === 'supervisor_record_delete') {
    api_department_supervisor_module();
    $entity = api_required('entity');
    $id = api_int('id');
    $entities = ['cliente', 'pedido', 'orden', 'produccion', 'mantenimiento', 'material', 'movimiento', 'proveedor', 'compra', 'calidad'];
    $idFields = [
        'cliente' => 'id_cliente',
        'pedido' => 'id_pedido',
        'orden' => 'id_orden',
        'produccion' => 'id_produccion',
        'mantenimiento' => 'id_mantenimiento',
        'material' => 'id_material',
        'movimiento' => 'id_movimiento',
        'proveedor' => 'id_proveedor',
        'compra' => 'id_compra',
        'calidad' => 'id_control'
    ];
    if (!in_array($entity, $entities, true)) {
        api_json(['success' => false, 'message' => 'Tu rol no puede modificar registros de esta área.'], 403);
    }
    if ($id <= 0) {
        api_json(['success' => false, 'message' => 'Seleccioná un registro válido.'], 422);
    }

    $pdo = api_db();
    $idField = $idFields[$entity];
    $tables = [
        'cliente' => 'cliente',
        'pedido' => 'pedido',
        'orden' => 'orden_trabajo',
        'produccion' => 'produccion',
        'mantenimiento' => 'mantenimiento',
        'material' => 'material',
        'movimiento' => 'material_movimiento',
        'proveedor' => 'proveedor',
        'compra' => 'compra',
        'calidad' => 'control_calidad'
    ];
    try {
        $pdo->beginTransaction();
        $existing = $pdo->prepare("SELECT $idField FROM {$tables[$entity]} WHERE $idField = :id FOR UPDATE");
        $existing->execute([':id' => $id]);
        if (!$existing->fetchColumn()) {
            $pdo->rollBack();
            api_json(['success' => false, 'message' => 'No se encontró el registro.'], 404);
        }

        if ($action === 'supervisor_record_update') {
            switch ($entity) {
                case 'cliente':
                    $update = $pdo->prepare('UPDATE cliente SET razon_social = :razon_social, cuit = :cuit, email = :email, direccion = :direccion WHERE id_cliente = :id');
                    $update->execute([
                        ':razon_social' => api_required('razon_social'),
                        ':cuit' => api_input('cuit', ''),
                        ':email' => api_input('email', ''),
                        ':direccion' => api_input('direccion', ''),
                        ':id' => $id
                    ]);
                    break;
                case 'pedido':
                    $quantity = api_int('cantidad', 0);
                    $deliveryDate = api_input('fecha_entrega');
                    if ($quantity <= 0 || ($deliveryDate !== null && $deliveryDate !== '' && !DateTime::createFromFormat('Y-m-d', $deliveryDate))) {
                        throw new InvalidArgumentException('La cantidad o la fecha de entrega no son válidas.');
                    }
                    $update = $pdo->prepare('UPDATE pedido SET descripcion = :descripcion, cantidad = :cantidad, material = :material, fecha_entrega = :fecha_entrega WHERE id_pedido = :id');
                    $update->execute([
                        ':descripcion' => api_required('descripcion'),
                        ':cantidad' => $quantity,
                        ':material' => api_required('material'),
                        ':fecha_entrega' => $deliveryDate === '' ? null : $deliveryDate,
                        ':id' => $id
                    ]);
                    break;
                case 'orden':
                    $priority = api_required('prioridad');
                    $validPriorities = ['Baja', 'Media', 'Alta', 'Urgente'];
                    $expectedDate = api_input('fecha_prevista');
                    if (!in_array($priority, $validPriorities, true)
                        || ($expectedDate !== null && $expectedDate !== '' && !DateTime::createFromFormat('Y-m-d', $expectedDate))) {
                        throw new InvalidArgumentException('La prioridad o la fecha prevista no son válidas.');
                    }
                    $update = $pdo->prepare('UPDATE orden_trabajo SET fecha_prevista = :fecha_prevista, prioridad = :prioridad, observaciones = :observaciones WHERE id_orden = :id');
                    $update->execute([
                        ':fecha_prevista' => $expectedDate === '' ? null : $expectedDate,
                        ':prioridad' => $priority,
                        ':observaciones' => api_input('observaciones', ''),
                        ':id' => $id
                    ]);
                    break;
                case 'produccion':
                    $cantidad = (int)api_input('cantidad_producida', '-1');
                    $avance = (int)api_input('avance', '-1');
                    if ($cantidad < 0 || $avance < 0 || $avance > 100) {
                        throw new InvalidArgumentException('La cantidad debe ser positiva y el avance debe estar entre 0 y 100.');
                    }
                    $update = $pdo->prepare('UPDATE produccion SET cantidad_producida = :cantidad, avance = :avance, observaciones = :observaciones WHERE id_produccion = :id');
                    $update->execute([':cantidad' => $cantidad, ':avance' => $avance, ':observaciones' => api_input('observaciones', ''), ':id' => $id]);
                    $orderQuery = $pdo->prepare('SELECT id_orden FROM produccion WHERE id_produccion = :id');
                    $orderQuery->execute([':id' => $id]);
                    api_sync_production_order($pdo, (int)$orderQuery->fetchColumn());
                    break;
                case 'mantenimiento':
                    $estado = api_required('estado');
                    if (!in_array($estado, ['Pendiente', 'En proceso', 'Resuelta'], true)) {
                        throw new InvalidArgumentException('El estado de mantenimiento no es válido.');
                    }
                    $update = $pdo->prepare('UPDATE mantenimiento SET problema = :problema, reparacion = :reparacion, repuestos = :repuestos, estado = :estado WHERE id_mantenimiento = :id');
                    $update->execute([
                        ':problema' => api_input('problema', ''),
                        ':reparacion' => api_input('reparacion', ''),
                        ':repuestos' => api_input('repuestos', ''),
                        ':estado' => $estado,
                        ':id' => $id
                    ]);
                    $machineQuery = $pdo->prepare('SELECT id_maquina FROM mantenimiento WHERE id_mantenimiento = :id');
                    $machineQuery->execute([':id' => $id]);
                    $idMaquina = (int)$machineQuery->fetchColumn();
                    $openQuery = $pdo->prepare("SELECT COUNT(*) FROM mantenimiento WHERE id_maquina = :id_maquina AND estado <> 'Resuelta'");
                    $openQuery->execute([':id_maquina' => $idMaquina]);
                    $machineUpdate = $pdo->prepare('UPDATE maquina SET estado = :estado WHERE id_maquina = :id_maquina');
                    $machineUpdate->execute([':estado' => (int)$openQuery->fetchColumn() > 0 ? 'Con falla' : 'Operativa', ':id_maquina' => $idMaquina]);
                    break;
                case 'material':
                    $minimum = (float)api_input('stock_minimo', '-1');
                    $maximum = (float)api_input('stock_maximo', '-1');
                    if ($minimum < 0 || $maximum < $minimum) {
                        throw new InvalidArgumentException('Los límites de stock no son válidos.');
                    }
                    $update = $pdo->prepare('UPDATE material SET nombre = :nombre, tipo = :tipo, unidad = :unidad, espesor = :espesor, stock_minimo = :minimo, stock_maximo = :maximo WHERE id_material = :id');
                    $update->execute([
                        ':nombre' => api_required('nombre'),
                        ':tipo' => api_input('tipo', ''),
                        ':unidad' => api_input('unidad', ''),
                        ':espesor' => api_input('espesor', ''),
                        ':minimo' => $minimum,
                        ':maximo' => $maximum,
                        ':id' => $id
                    ]);
                    break;
                case 'movimiento':
                    $type = api_required('tipo');
                    $quantity = (float)api_input('cantidad', '0');
                    if (!in_array($type, ['Ingreso', 'Salida'], true) || $quantity <= 0) {
                        throw new InvalidArgumentException('El tipo y la cantidad del movimiento no son válidos.');
                    }
                    $currentQuery = $pdo->prepare('SELECT id_material, tipo, cantidad FROM material_movimiento WHERE id_movimiento = :id');
                    $currentQuery->execute([':id' => $id]);
                    $current = $currentQuery->fetch();
                    $sign = static function ($movementType, $movementQuantity) {
                        return $movementType === 'Salida' ? -(float)$movementQuantity : (float)$movementQuantity;
                    };
                    api_adjust_stock($pdo, (int)$current['id_material'], $sign($type, $quantity) - $sign($current['tipo'], $current['cantidad']));
                    $update = $pdo->prepare('UPDATE material_movimiento SET tipo = :tipo, cantidad = :cantidad, motivo = :motivo WHERE id_movimiento = :id');
                    $update->execute([':tipo' => $type, ':cantidad' => $quantity, ':motivo' => api_input('motivo', ''), ':id' => $id]);
                    break;
                case 'proveedor':
                    $update = $pdo->prepare('UPDATE proveedor SET razon_social = :razon_social, cuit = :cuit, telefono = :telefono, email = :email WHERE id_proveedor = :id');
                    $update->execute([
                        ':razon_social' => api_required('razon_social'),
                        ':cuit' => api_input('cuit', ''),
                        ':telefono' => api_input('telefono', ''),
                        ':email' => api_input('email', ''),
                        ':id' => $id
                    ]);
                    break;
                case 'compra':
                    $estado = api_required('estado');
                    $fecha = api_required('fecha');
                    if (!in_array($estado, ['Solicitada', 'Recibida', 'Cancelada'], true)
                        || !DateTime::createFromFormat('Y-m-d', $fecha)) {
                        throw new InvalidArgumentException('La fecha o el estado de compra no son válidos.');
                    }
                    $currentQuery = $pdo->prepare('SELECT estado FROM compra WHERE id_compra = :id');
                    $currentQuery->execute([':id' => $id]);
                    $oldState = $currentQuery->fetchColumn();
                    if (($oldState === 'Recibida') !== ($estado === 'Recibida')) {
                        $details = $pdo->prepare('SELECT id_material, cantidad FROM detalle_compra WHERE id_compra = :id');
                        $details->execute([':id' => $id]);
                        foreach ($details->fetchAll() as $detail) {
                            $delta = (float)$detail['cantidad'] * ($estado === 'Recibida' ? 1 : -1);
                            api_adjust_stock($pdo, (int)$detail['id_material'], $delta);
                        }
                    }
                    $update = $pdo->prepare('UPDATE compra SET fecha = :fecha, estado = :estado WHERE id_compra = :id');
                    $update->execute([':fecha' => $fecha, ':estado' => $estado, ':id' => $id]);
                    break;
                case 'calidad':
                    $resultado = api_required('resultado');
                    $fecha = api_required('fecha');
                    if (!in_array($resultado, ['Aprobado', 'Observado', 'Rechazado'], true)
                        || !DateTime::createFromFormat('Y-m-d', $fecha)) {
                        throw new InvalidArgumentException('La fecha o el resultado de calidad no son válidos.');
                    }
                    $update = $pdo->prepare('UPDATE control_calidad SET fecha = :fecha, resultado = :resultado, observaciones = :observaciones WHERE id_control = :id');
                    $update->execute([':fecha' => $fecha, ':resultado' => $resultado, ':observaciones' => api_input('observaciones', ''), ':id' => $id]);
                    break;
            }
            $pdo->commit();
            api_json(['success' => true]);
        }

        switch ($entity) {
            case 'cliente':
                $delete = $pdo->prepare('DELETE FROM cliente WHERE id_cliente = :id');
                $delete->execute([':id' => $id]);
                break;
            case 'pedido':
                $linkedOrder = $pdo->prepare('SELECT id_orden FROM orden_trabajo WHERE id_pedido = :id LIMIT 1');
                $linkedOrder->execute([':id' => $id]);
                if ($linkedOrder->fetchColumn()) {
                    throw new InvalidArgumentException('No se puede eliminar un pedido que ya tiene una orden de trabajo.');
                }
                $delete = $pdo->prepare('DELETE FROM pedido WHERE id_pedido = :id');
                $delete->execute([':id' => $id]);
                break;
            case 'orden':
                $pedidoQuery = $pdo->prepare('SELECT id_pedido FROM orden_trabajo WHERE id_orden = :id');
                $pedidoQuery->execute([':id' => $id]);
                $idPedido = (int)$pedidoQuery->fetchColumn();
                $childTables = [
                    ['produccion', 'id_orden'],
                    ['control_calidad', 'id_orden'],
                    ['entrega', 'id_orden'],
                    ['orden_maquina', 'id_orden'],
                    ['orden_material', 'id_orden']
                ];
                foreach ($childTables as [$table, $column]) {
                    $linkedRecords = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE $column = :id");
                    $linkedRecords->execute([':id' => $id]);
                    if ((int)$linkedRecords->fetchColumn() > 0) {
                        throw new InvalidArgumentException('No se puede eliminar una orden con registros de producción, calidad, entrega o materiales asociados.');
                    }
                }
                $delete = $pdo->prepare('DELETE FROM orden_trabajo WHERE id_orden = :id');
                $delete->execute([':id' => $id]);
                $resetPedido = $pdo->prepare("UPDATE pedido SET estado = 'Pendiente' WHERE id_pedido = :id_pedido");
                $resetPedido->execute([':id_pedido' => $idPedido]);
                break;
            case 'produccion':
                $orderQuery = $pdo->prepare('SELECT id_orden FROM produccion WHERE id_produccion = :id');
                $orderQuery->execute([':id' => $id]);
                $idOrden = (int)$orderQuery->fetchColumn();
                $delete = $pdo->prepare('DELETE FROM produccion WHERE id_produccion = :id');
                $delete->execute([':id' => $id]);
                api_sync_production_order($pdo, $idOrden);
                break;
            case 'mantenimiento':
                $machineQuery = $pdo->prepare('SELECT id_maquina FROM mantenimiento WHERE id_mantenimiento = :id');
                $machineQuery->execute([':id' => $id]);
                $idMaquina = (int)$machineQuery->fetchColumn();
                $delete = $pdo->prepare('DELETE FROM mantenimiento WHERE id_mantenimiento = :id');
                $delete->execute([':id' => $id]);
                $openQuery = $pdo->prepare("SELECT COUNT(*) FROM mantenimiento WHERE id_maquina = :id_maquina AND estado <> 'Resuelta'");
                $openQuery->execute([':id_maquina' => $idMaquina]);
                $machineUpdate = $pdo->prepare('UPDATE maquina SET estado = :estado WHERE id_maquina = :id_maquina');
                $machineUpdate->execute([':estado' => (int)$openQuery->fetchColumn() > 0 ? 'Con falla' : 'Operativa', ':id_maquina' => $idMaquina]);
                break;
            case 'material':
                $delete = $pdo->prepare('DELETE FROM material WHERE id_material = :id');
                $delete->execute([':id' => $id]);
                break;
            case 'movimiento':
                $currentQuery = $pdo->prepare('SELECT id_material, tipo, cantidad FROM material_movimiento WHERE id_movimiento = :id');
                $currentQuery->execute([':id' => $id]);
                $current = $currentQuery->fetch();
                $delta = $current['tipo'] === 'Salida' ? (float)$current['cantidad'] : -(float)$current['cantidad'];
                api_adjust_stock($pdo, (int)$current['id_material'], $delta);
                $delete = $pdo->prepare('DELETE FROM material_movimiento WHERE id_movimiento = :id');
                $delete->execute([':id' => $id]);
                break;
            case 'proveedor':
                $delete = $pdo->prepare('DELETE FROM proveedor WHERE id_proveedor = :id');
                $delete->execute([':id' => $id]);
                break;
            case 'compra':
                $currentQuery = $pdo->prepare('SELECT estado FROM compra WHERE id_compra = :id');
                $currentQuery->execute([':id' => $id]);
                if ($currentQuery->fetchColumn() === 'Recibida') {
                    $details = $pdo->prepare('SELECT id_material, cantidad FROM detalle_compra WHERE id_compra = :id');
                    $details->execute([':id' => $id]);
                    foreach ($details->fetchAll() as $detail) {
                        api_adjust_stock($pdo, (int)$detail['id_material'], -(float)$detail['cantidad']);
                    }
                }
                $deleteDetails = $pdo->prepare('DELETE FROM detalle_compra WHERE id_compra = :id');
                $deleteDetails->execute([':id' => $id]);
                $delete = $pdo->prepare('DELETE FROM compra WHERE id_compra = :id');
                $delete->execute([':id' => $id]);
                break;
            case 'calidad':
                $delete = $pdo->prepare('DELETE FROM control_calidad WHERE id_control = :id');
                $delete->execute([':id' => $id]);
                break;
        }
        $pdo->commit();
        api_json(['success' => true]);
    } catch (InvalidArgumentException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        api_json(['success' => false, 'message' => $e->getMessage()], 409);
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('MetalGest supervisor record change failed: ' . $e->getMessage());
        api_json(['success' => false, 'message' => 'No se puede eliminar el registro porque tiene datos asociados.'], 409);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('MetalGest supervisor record change failed: ' . $e->getMessage());
        api_json(['success' => false, 'message' => 'No se pudo guardar el cambio.'], 500);
    }
}

api_json(['success' => false, 'message' => 'AcciÃ³n no reconocida'], 404);
