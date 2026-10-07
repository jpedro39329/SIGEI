<?php
require_once "../../config/init.php";

exigirPerfil(array('USUARIO_SEFISC', 'ADMIN', 'SEDUC'));

$id = (int) ($_GET['id'] ?? 0);
$token = $_GET['csrf_token'] ?? '';

if (!validarTokenCSRF($token)) {
    header("Location: ../../views/usuarios_ue/listar.php?erro=" . urlencode("Token de segurança inválido."));
    exit();
}

if ($id <= 0) {
    header("Location: ../../views/usuarios_ue/listar.php?erro=" . urlencode("Usuário não informado."));
    exit();
}

// Busca dados atuais do usuário para auditoria
$stmtBusca = $conexao->prepare("SELECT nome, cpf, ativo FROM usuarios_ue WHERE id_usuario_ue = ?");
$stmtBusca->bind_param("i", $id);
$stmtBusca->execute();
$usuario = $stmtBusca->get_result()->fetch_assoc();
$stmtBusca->close();

if (!$usuario) {
    header("Location: ../../views/usuarios_ue/listar.php?erro=" . urlencode("Usuário não encontrado."));
    exit();
}

$novoStatus = ((int)$usuario['ativo'] === 1) ? 0 : 1;
$stmt = $conexao->prepare("UPDATE usuarios_ue SET ativo = ? WHERE id_usuario_ue = ?");
$stmt->bind_param("ii", $novoStatus, $id);

if ($stmt->execute()) {
    $acaoAudit = ($novoStatus === 1) ? 'ATIVAR' : 'INATIVAR';
    registrarAuditoria($conexao, 'USUARIOS', $acaoAudit, 'usuarios_ue', $id, [
        'nome' => $usuario['nome'],
        'cpf' => $usuario['cpf'],
        'novo_status' => $novoStatus
    ]);

    $msg = ($novoStatus === 1) ? 'ativado' : 'inativado';
    header("Location: ../../views/usuarios_ue/listar.php?msg=" . urlencode($msg));
    exit();
}

header("Location: ../../views/usuarios_ue/listar.php?erro=" . urlencode("Erro ao alterar status: " . $stmt->error));
exit();

