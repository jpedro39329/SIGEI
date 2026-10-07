<?php
require_once "../../config/init.php";

exigirPerfil(array('SUPERVISOR', 'PERFIL_SUPERVISOR','USUARIO_EMPRESA'));

$id = (int) ($_GET['id'] ?? 0);
$userPerfil = $_SESSION['user_perfil'];
$userId = (int) $_SESSION['user_id'];
$token = $_GET['csrf_token'] ?? '';

if (!validarTokenCSRF($token)) {
    header("Location: ../../views/paes/listar.php?erro=" . urlencode("Token de segurança inválido."));
    exit();
}

if ($id <= 0) {
    header("Location: ../../views/paes/listar.php?erro=" . urlencode("PAE não informado."));
    exit();
} 

$idEmpresa = 0;
if (!in_array($userPerfil, ['ADMIN', 'SEDUC'])) {
    $idEmpresa = idEmpresaSupervisor($conexao, $userId);
    $stmtVerifica = $conexao->prepare("SELECT id_pae FROM usuarios_pae WHERE id_pae = ? AND id_empresa = ?");
    $stmtVerifica->bind_param("ii", $id, $idEmpresa);
    $stmtVerifica->execute();
    if ($stmtVerifica->get_result()->num_rows === 0) {
        header("Location: ../../views/paes/listar.php?erro=" . urlencode("PAE não encontrado ou não pertence à sua empresa."));
        exit();
    }
    $stmtVerifica->close();
}

$stmtAssociacoes = $conexao->prepare("SELECT COUNT(*) AS total FROM associacoes WHERE id_pae = ? AND ativo = 1");
$stmtAssociacoes->bind_param("i", $id);
$stmtAssociacoes->execute();
$totalAssociacoes = (int) $stmtAssociacoes->get_result()->fetch_assoc()['total'];
$stmtAssociacoes->close();

if ($totalAssociacoes > 0) {
    header("Location: ../../views/paes/listar.php?erro=" . urlencode("Desassocie todos os alunos antes de inativar este PAE."));
    exit();
}

try {
    $stmt = $conexao->prepare("UPDATE usuarios_pae SET ativo = 0 WHERE id_pae = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    registrarAuditoria($conexao, 'PAE', 'INATIVAR', 'usuarios_pae', $id, [
        'id_empresa' => $idEmpresa,
        'motivo' => 'Inativação via painel de supervisão'
    ]);
} catch (mysqli_sql_exception $exception) {
    header("Location: ../../views/paes/listar.php?erro=" . urlencode("Não foi possível inativar o PAE."));
    exit();
}

header("Location: ../../views/paes/listar.php?msg=inativado");
exit();

