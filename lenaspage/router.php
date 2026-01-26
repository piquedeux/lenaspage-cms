<?php
// Router for PHP built-in server

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Serve static files directly
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false;
}

// Route admin requests
if ($uri === '/admin.php' || strpos($uri, '/admin.php') === 0) {
    require __DIR__ . '/admin.php';
    return true;
}

// Route all other requests through index.php
$_SERVER['REQUEST_URI'] = $uri;
require __DIR__ . '/index.php';
