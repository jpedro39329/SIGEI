<?php
require_once "../../config/init.php";

exigirPerfil(array('ADMIN', 'SEDUC'));

$id = (int) ($_GET['id'] ?? 0);
$token = $_GET['csrf_token'] ?? '';

if (!validarTokenCSRF($token)) {
    header("Location: ../../views/ures/listar.php?erro=" . urlencode("Token de segurança inválido."));
    exit();
}

if ($id <= 0) {
    header("Location: ../../views/ures/listar.php?erro=" . urlencode("URE não informada."));
    exit();
}

// Verifica se existem escolas vinculadas
$stmtEscolas = $conexao->prepare("SELECT COUNT(*) as total FROM unidades_escolares WHERE id_ure = ?");
$stmtEscolas->bind_param("i", $id);
$stmtEscolas->execute();
$resEscolas = $stmtEscolas->get_result()->fetch_assoc();

if ($resEscolas && $resEscolas['total'] > 0) {
    header("Location: ../../views/ures/listar.php?erro=" . urlencode("Não é possível excluir esta URE pois existem " . $resEscolas['total'] . " escola(s) vinculada(s)."));
    exit();
}

// Verifica se existem servidores/usuários vinculados a esta regional
$stmtUsuarios = $conexao->prepare("SELECT COUNT(*) as total FROM usuarios_ure WHERE id_ure = ?");
$stmtUsuarios->bind_param("i", $id);
$stmtUsuarios->execute();
$totalUsuarios = (int) $stmtUsuarios->get_result()->fetch_assoc()['total'];
$stmtUsuarios->close();

if ($totalUsuarios > 0) {
    header("Location: ../../views/ures/listar.php?erro=" . urlencode("Não é possível excluir esta URE pois existem $totalUsuarios servidor(es) vinculado(s) a ela."));
    exit();
}

$stmt = $conexao->prepare("DELETE FROM unidades_regionais WHERE id_ure = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: ../../views/ures/listar.php?msg=excluido");
    exit();
}

header("Location: ../../views/ures/listar.php?erro=" . urlencode("Erro ao excluir URE: " . $stmt->error));
exit();

