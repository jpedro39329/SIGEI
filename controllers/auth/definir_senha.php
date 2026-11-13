<?php
// ============================================================
// SIGEI - DEFINIÇÃO DA SENHA DEFINITIVA (primeiro acesso)
// Substitui a senha provisória criada pelo administrador.
// ============================================================
require_once "../../config/init.php";

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function responderSenha(int $codigo, bool $sucesso, string $msg): void
{
    http_response_code($codigo);
    echo json_encode(['sucesso' => $sucesso, 'erro' => $sucesso ? null : $msg, 'mensagem' => $sucesso ? $msg : null]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderSenha(405, false, 'Método não permitido.');
}
if (!estaLogado()) {
    responderSenha(401, false, 'Usuário não autenticado.');
}
if (!validarTokenCSRF($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''))) {
    responderSenha(403, false, 'Token de segurança inválido. Recarregue a página.');
}
if (empty($_SESSION['senha_provisoria'])) {
    responderSenha(400, false, 'Sua senha já é definitiva.');
}

$tabela  = (string) ($_SESSION['user_tabela'] ?? '');
$mapa    = tabelasSenhaProvisoria();
$userId  = (int) ($_SESSION['user_id'] ?? 0);
if ($userId <= 0 || !isset($mapa[$tabela])) {
    responderSenha(400, false, 'Dados de sessão inválidos.');
}
$campoId = $mapa[$tabela];

$nova      = (string) ($_POST['nova_senha'] ?? '');
$confirmar = (string) ($_POST['confirmar_senha'] ?? '');

if (mb_strlen($nova, 'UTF-8') < 8) {
    responderSenha(422, false, 'A senha deve ter pelo menos 8 caracteres.');
}
if (strlen($nova) > 72) {
    responderSenha(422, false, 'A senha deve ter no máximo 72 caracteres.');
}
if (preg_match('/\s/', $nova)) {
    responderSenha(422, false, 'A senha não pode conter espaços.');
}
if (!preg_match('/[A-Za-z]/', $nova) || !preg_match('/\d/', $nova)) {
    responderSenha(422, false, 'Use letras e números na senha.');
}
if (!preg_match('/[^A-Za-z0-9\s]/', $nova)) {
    responderSenha(422, false, 'Inclua ao menos um símbolo na senha.');
}
if ($nova !== $confirmar) {
    responderSenha(422, false, 'As senhas não coincidem.');
}

// A nova senha não pode ser igual à provisória atual
$stmt = $conexao->prepare("SELECT senha FROM `$tabela` WHERE `$campoId` = ? LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$atual = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$atual) {
    responderSenha(404, false, 'Usuário não encontrado.');
}
if (password_verify($nova, $atual['senha'])) {
    responderSenha(422, false, 'Escolha uma senha diferente da provisória.');
}

$hash = password_hash($nova, PASSWORD_DEFAULT);
$stmt = $conexao->prepare("UPDATE `$tabela` SET senha = ?, senha_provisoria = 0 WHERE `$campoId` = ?");
if (!$stmt) {
    error_log('SIGEI: coluna senha_provisoria ausente? ' . $conexao->error);
    responderSenha(500, false, 'Não foi possível salvar a senha. Contate o suporte.');
}
$stmt->bind_param("si", $hash, $userId);
if (!$stmt->execute()) {
    error_log('SIGEI: erro ao definir senha: ' . $stmt->error);
    responderSenha(500, false, 'Não foi possível salvar a senha. Tente novamente.');
}
$stmt->close();

$_SESSION['senha_provisoria'] = false;
session_regenerate_id(true);

registrarAuditoria($conexao, 'AUTH', 'DEFINIR_SENHA', $tabela, $userId, [
    'perfil' => $_SESSION['user_perfil'] ?? ''
]);

responderSenha(200, true, 'Senha definida com sucesso.');
