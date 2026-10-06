<?php
// ============================================================
// SIGEI - TRATAMENTO CENTRAL DE ERROS
// Esconde erros técnicos do usuário, registra tudo no log
// e exibe uma página/resposta amigável.
// ============================================================

if (defined('SIGEI_ERROR_HANDLER')) {
    return;
}
define('SIGEI_ERROR_HANDLER', true);

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// Buffer de saída: permite descartar HTML parcial caso ocorra um erro fatal no meio da página
if (PHP_SAPI !== 'cli') {
    ob_start();
}

// Identifica requisições AJAX/fetch que esperam JSON
function sigei_requisicao_ajax(): bool
{
    $xhr    = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
    $accept = stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;
    return $xhr || $accept;
}

// Descarta a saída atual e mostra a página de erro amigável
function sigei_responder_erro(int $codigo = 500): void
{
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }

    if (!headers_sent()) {
        http_response_code($codigo);
        header('Cache-Control: no-store');
    }

    if (sigei_requisicao_ajax()) {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=UTF-8');
        }
        echo json_encode([
            'sucesso' => false,
            'erro'    => 'Ocorreu um problema. Tente novamente.'
        ]);
        return;
    }

    if (!headers_sent()) {
        header('Content-Type: text/html; charset=UTF-8');
    }
    require_once __DIR__ . '/../includes/pagina_erro.php';
    sigei_pagina_erro($codigo);
}

// Avisos e notices: apenas registra no log (não interrompe a lógica existente)
set_error_handler(function (int $errno, string $errstr, string $errfile = '', int $errline = 0): bool {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    error_log(sprintf('[SIGEI] PHP erro %d: %s em %s:%d', $errno, $errstr, $errfile, $errline));

    if ($errno === E_USER_ERROR) {
        sigei_responder_erro(500);
        exit;
    }
    return true; // impede a exibição padrão do PHP
});

// Exceções não tratadas (inclui erros de banco mysqli_sql_exception)
set_exception_handler(function (Throwable $e): void {
    error_log('[SIGEI] Exceção não tratada: ' . get_class($e) . ': ' . $e->getMessage()
        . ' em ' . $e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString());
    sigei_responder_erro(500);
});

// Erros fatais (memória, sintaxe em include, etc.)
register_shutdown_function(function (): void {
    $erro = error_get_last();
    $fatais = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];
    if ($erro && in_array($erro['type'], $fatais, true)) {
        error_log(sprintf('[SIGEI] Erro fatal: %s em %s:%d', $erro['message'], $erro['file'], $erro['line']));
        sigei_responder_erro(500);
    }
});

