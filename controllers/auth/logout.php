<?php
require_once "../../config/init.php";

if (isset($_SESSION['user_id'])) {
    registrarAuditoria($conexao, 'AUTH', 'LOGOUT', 'sessao', (int)$_SESSION['user_id'], [
        'perfil' => $_SESSION['user_perfil'] ?? ''
    ]);
}

// Destrói todas as variáveis de sessão e invalida o cookie
sigei_encerrar_sessao();

// Redireciona para a tela inicial/login
header("Location: ../../index.html");
exit();
