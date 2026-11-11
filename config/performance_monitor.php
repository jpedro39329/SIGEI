<?php

// ============================================================
// SIGEI - MONITORAMENTO LEVE DE DESEMPENHO (PERFORMANCE MONITOR)
// Registra tempos de execução de páginas, consultas SQL e operações lentas
// Sem armazenar senhas, tokens, CPFs ou dados pessoais sensíveis.
// ============================================================

if (!defined('SIGEI_PERF_START_NS')) {
    define('SIGEI_PERF_START_NS', hrtime(true));
}

if (!defined('SIGEI_SLOW_REQUEST_THRESHOLD_MS')) {
    define('SIGEI_SLOW_REQUEST_THRESHOLD_MS', 500.0);
}

if (!defined('SIGEI_SLOW_QUERY_THRESHOLD_MS')) {
    define('SIGEI_SLOW_QUERY_THRESHOLD_MS', 100.0);
}

$GLOBALS['_sigei_perf_metrics'] = [
    'queries_count' => 0,
    'queries_time_ms' => 0.0,
    'slow_queries' => [],
];

/**
 * Sanitiza uma consulta SQL ou mensagem antes de registrar em log,
 * removendo literais sensíveis (CPFs, senhas, tokens, strings longas).
 */
function sigeiSanitizarSqlParaLog($sql) {
    $limpo = preg_replace('/\s+/', ' ', trim((string) $sql));
    // Mascara strings entre aspas simples ou duplas para nunca vazar CPFs, nomes, senhas ou tokens
    $limpo = preg_replace("/'[^']*'/", "'?'", $limpo);
    $limpo = preg_replace('/"[^"]*"/', '"?"', $limpo);
    // Mascara sequências numéricas de 8+ dígitos (ex: CPF/RA/CNPJ sem aspas)
    $limpo = preg_replace('/\b\d{8,14}\b/', '?', $limpo);
    if (strlen($limpo) > 260) {
        $limpo = substr($limpo, 0, 260) . '...';
    }
    return $limpo;
}

/**
 * Executa uma consulta mysqli medindo o tempo de execução e registrando consultas lentas.
 */
function sigeiQuery($conexao, $sql) {
    $inicio = hrtime(true);
    $resultado = mysqli_query($conexao, $sql);
    $duracaoMs = (hrtime(true) - $inicio) / 1e6;

    if (isset($GLOBALS['_sigei_perf_metrics'])) {
        $GLOBALS['_sigei_perf_metrics']['queries_count']++;
        $GLOBALS['_sigei_perf_metrics']['queries_time_ms'] += $duracaoMs;

        if ($duracaoMs >= SIGEI_SLOW_QUERY_THRESHOLD_MS) {
            $GLOBALS['_sigei_perf_metrics']['slow_queries'][] = [
                'ms' => round($duracaoMs, 2),
                'sql' => sigeiSanitizarSqlParaLog($sql)
            ];
        }
    }

    return $resultado;
}

/**
 * Registra manualmente uma métrica de consulta/operação no monitor de performance.
 */
function sigeiRegistrarTempoOperacao($duracaoMs, $descricaoSqlOuOp = '') {
    if (!isset($GLOBALS['_sigei_perf_metrics'])) {
        return;
    }
    $GLOBALS['_sigei_perf_metrics']['queries_count']++;
    $GLOBALS['_sigei_perf_metrics']['queries_time_ms'] += (float) $duracaoMs;

    if ($duracaoMs >= SIGEI_SLOW_QUERY_THRESHOLD_MS && $descricaoSqlOuOp !== '') {
        $GLOBALS['_sigei_perf_metrics']['slow_queries'][] = [
            'ms' => round((float) $duracaoMs, 2),
            'sql' => sigeiSanitizarSqlParaLog($descricaoSqlOuOp)
        ];
    }
}

/**
 * Retorna as métricas acumuladas da requisição atual.
 */
function sigeiObterMetricasPerformance() {
    $totalMs = (hrtime(true) - SIGEI_PERF_START_NS) / 1e6;
    $metrics = $GLOBALS['_sigei_perf_metrics'] ?? ['queries_count' => 0, 'queries_time_ms' => 0.0, 'slow_queries' => []];
    return [
        'total_request_ms' => round($totalMs, 2),
        'queries_count' => (int) $metrics['queries_count'],
        'queries_time_ms' => round((float) $metrics['queries_time_ms'], 2),
        'slow_queries_count' => count($metrics['slow_queries'])
    ];
}

// Emite cabeçalho Server-Timing (visível no DevTools -> Network -> Timing) antes do envio de headers
if (function_exists('header_register_callback')) {
    header_register_callback(function () {
        if (!headers_sent()) {
            $m = sigeiObterMetricasPerformance();
            header(sprintf(
                'Server-Timing: app;dur=%.2f;desc="PHP Total", db;dur=%.2f;desc="SQL (%d queries)"',
                $m['total_request_ms'],
                $m['queries_time_ms'],
                $m['queries_count']
            ));
        }
    });
}

// Registra no log apenas operações ou endpoints que ultrapassem o limite configurado
register_shutdown_function(function () {
    $totalMs = (hrtime(true) - SIGEI_PERF_START_NS) / 1e6;
    $metrics = $GLOBALS['_sigei_perf_metrics'] ?? ['queries_count' => 0, 'queries_time_ms' => 0.0, 'slow_queries' => []];
    $temSlowQuery = !empty($metrics['slow_queries']);

    if ($totalMs < SIGEI_SLOW_REQUEST_THRESHOLD_MS && !$temSlowQuery) {
        return;
    }

    $logDir = dirname(__DIR__) . '/uploads/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    $htaccessPath = $logDir . '/.htaccess';
    if (is_dir($logDir) && !is_file($htaccessPath)) {
        @file_put_contents($htaccessPath, "Order allow,deny\nDeny from all\n<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n");
    }

    // Sanitiza a URI removendo qualquer query string potencialmente sensível (tokens, buscas por CPF/nome)
    $uriPath = parse_url($_SERVER['REQUEST_URI'] ?? ($_SERVER['SCRIPT_NAME'] ?? 'cli'), PHP_URL_PATH) ?: 'unknown';
    $metodo = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
    $perfil = $_SESSION['user_perfil'] ?? 'ANON';

    $registro = [
        'timestamp' => date('Y-m-d H:i:s'),
        'method' => $metodo,
        'endpoint' => $uriPath,
        'perfil' => $perfil,
        'total_ms' => round($totalMs, 2),
        'db_queries' => (int) $metrics['queries_count'],
        'db_ms' => round((float) $metrics['queries_time_ms'], 2),
    ];

    if ($temSlowQuery) {
        $registro['slow_queries'] = $metrics['slow_queries'];
    }

    $linha = json_encode($registro, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    @file_put_contents($logDir . '/performance_slow.log', $linha, FILE_APPEND | LOCK_EX);
});
