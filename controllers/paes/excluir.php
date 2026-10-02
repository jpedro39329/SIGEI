<?php
require_once "../../config/init.php";

exigirPerfil(array('SUPERVISOR', 'PERFIL_SUPERVISOR','USUARIO_EMPRESA'));

$id = (int) ($_GET['id'] ?? 0);
$token = $_GET['csrf_token'] ?? '';

if (!validarTokenCSRF($token)) {
    header("Location: ../../views/paes/listar.php?erro=" . urlencode("Token de segurança inválido."));
    exit();
}

if ($id <= 0) {
    header("Location: ../../views/paes/listar.php?erro=" . urlencode("PAE não informado."));
    exit();
} 

$stmtAssociacoes = $conexao->prepare("SELECT COUNT(*) AS total FROM associacoes WHERE id_pae = ?");
$stmtAssociacoes->bind_param("i", $id);
$stmtAssociacoes->execute();
$totalAssociacoes = (int) $stmtAssociacoes->get_result()->fetch_assoc()['total'];
$stmtAssociacoes->close();

if ($totalAssociacoes > 0) {
    header("Location: ../../views/paes/listar.php?erro=" . urlencode("Não é possível excluir este PAE porque existem alunos associados."));
    exit();
}

try {
    $stmt = $conexao->prepare("DELETE FROM usuarios_pae WHERE id_pae = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
} catch (mysqli_sql_exception $exception) {
    header("Location: ../../views/paes/listar.php?erro=" . urlencode("Não foi possível excluir o PAE. Verifique se ele possui vínculos."));
    exit();
}

header("Location: ../../views/paes/listar.php?msg=excluido");
exit();

