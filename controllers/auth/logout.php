<?php
require_once "../../config/init.php";

if (isset($_SESSION['user_id'])) {
    registrarAuditoria($conexao, 'AUTH', 'LOGOUT', 'sessao', (int)$_SESSION['user_id'], [
        'perfil' => $_SESSION['user_perfil'] ?? ''
    ]);
}

// Destrói todas as variáveis de sessão
$_SESSION = [];

// Invalida o cookie de sessão no navegador
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}

// Destrói a sessão no servidor
session_destroy();

// Redireciona para a tela inicial/login
header("Location: ../../index.html");
exit();
