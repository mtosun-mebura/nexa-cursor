<?php

$publicPath = getcwd();

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

// Gzip CSS/JS/SVG locally: the PHP built-in server has no nginx compression.
if ($uri !== '/' && $uri !== '' && file_exists($publicPath.$uri) && is_file($publicPath.$uri)) {
    $ext = strtolower(pathinfo($uri, PATHINFO_EXTENSION));
    $gzipExtensions = ['css', 'js', 'mjs', 'svg', 'json', 'html', 'htm', 'txt', 'xml', 'map', 'vtt', 'csv'];
    $acceptsGzip = str_contains(strtolower((string) ($_SERVER['HTTP_ACCEPT_ENCODING'] ?? '')), 'gzip');

    if ($acceptsGzip && in_array($ext, $gzipExtensions, true) && function_exists('gzencode')) {
        $raw = file_get_contents($publicPath.$uri);
        if ($raw !== false && strlen($raw) >= 1024) {
            $gzipped = gzencode($raw, 5);
            if ($gzipped !== false && strlen($gzipped) < strlen($raw)) {
                $types = [
                    'css' => 'text/css; charset=UTF-8',
                    'js' => 'application/javascript; charset=UTF-8',
                    'mjs' => 'application/javascript; charset=UTF-8',
                    'svg' => 'image/svg+xml',
                    'json' => 'application/json',
                    'html' => 'text/html; charset=UTF-8',
                    'htm' => 'text/html; charset=UTF-8',
                    'txt' => 'text/plain; charset=UTF-8',
                    'xml' => 'application/xml',
                    'map' => 'application/json',
                    'vtt' => 'text/vtt; charset=UTF-8',
                    'csv' => 'text/csv; charset=UTF-8',
                ];

                header('Content-Type: '.($types[$ext] ?? 'application/octet-stream'));
                header('Content-Encoding: gzip');
                header('Vary: Accept-Encoding');
                header('Cache-Control: public, max-age=31536000, immutable');
                header('Content-Length: '.strlen($gzipped));
                echo $gzipped;

                return true;
            }
        }
    }

    return false;
}

$formattedDateTime = date('D M j H:i:s Y');

$requestMethod = $_SERVER['REQUEST_METHOD'];
$remoteAddress = $_SERVER['REMOTE_ADDR'].':'.$_SERVER['REMOTE_PORT'];

file_put_contents('php://stdout', "[$formattedDateTime] $remoteAddress [$requestMethod] URI: $uri\n");

require_once $publicPath.'/index.php';
