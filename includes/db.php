<?php
require_once __DIR__ . '/env.php';
rdi_env_load(__DIR__ . '/../.env');
$tz = rdi_env('APP_TIMEZONE', 'Asia/Dhaka');
if ($tz) @date_default_timezone_set($tz);
/* RDI API core: deliberately compatible with PHP 7.1+ and PHP 8.x. */
ini_set('display_errors', '0');
ini_set('html_errors', '0');
error_reporting(E_ALL);

function api_is_request() {
    $script = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', $_SERVER['SCRIPT_NAME']) : '';
    return strpos($script, '/api/') !== false;
}

function json_response($data, $status = 200) {
    if (!headers_sent()) {
        http_response_code((int)$status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    }
    $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        $json = '{"error":"Could not encode server response."}';
    }
    echo $json;
    exit;
}

/* Convert uncaught API failures into valid JSON instead of PHP's HTML error page. */
if (api_is_request()) {
    set_exception_handler(function ($e) {
        error_log('[RDI API] ' . get_class($e) . ': ' . $e->getMessage());
        $message = 'Server error. Check database configuration and PHP error log.';
        if ($e instanceof PDOException) {
            $message = 'Database error. Verify MySQL is running, database is installed, and config/database.php credentials are correct.';
        }
        json_response(array('error' => $message), 500);
    });

    register_shutdown_function(function () {
        $e = error_get_last();
        if (!$e) return;
        $fatal = array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR);
        if (!in_array($e['type'], $fatal, true)) return;
        error_log('[RDI API fatal] ' . $e['message'] . ' in ' . $e['file'] . ':' . $e['line']);
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode(array('error' => 'PHP server error. Check PHP version, extensions, and server error log.'));
    });
}

function db() {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $configFile = __DIR__ . '/../config/database.php';
    if (!is_file($configFile)) {
        throw new RuntimeException('Database configuration file not found.');
    }
    $c = require $configFile;
    foreach (array('host','port','name','user','pass','charset') as $key) {
        if (!array_key_exists($key, $c)) throw new RuntimeException('Missing database config: ' . $key);
    }
    foreach (array('host','port','name','user','charset') as $required) {
        if ($c[$required] === null || $c[$required] === '') {
            throw new RuntimeException('Missing required .env value for database setting: ' . $required . '. Copy .env.example to .env and configure it.');
        }
    }
    if (!extension_loaded('pdo_mysql')) {
        throw new RuntimeException('PDO MySQL extension is not enabled in PHP.');
    }

    $dsn = 'mysql:host=' . $c['host'] . ';port=' . $c['port'] . ';dbname=' . $c['name'] . ';charset=' . $c['charset'];
    $pdo = new PDO($dsn, $c['user'], $c['pass'], array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ));
    return $pdo;
}


function db_statement($sql, $params = array()) {
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}

function db_all($sql, $params = array()) {
    return db_statement($sql, $params)->fetchAll();
}

function db_one($sql, $params = array()) {
    $row = db_statement($sql, $params)->fetch();
    return $row ?: null;
}

function db_value($sql, $params = array()) {
    return db_statement($sql, $params)->fetchColumn();
}

function db_execute($sql, $params = array()) {
    $st = db_statement($sql, $params);
    return $st->rowCount();
}

function body_json() {
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') return array();
    $d = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($d)) {
        json_response(array('error' => 'Invalid JSON request body.'), 400);
    }
    return $d;
}

function start_rdi_session() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        if (function_exists('session_name')) session_name('RDISESSID');
        @ini_set('session.use_strict_mode', '1');
        @ini_set('session.use_only_cookies', '1');
        if (PHP_VERSION_ID >= 70300) {
            session_set_cookie_params(array('lifetime'=>0,'path'=>'/','secure'=>$secure,'httponly'=>true,'samesite'=>'Lax'));
        } else {
            session_set_cookie_params(0, '/; samesite=Lax', '', $secure, true);
        }
        if (!session_start()) throw new RuntimeException('Could not start PHP session. Check session configuration and permissions.');
    }
}

function user_row_by_id($id) {
    if (!$id) return null;
    try {
        $st=db()->prepare("SELECT id,rdi_member_id,name,email,role,department,team,profile_image,status,join_date,deleted_at,created_at FROM users WHERE id=? LIMIT 1");
        $st->execute(array($id));
        return $st->fetch() ?: null;
    } catch (Throwable $e) {
        /* Allows installer to be opened before migrations exist. */
        return null;
    }
}

function current_user() {
    start_rdi_session();
    if (empty($_SESSION['rdi_user']['id'])) return null;
    $fresh=user_row_by_id($_SESSION['rdi_user']['id']);
    if (!$fresh || ($fresh['status'] ?? 'Active') !== 'Active' || !empty($fresh['deleted_at'])) {
        unset($_SESSION['rdi_user']);
        return null;
    }
    $fresh['permissions']=effective_permissions($fresh);
    $_SESSION['rdi_user']=$fresh;
    return $fresh;
}

function effective_permissions($user) {
    if (!$user || empty($user['id'])) return array();
    $pdo=db();
    $map=array();
    $st=$pdo->prepare("SELECT permission_key FROM role_permissions WHERE role_slug=?");
    $st->execute(array($user['role'] ?? 'researcher'));
    foreach($st as $r) $map[$r['permission_key']]=true;
    $st=$pdo->prepare("SELECT permission_key,allowed FROM user_permissions WHERE user_id=?");
    $st->execute(array($user['id']));
    foreach($st as $r) $map[$r['permission_key']]=(bool)$r['allowed'];
    return array_values(array_keys(array_filter($map)));
}

function has_permission($user,$permission) {
    if (!$user) return false;
    if (($user['role'] ?? '') === 'super_admin') return true;
    $perms=isset($user['permissions']) && is_array($user['permissions']) ? $user['permissions'] : effective_permissions($user);
    return in_array($permission,$perms,true);
}

function require_user() {
    $u=current_user();
    if(!$u) json_response(array('error'=>'Authentication required or account is not active.'),401);
    return $u;
}
function require_admin() {
    $u=require_user();
    if(!in_array(($u['role'] ?? ''),array('super_admin','admin'),true)) json_response(array('error'=>'Admin access required'),403);
    return $u;
}
function require_permission($permission) {
    $u=require_user();
    if(!has_permission($u,$permission)) json_response(array('error'=>'Permission denied: '.$permission),403);
    return $u;
}
function is_super_admin($user){ return $user && (($user['role'] ?? '')==='super_admin'); }

function audit_log($action,$entityType=null,$entityId=null,$details=null,$actor=null) {
    try {
        if ($actor===null) {
            start_rdi_session();
            $actor=isset($_SESSION['rdi_user']) ? $_SESSION['rdi_user'] : null;
        }
        $ip=isset($_SERVER['REMOTE_ADDR']) ? substr((string)$_SERVER['REMOTE_ADDR'],0,64) : null;
        $st=db()->prepare("INSERT INTO audit_logs(actor_user_id,action,entity_type,entity_id,details,ip_address) VALUES(?,?,?,?,?,?)");
        $st->execute(array($actor['id'] ?? null,$action,$entityType,$entityId,$details,$ip));
    } catch (Throwable $e) { error_log('[RDI audit] '.$e->getMessage()); }
}

function get_setting($key,$default='') {
    try { $st=db()->prepare("SELECT setting_value FROM settings WHERE setting_key=? LIMIT 1");$st->execute(array($key));$v=$st->fetchColumn();return $v===false?$default:$v; }
    catch(Throwable $e){ return $default; }
}
