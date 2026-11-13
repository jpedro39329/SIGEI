<?php
require_once "../../config/init.php";

exigirPerfil(array('DIRIGENTE', 'ADMIN', 'SEDUC'));

$id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
$token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';

if (!validarTokenCSRF($token)) {
    header("Location: ../../views/escolas/listar.php?erro=" . urlencode("Token de segurança inválido."));
    exit();
}

if ($id <= 0) {
    header("Location: ../../views/escolas/listar.php?erro=" . urlencode("Escola não informada."));
    exit();
}

$userPerfil = $_SESSION['user_perfil'] ?? '';
$userId = (int) ($_SESSION['user_id'] ?? 0);

$stmtEscola = $conexao->prepare("SELECT id_ue, id_ure, nome FROM unidades_escolares WHERE id_ue = ? LIMIT 1");
$stmtEscola->bind_param("i", $id);
$stmtEscola->execute();
$escolaAlvo = $stmtEscola->get_result()->fetch_assoc();
$stmtEscola->close();

if (!$escolaAlvo) {
    header("Location: ../../views/escolas/listar.php?erro=" . urlencode("Escola não encontrada."));
    exit();
}

if ($userPerfil === 'DIRIGENTE') {
    $idUre = (int) ($_SESSION['id_ure'] ?? idUreUsuario($conexao, $userId));
    if (($idUre <= 0 || (int) $escolaAlvo['id_ure'] !== $idUre)) {
        redirecionarAcessoNegado();
    }
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

