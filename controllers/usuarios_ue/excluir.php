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

// 1. Busca dados do usuário para auditoria e histórico
$stmtUser = $conexao->prepare("SELECT nome, cpf, email, id_ue FROM usuarios_ue WHERE id_usuario_ue = ?");
$stmtUser->bind_param("i", $id);
$stmtUser->execute();
$dadosUser = $stmtUser->get_result()->fetch_assoc();
$stmtUser->close();

if (!$dadosUser) {
    header("Location: ../../views/usuarios_ue/listar.php?erro=" . urlencode("Usuário não encontrado."));
    exit();
}

// 2. Garante que qualquer aluno vinculado a este usuário preserve o histórico antes da desvinculação
$stmtPreserva = $conexao->prepare("
    UPDATE alunos 
    SET cadastrado_por_nome = IF(cadastrado_por_nome IS NULL OR cadastrado_por_nome = '', ?, cadastrado_por_nome),
        cadastrado_por_cpf  = IF(cadastrado_por_cpf IS NULL OR cadastrado_por_cpf = '', ?, cadastrado_por_cpf)
    WHERE id_usuario_ue = ?
");
if ($stmtPreserva) {
    $stmtPreserva->bind_param("ssi", $dadosUser['nome'], $dadosUser['cpf'], $id);
    $stmtPreserva->execute();
    $stmtPreserva->close();
}

// 3. Desvincula o usuário dos alunos com segurança (SET NULL) para evitar bloqueio de FK ou cascade indesejado
$stmtDesvincula = $conexao->prepare("UPDATE alunos SET id_usuario_ue = NULL WHERE id_usuario_ue = ?");
if ($stmtDesvincula) {
    $stmtDesvincula->bind_param("i", $id);
    $stmtDesvincula->execute();
    $stmtDesvincula->close();
}

// 4. Executa a exclusão da conta do usuário
$stmt = $conexao->prepare("DELETE FROM usuarios_ue WHERE id_usuario_ue = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $stmt->close();

    // 5. Registra a exclusão na auditoria central preservando o autor da exclusão e quem foi excluído
    registrarAuditoria($conexao, 'USUARIOS', 'EXCLUIR', 'usuarios_ue', $id, [
        'usuario_excluido_nome' => $dadosUser['nome'],
        'usuario_excluido_cpf' => $dadosUser['cpf'],
        'usuario_excluido_email' => $dadosUser['email'],
        'id_ue' => $dadosUser['id_ue']
    ]);

    header("Location: ../../views/usuarios_ue/listar.php?msg=excluido");
    exit();
}

header("Location: ../../views/usuarios_ue/listar.php?erro=" . urlencode("Erro ao excluir usuário: " . $stmt->error));
exit();
