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

// Alterna o status ativo / inativo
$stmt = $conexao->prepare("UPDATE empresas SET ativo = IF(ativo = 1, 0, 1) WHERE id_empresa = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: ../../views/empresas/listar.php?msg=inativado");
    exit();
}

header("Location: ../../views/empresas/listar.php?erro=" . urlencode("Erro ao alterar status: " . $stmt->error));
exit();

