<?php
/**
 * Router para el servidor built-in de PHP.
 * Strips /quiniela-php/ prefix and serves files from
 * /home/runner/workspace/quiniela-php/
 */

$basePath = '/quiniela-php';
$docRoot  = '/home/runner/workspace/quiniela-php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = $uri ?: '/';

// Redirect bare root → login
if ($uri === '/' || $uri === '') {
    header('Location: /quiniela-php/login.php', true, 302);
    exit;
}

// Strip the /quiniela-php prefix
if (strpos($uri, $basePath) === 0) {
    $stripped = substr($uri, strlen($basePath));
    $uri = ($stripped === '' || $stripped === false) ? '/' : $stripped;
}

// Default to index
if ($uri === '' || $uri === '/') {
    $uri = '/index.php';
}

$uri  = '/' . ltrim($uri, '/');
$file = $docRoot . $uri;

// Security: block path traversal
$real = realpath($file);
if ($real !== false && strpos($real, realpath($docRoot)) !== 0) {
    http_response_code(403);
    exit('Forbidden');
}

// Static files — serve directly with correct MIME type
if (is_file($file)) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

    if ($ext !== 'php') {
        $mimes = [
            'css'   => 'text/css; charset=utf-8',
            'js'    => 'application/javascript; charset=utf-8',
            'png'   => 'image/png',
            'jpg'   => 'image/jpeg',
            'jpeg'  => 'image/jpeg',
            'gif'   => 'image/gif',
            'svg'   => 'image/svg+xml',
            'ico'   => 'image/x-icon',
            'woff'  => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf'   => 'font/ttf',
            'json'  => 'application/json',
            'txt'   => 'text/plain; charset=utf-8',
        ];
        header('Content-Type: ' . ($mimes[$ext] ?? 'application/octet-stream'));
        header('Content-Length: ' . filesize($file));
        readfile($file);
        return true;
    }

    // PHP file — execute it
    chdir(dirname($file));
    $_SERVER['SCRIPT_FILENAME'] = $file;
    $_SERVER['SCRIPT_NAME']     = $basePath . $uri;
    $_SERVER['PHP_SELF']        = $basePath . $uri;
    include $file;
    return true;
}

// Directory — look for index.php
if (is_dir($file)) {
    $index = rtrim($file, '/') . '/index.php';
    if (is_file($index)) {
        chdir(dirname($index));
        $_SERVER['SCRIPT_FILENAME'] = $index;
        $_SERVER['SCRIPT_NAME']     = $basePath . rtrim($uri, '/') . '/index.php';
        $_SERVER['PHP_SELF']        = $_SERVER['SCRIPT_NAME'];
        include $index;
        return true;
    }
}

// 404
http_response_code(404);
echo '<!DOCTYPE html><html lang="es"><body style="font-family:sans-serif;background:#0f172a;color:#f1f5f9;padding:2rem">'
   . '<h2>404 — No encontrado</h2><p>' . htmlspecialchars($uri) . '</p>'
   . '<a href="/quiniela-php/" style="color:#22c55e">← Inicio</a></body></html>';
