<?php

// ============================================================
// SIGEI - INICIALIZAÇÃO CENTRAL
// Sessão + conexão + funções auxiliares
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');

    $isHttps = (
        (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443)
        || (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https')
    );

    if ($isHttps) {
        ini_set('session.cookie_secure', '1');
    }

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

// Cabeçalhos de segurança e anti-cache: impede que o botão "Voltar" exiba páginas
// autenticadas (dados pessoais/LGPD) após o logout. Endpoints que precisam de cache
// (ex.: controllers/arquivo.php) sobrescrevem o Cache-Control com header().
if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

require_once __DIR__ . '/performance_monitor.php';
require_once __DIR__ . '/error_handler.php';
require_once __DIR__ . '/database.php';
if (isset($conexao) && $conexao instanceof mysqli) {
    $conexao->set_charset('utf8mb4');
}
require_once __DIR__ . '/perfil_functions.php';
require_once __DIR__ . '/auditoria.php';
require_once __DIR__ . '/session_guard.php';

