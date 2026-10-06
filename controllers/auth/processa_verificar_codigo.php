<?php
// controllers/auth/processa_verificar_codigo.php
// Etapa 2 da recuperação de senha: valida o código digitado pelo usuário.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../config/database.php';

const RECUPERACAO_MAX_TENTATIVAS = 5;

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['recuperacao_email'])) {
    header('Location: ../../views/auth/esqueciminha_senha.php');
    exit;
}

$email  = $_SESSION['recuperacao_email'];
$codigo = preg_replace('/\D/', '', $_POST['codigo'] ?? '');

$_SESSION['recuperacao_tentativas'] = ($_SESSION['recuperacao_tentativas'] ?? 0) + 1;

if ($_SESSION['recuperacao_tentativas'] > RECUPERACAO_MAX_TENTATIVAS) {
    // Bloqueia: invalida o código e obriga a solicitar um novo
    $stmtInv = $conexao->prepare("UPDATE recuperacao_senha SET usado = 1 WHERE contato = ? AND usado = 0");
    $stmtInv->bind_param("s", $email);
    $stmtInv->execute();

    unset($_SESSION['recuperacao_email'], $_SESSION['recuperacao_tentativas'], $_SESSION['recuperacao_codigo_teste']);
    $_SESSION['recuperacao_msg_erro'] = 'Número máximo de tentativas excedido. Solicite um novo código.';
    header('Location: ../../views/auth/esqueciminha_senha.php');
    exit;
}

$recuperacao = null;
if (strlen($codigo) === 6) {
    $sql = "SELECT token FROM recuperacao_senha
            WHERE contato = ? AND codigo = ? AND usado = 0 AND expira_em >= NOW()
            ORDER BY expira_em DESC LIMIT 1";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("ss", $email, $codigo);
    $stmt->execute();
    $recuperacao = $stmt->get_result()->fetch_assoc();
}

if (!$recuperacao) {
    $restantes = RECUPERACAO_MAX_TENTATIVAS - $_SESSION['recuperacao_tentativas'];
    $_SESSION['recuperacao_msg_erro'] = "Código incorreto ou expirado. Tentativas restantes: {$restantes}.";
    header('Location: ../../views/auth/verificar_codigo.php');
    exit;
}

// Código válido: libera a etapa de nova senha
session_regenerate_id(true);
$_SESSION['recuperacao_id'] = $recuperacao['token'];
$_SESSION['recuperacao_verificada'] = true;
unset($_SESSION['recuperacao_codigo_teste']);

header('Location: ../../views/auth/redefinir_senha.php');
exit;
