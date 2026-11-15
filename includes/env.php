<?php
/* Lightweight .env loader for PHP 7.1+; no external dependency required. */
function rdi_env_load($path) {
    static $loaded = array();
    $real = realpath($path);
    if ($real === false || isset($loaded[$real])) return;
    $loaded[$real] = true;
    $lines = @file($real, FILE_IGNORE_NEW_LINES);
    if ($lines === false) return;
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, 'export ') === 0) $line = trim(substr($line, 7));
        $pos = strpos($line, '=');
        if ($pos === false) continue;
        $key = trim(substr($line, 0, $pos));
        $value = trim(substr($line, $pos + 1));
        if ($key === '' || !preg_match('/^[A-Z0-9_]+$/i', $key)) continue;
        if (strlen($value) >= 2) {
            $first = $value[0]; $last = $value[strlen($value)-1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $value = substr($value, 1, -1);
                if ($first === '"') $value = stripcslashes($value);
            } else {
                $hash = strpos($value, ' #');
                if ($hash !== false) $value = rtrim(substr($value, 0, $hash));
            }
        }
        if (getenv($key) === false) putenv($key . '=' . $value);
        if (!isset($_ENV[$key])) $_ENV[$key] = $value;
        if (!isset($_SERVER[$key])) $_SERVER[$key] = $value;
    }
}

function rdi_env($key, $default = null) {
    $value = getenv($key);
    if ($value === false && isset($_ENV[$key])) $value = $_ENV[$key];
    if ($value === false && isset($_SERVER[$key])) $value = $_SERVER[$key];
    return ($value === false || $value === '') ? $default : $value;
}

function rdi_env_bool($key, $default = false) {
    $value = rdi_env($key, null);
    if ($value === null) return (bool)$default;
    return in_array(strtolower(trim((string)$value)), array('1','true','yes','on'), true);
}
