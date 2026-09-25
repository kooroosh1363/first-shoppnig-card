<?php

declare(strict_types=1);

require_once __DIR__ . '/ProductCatalog.php';
require_once __DIR__ . '/CartService.php';
require_once __DIR__ . '/helpers.php';

if (PHP_SAPI !== 'cli') {
    $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $isHttps,
        'samesite' => 'Lax',
        'path' => '/',
    ]);

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    header("Content-Security-Policy: default-src 'self'; style-src 'self'; form-action 'self'; base-uri 'none'; frame-ancestors 'none'");
    header('Referrer-Policy: no-referrer');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Cache-Control: no-store');
}

$productCatalog = new ProductCatalog();
$cartService = new CartService($productCatalog);

if (PHP_SAPI !== 'cli') {
    save_session_cart($cartService->normalize(session_cart()));
}
