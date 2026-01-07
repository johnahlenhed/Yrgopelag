<?php
// Redirects all traffic to public/

// Get the requested path
$request = $_SERVER['REQUEST_URI'];

// Remove any query string
$path = strtok($request, '?');
$query = $_SERVER['QUERY_STRING'] ?? '';

// If requesting root or index.php, go to public/
if ($path === '/' || $path === '/index.php') {
    header('Location: /public/index.php' . ($query ? '?' . $query : ''));
    exit;
}

// For other files, redirect to public/ version
header('Location: /public' . $path . ($query ? '?' . $query : ''));
exit;
