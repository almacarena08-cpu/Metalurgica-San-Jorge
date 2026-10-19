<?php
$apiOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedApiOrigins = ['http://127.0.0.1:5500', 'http://localhost:5500'];
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
    if (!$user || !in_array(strtolower($user['rol'] ?? ''), ['gerencia', 'administracion'], true)) {
        api_json(['success' => false, 'message' => 'Iniciá sesión con una cuenta autorizada para acceder al chat.'], 401);
    }
    return $user;
}

function api_chat_require_user() {
    $user = $_SESSION['metalgest_user'] ?? null;
    if (!$user) {
        api_json(['success' => false, 'message' => 'Iniciá sesión para acceder al chat.'], 401);
    }
    return $user;
}

function api_require_employee_roles(array $roles) {
    $user = $_SESSION['metalgest_user'] ?? null;
    if (!$user || ($user['tipo'] ?? '') !== 'empleado') {
        api_json(['success' => false, 'message' => 'Iniciá sesión con una cuenta de empleado.'], 401);
    }
    if (!in_array(strtolower($user['rol'] ?? ''), array_map('strtolower', $roles), true)) {
        api_json(['success' => false, 'message' => 'Tu cuenta no tiene permiso para esta acción.'], 403);
    }
    return $user;
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
        'SELECT u.id_usuario, u.nombre, u.apellido, u.usuario, u.id_cliente, r.nombre AS rol
         FROM usuario u
         INNER JOIN rol r ON r.id_rol = u.id_rol
         WHERE u.usuario = :usuario
             AND u.contrasena = :contrasena
         LIMIT 1'
    );
    $stmt->execute([
        ':usuario' => $usuarioLogin,
        ':contrasena' => $contrasenaLogin
    ]);
    $user = $stmt->fetch();
    if (!$user) {
        api_json(['success' => false, 'message' => 'Credenciales incorrectas'], 401);
    }
    session_regenerate_id(true);
    $_SESSION['metalgest_user'] = [
        'tipo' => 'empleado',
        'id_usuario' => (int)$user['id_usuario'],
        'rol' => $user['rol']
    ];
    api_json(['success' => true, 'usuario' => $user]);
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
    api_json($clienteDAO->listar());
}

if ($action === 'clientes_create') {
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

if ($action === 'chat_presence') {
    $user = api_require_client();
    $idChat = api_int('id_chat');
    if ($idChat <= 0 || !$chatDAO->registrarActividadCliente($idChat, (int)$user['id_cliente'])) {
        api_json(['success' => false, 'message' => 'No tenés acceso a esta conversación.'], 403);
    }
    api_json(['success' => true]);
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
        error_log('MetalGest chat upload failed: ' . $e->getMessage());
        api_json(['success' => false, 'message' => 'No se pudieron guardar los archivos. Intentá nuevamente.'], 500);
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
        $clienteEnLinea = $chatDAO->clienteActivoRecientemente($idChat);
        $chatDAO->enviarMensaje([
            'id_chat' => $idChat,
            'id_usuario' => (int)$user['id_usuario'],
            'autor_tipo' => 'Administracion',
            'mensaje' => $mensaje,
            'adjuntos' => $uploads
        ]);
        $notificacionEnviada = false;
        if (!$clienteEnLinea && filter_var($contacto['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
            $notificacionEnviada = api_send_new_chat_message_email($contacto['email']);
        }
        api_json(['success' => true, 'cliente_en_linea' => $clienteEnLinea, 'notificacion_enviada' => $notificacionEnviada]);
    } catch (Throwable $e) {
        api_chat_remove_uploads($uploads ?? []);
        error_log('MetalGest chat upload failed: ' . $e->getMessage());
        api_json(['success' => false, 'message' => 'No se pudieron guardar los archivos. Intentá nuevamente.'], 500);
    }
}

if ($action === 'pedido_create_from_chat') {
    try {
        $user = api_chat_require_staff();
        $cantidad = api_int('cantidad', 1);
        if ($cantidad <= 0) api_json(['success' => false, 'message' => 'La cantidad debe ser mayor que cero.'], 422);
        $idPedido = $pedidoDAO->crearDesdeConversacion([
            'id_chat' => api_int('id_chat'),
            'descripcion' => api_required('descripcion'),
            'cantidad' => $cantidad,
            'material' => api_required('material'),
            'fecha_entrega' => api_date(api_input('fecha_entrega')),
            'id_usuario' => (int)($user['id_usuario'] ?? 0)
        ]);
        api_json(['success' => true, 'id_pedido' => $idPedido]);
    } catch (InvalidArgumentException $e) {
        api_json(['success' => false, 'message' => $e->getMessage()], 409);
    } catch (Throwable $e) {
        error_log('MetalGest order registration from chat failed: ' . $e->getMessage());
        api_json(['success' => false, 'message' => 'No se pudo registrar el pedido desde la conversación.'], 500);
    }
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
        $ordenTrabajoDAO->actualizarEstado([
            'id_orden' => $idOrden,
            'estado' => $estado,
            'observaciones' => $observaciones,
            'seguimiento_estado' => $estado === 'Finalizada' ? 'Pedido finalizado' : ($estado === 'Lista para entrega' ? 'Listo para su entrega' : $estado),
            'seguimiento_descripcion' => $observaciones !== '' ? $observaciones : 'El área de ' . ucfirst($user['rol']) . ' actualizó el estado del pedido a ' . $estado . '.',
            'area' => $user['rol'] === 'produccion' ? 'Producción' : 'Administración',
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
            'area' => strtolower($user['rol']) === 'mantenimiento' ? 'Mantenimiento' : 'Producción'
        ]);
        api_json(['success' => true, 'id_mantenimiento' => $idMantenimiento]);
    } catch (Throwable $e) {
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

api_json(['success' => false, 'message' => 'AcciÃ³n no reconocida'], 404);
