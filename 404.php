<?php
// ============================================================
// SIGEI - PÁGINA 404 (acionada pelo .htaccess)
// ============================================================
require_once __DIR__ . '/config/error_handler.php';
require_once __DIR__ . '/includes/pagina_erro.php';

http_response_code(404);
header('Content-Type: text/html; charset=UTF-8');

// Raiz da aplicação (ex.: "/" na hospedagem ou "/SIGEI/" no XAMPP)
$raiz = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/') . '/';

sigei_pagina_erro(404, $raiz);

