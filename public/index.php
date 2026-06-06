<?php
$request = $_SERVER['REQUEST_URI'];
$file = dirname(__DIR__) . parse_url($request, PHP_URL_PATH);

if (is_file($file)) {
    return false;
}

$page = ltrim(parse_url($request, PHP_URL_PATH), '/');
$target = dirname(__DIR__) . '/' . ($page ?: 'index.php');

if (is_file($target)) {
    require $target;
} else {
    require dirname(__DIR__) . '/index.php';
}