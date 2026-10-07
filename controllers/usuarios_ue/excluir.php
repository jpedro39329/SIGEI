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

// Verifica se existem alunos vinculados/cadastrados por este usuário
$stmtAlunos = $conexao->prepare("SELECT COUNT(*) as total FROM alunos WHERE id_usuario_ue = ?");
$stmtAlunos->bind_param("i", $id);
$stmtAlunos->execute();
$totalAlunos = (int) $stmtAlunos->get_result()->fetch_assoc()['total'];
$stmtAlunos->close();

if ($totalAlunos > 0) {
    header("Location: ../../views/usuarios_ue/listar.php?erro=" . urlencode("Não é possível excluir este usuário pois existem $totalAlunos aluno(s) cadastrado(s) por ele."));
    exit();
}

$stmt = $conexao->prepare("DELETE FROM usuarios_ue WHERE id_usuario_ue = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: ../../views/usuarios_ue/listar.php?msg=excluido");
    exit();
}

header("Location: ../../views/usuarios_ue/listar.php?erro=" . urlencode("Erro ao excluir usuário: " . $stmt->error));
exit();
