<?php
// controllers/auth/processa_redefinir_senha.php
// Etapa 3 da recuperação de senha: define a nova senha após o código ter sido verificado.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../config/database.php';

const SENHA_TAMANHO_MINIMO = 6;

if ($_SERVER['REQUEST_METHOD'] !== 'POST'
    || empty($_SESSION['recuperacao_verificada'])
    || empty($_SESSION['recuperacao_id'])) {
    header('Location: ../../views/auth/esqueciminha_senha.php');
    exit;
}

$novaSenha     = $_POST['nova_senha'] ?? '';
$confirmaSenha = $_POST['confirma_senha'] ?? '';

if (strlen($novaSenha) < SENHA_TAMANHO_MINIMO) {
    $_SESSION['recuperacao_msg_erro'] = 'A senha deve ter pelo menos ' . SENHA_TAMANHO_MINIMO . ' caracteres.';
    header('Location: ../../views/auth/redefinir_senha.php');
    exit;
}

if ($novaSenha !== $confirmaSenha) {
    $_SESSION['recuperacao_msg_erro'] = 'As senhas não coincidem.';
    header('Location: ../../views/auth/redefinir_senha.php');
    exit;
}

$idRecuperacao = (string) $_SESSION['recuperacao_id'];

// Revalida: o código ainda precisa estar não utilizado e dentro da validade
$sql = "SELECT * FROM recuperacao_senha WHERE token = ? AND usado = 0 AND expira_em >= NOW()";
$stmt = $conexao->prepare($sql);
$stmt->bind_param("s", $idRecuperacao);
$stmt->execute();
$recuperacao = $stmt->get_result()->fetch_assoc();

$limparSessao = function () {
    unset(
        $_SESSION['recuperacao_id'],
        $_SESSION['recuperacao_verificada'],
        $_SESSION['recuperacao_email'],
        $_SESSION['recuperacao_tentativas'],
        $_SESSION['recuperacao_codigo_teste']
    );
};

if (!$recuperacao) {
    $limparSessao();
    $_SESSION['recuperacao_msg_erro'] = 'O código expirou ou já foi utilizado. Solicite um novo código.';
    header('Location: ../../views/auth/esqueciminha_senha.php');
    exit;
}

$tabelasPerfis = [
    'SEDUC'      => ['tabela' => 'seduc', 'id' => 'id_seduc'],
    'URE'        => ['tabela' => 'usuarios_ure', 'id' => 'id_usuario_ure'],
    'SUPERVISOR' => ['tabela' => 'usuarios_supervisor', 'id' => 'id_usuario_supervisor'],
    'ESCOLA'     => ['tabela' => 'usuarios_ue', 'id' => 'id_usuario_ue'],
    'PAE'        => ['tabela' => 'usuarios_pae', 'id' => 'id_pae']
];

$tipoPerfil   = $recuperacao['tipo_perfil'];
$idReferencia = $recuperacao['id_referencia'];

if (!isset($tabelasPerfis[$tipoPerfil])) {
    $limparSessao();
    $_SESSION['recuperacao_msg_erro'] = 'Não foi possível redefinir a senha. Solicite um novo código.';
    header('Location: ../../views/auth/esqueciminha_senha.php');
    exit;
}

$info = $tabelasPerfis[$tipoPerfil];
$hash = password_hash($novaSenha, PASSWORD_DEFAULT);

$stmtUpdate = $conexao->prepare("UPDATE {$info['tabela']} SET senha = ? WHERE {$info['id']} = ?");
$stmtUpdate->bind_param("si", $hash, $idReferencia);
$stmtUpdate->execute();

// Invalida o código para não ser reutilizado
$stmtToken = $conexao->prepare("UPDATE recuperacao_senha SET usado = 1 WHERE token = ?");
$stmtToken->bind_param("s", $idRecuperacao);
$stmtToken->execute();

$limparSessao();
$_SESSION['recuperacao_sucesso'] = true;
header('Location: ../../views/auth/redefinir_senha.php');
exit;
