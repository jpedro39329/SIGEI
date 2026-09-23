<?php
require_once "../../config/init.php";

exigirPerfil(array('ADMIN', 'SEDUC'));

$id = (int) ($_GET['id'] ?? 0);
$token = $_GET['csrf_token'] ?? '';

if (!validarTokenCSRF($token)) {
    header("Location: ../../views/empresas/listar.php?erro=" . urlencode("Token de segurança inválido."));
    exit();
}

if ($id <= 0) {
    header("Location: ../../views/empresas/listar.php?erro=" . urlencode("Empresa não informada."));
    exit();
}

// Verifica se existem supervisores ou PAEs vinculados
$stmtSup = $conexao->prepare("SELECT COUNT(*) as total FROM usuarios_supervisor WHERE id_empresa = ?");
$stmtSup->bind_param("i", $id);
$stmtSup->execute();
$totalSup = $stmtSup->get_result()->fetch_assoc()['total'];

$stmtPae = $conexao->prepare("SELECT COUNT(*) as total FROM usuarios_pae WHERE id_empresa = ?");
$stmtPae->bind_param("i", $id);
$stmtPae->execute();
$totalPae = $stmtPae->get_result()->fetch_assoc()['total'];

if ($totalSup > 0 || $totalPae > 0) {
    header("Location: ../../views/empresas/listar.php?erro=" . urlencode("Não é possível excluir esta empresa pois existem $totalSup supervisor(es) e $totalPae PAE(s) cadastrados. Considere inativá-la."));
    exit();
}

$stmt = $conexao->prepare("DELETE FROM empresas WHERE id_empresa = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: ../../views/empresas/listar.php?msg=excluido");
    exit();
}

header("Location: ../../views/empresas/listar.php?erro=" . urlencode("Erro ao excluir empresa: " . $stmt->error));
exit();

