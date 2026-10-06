<?php
// ============================================================
// SIGEI - ENDPOINT DE SESSÃO
// ?acao=ping     -> renova a atividade (chamado pelo JS quando há interação)
// ?acao=encerrar -> encerra por inatividade e volta ao login
// A validação do timeout acontece antes, em config/session_guard.php (via init.php).
// ============================================================
require_once __DIR__ . '/../config/init.php';

$acao = $_GET['acao'] ?? 'ping';

if ($acao === 'encerrar') {
    sigei_encerrar_sessao();
    header('Location: ../views/auth/login.php?aviso=sessao_expirada');
    exit;
}

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

if (!estaLogado()) {
    http_response_code(401);
    echo json_encode(['sucesso' => false, 'sessao_expirada' => true]);
    exit;
}

echo json_encode(['sucesso' => true, 'timeout' => SIGEI_SESSAO_TIMEOUT]);

