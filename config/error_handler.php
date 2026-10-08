<?php
// ============================================================
// SIGEI - TRATAMENTO CENTRAL DE ERROS
// Intercepta erros e exceções para evitar tela branca ou HTTP 500 feio,
// registrando no log e exibindo um card limpo com o erro e botão Fechar.
// ============================================================

if (defined('SIGEI_ERROR_HANDLER')) {
    return;
}
define('SIGEI_ERROR_HANDLER', true);

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// Buffer de saída para evitar saída parcial corrompida
if (PHP_SAPI !== 'cli') {
    ob_start();
}

// Identifica se é requisição AJAX
function sigei_requisicao_ajax(): bool
{
    $xhr    = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
    $accept = stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;
    return $xhr || $accept;
}

// Descarta a saída atual e renderiza o card de erro
function sigei_responder_erro(int $codigo = 500, ?string $mensagem = null, ?string $arquivo = null, ?int $linha = null): void
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
            'erro'    => $mensagem ?? 'Ocorreu um problema ao processar a solicitação.',
            'onde'    => $arquivo ? ($arquivo . ($linha ? ':' . $linha : '')) : null
        ]);
        return;
    }

    if (!headers_sent()) {
        header('Content-Type: text/html; charset=UTF-8');
    }
    require_once __DIR__ . '/../includes/pagina_erro.php';
    sigei_pagina_erro($codigo, '/', $mensagem, $arquivo, $linha);
}

// Handler de avisos e erros do PHP
set_error_handler(function (int $errno, string $errstr, string $errfile = '', int $errline = 0): bool {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    error_log(sprintf('[SIGEI] PHP erro %d: %s em %s:%d', $errno, $errstr, $errfile, $errline));

    if (in_array($errno, [E_USER_ERROR, E_RECOVERABLE_ERROR], true)) {
        sigei_responder_erro(500, $errstr, $errfile, $errline);
        exit;
    }
    return true; // Suprime warnings soltos que poluiriam a interface
});

// Exceções não tratadas (ex.: mysqli_sql_exception, ArgumentCountError, TypeError)
set_exception_handler(function (Throwable $e): void {
    error_log('[SIGEI] Exceção: ' . get_class($e) . ': ' . $e->getMessage()
        . ' em ' . $e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString());
    sigei_responder_erro(500, $e->getMessage(), $e->getFile(), $e->getLine());
    exit;
});

// Erros fatais no encerramento (Parse error, memory exhausted, etc.)
register_shutdown_function(function (): void {
    $erro = error_get_last();
    $fatais = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];
    if ($erro && in_array($erro['type'], $fatais, true)) {
        error_log(sprintf('[SIGEI] Erro fatal: %s em %s:%d', $erro['message'], $erro['file'], $erro['line']));
        sigei_responder_erro(500, $erro['message'], $erro['file'], $erro['line']);
    }
});
