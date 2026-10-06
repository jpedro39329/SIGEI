<?php
// ============================================================
// SIGEI - CONTROLE DE INATIVIDADE DA SESSÃO (validação no servidor)
// Requer sessão iniciada e perfil_functions.php carregado.
// ============================================================

if (!defined('SIGEI_SESSAO_TIMEOUT')) {
    define('SIGEI_SESSAO_TIMEOUT', 600);      // 10 minutos sem atividade
}
if (!defined('SIGEI_SESSAO_AVISO')) {
    define('SIGEI_SESSAO_AVISO', 540);        // aviso aos 9 minutos
}

// URL do login informando que a sessão expirou
function sigei_url_login_expirada(): string
{
    return urlLogin() . '?aviso=sessao_expirada';
}

// Encerra a sessão atual por completo
function sigei_encerrar_sessao(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

(function () {
    if (!estaLogado()) {
        return;
    }

    // Telas de autenticação (login, logout, recuperação) não sofrem o bloqueio
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    if (strpos($script, '/auth/') !== false) {
        $_SESSION['ultima_atividade'] = time();
        return;
    }

    $ultima = $_SESSION['ultima_atividade'] ?? time();

    if ((time() - $ultima) > SIGEI_SESSAO_TIMEOUT) {
        sigei_encerrar_sessao();

        if (function_exists('sigei_requisicao_ajax') && sigei_requisicao_ajax()) {
            http_response_code(401);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['sucesso' => false, 'sessao_expirada' => true]);
            exit;
        }

        header('Location: ' . sigei_url_login_expirada());
        exit;
    }

    $_SESSION['ultima_atividade'] = time();
})();

