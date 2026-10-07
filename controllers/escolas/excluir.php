<?php
require_once "../../config/init.php";

exigirPerfil(array('DIRIGENTE', 'ADMIN', 'SEDUC'));

$id = (int) ($_GET['id'] ?? 0);
$token = $_GET['csrf_token'] ?? '';

if (!validarTokenCSRF($token)) {
    header("Location: ../../views/escolas/listar.php?erro=" . urlencode("Token de segurança inválido."));
    exit();
}

if ($id <= 0) {
    header("Location: ../../views/escolas/listar.php?erro=" . urlencode("Escola não informada."));
    exit();
}

// Verifica se existem alunos matriculados
$stmtAlunos = $conexao->prepare("SELECT COUNT(*) as total FROM alunos WHERE id_ue = ?");
$stmtAlunos->bind_param("i", $id);
$stmtAlunos->execute();
$totalAlunos = $stmtAlunos->get_result()->fetch_assoc()['total'];

if ($totalAlunos > 0) {
    header("Location: ../../views/escolas/listar.php?erro=" . urlencode("Não é possível excluir esta escola pois existem $totalAlunos aluno(s) cadastrado(s)."));
    exit();
}

// Verifica se existem usuários vinculados a esta escola
$stmtUsuarios = $conexao->prepare("SELECT COUNT(*) as total FROM usuarios_ue WHERE id_ue = ?");
$stmtUsuarios->bind_param("i", $id);
$stmtUsuarios->execute();
$totalUsuarios = $stmtUsuarios->get_result()->fetch_assoc()['total'];

if ($totalUsuarios > 0) {
    header("Location: ../../views/escolas/listar.php?erro=" . urlencode("Não é possível excluir esta escola pois existem $totalUsuarios usuário(s) vinculado(s)."));
    exit();
}

$stmt = $conexao->prepare("DELETE FROM unidades_escolares WHERE id_ue = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $stmt->close();
    registrarAuditoria($conexao, 'ESCOLAS', 'EXCLUIR', 'unidades_escolares', $id, [
        'id_ue' => $id
    ]);
    header("Location: ../../views/escolas/listar.php?msg=excluido");
    exit();
}

header("Location: ../../views/escolas/listar.php?erro=" . urlencode("Erro ao excluir escola: " . $stmt->error));
exit();

