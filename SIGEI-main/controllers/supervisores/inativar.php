<?php
require_once "../../config/init.php";

exigirPerfil(array('ADMIN', 'SEDUC'));

$id = (int) ($_GET['id'] ?? 0);
$token = $_GET['csrf_token'] ?? '';

if (!validarTokenCSRF($token)) {
    header("Location: ../../views/supervisores/listar.php?erro=" . urlencode("Token de segurança inválido."));
    exit();
}

if ($id <= 0) {
    header("Location: ../../views/supervisores/listar.php?erro=" . urlencode("Supervisor não informado."));
    exit();
}

$stmt = $conexao->prepare("UPDATE usuarios_supervisor SET ativo = IF(ativo = 1, 0, 1) WHERE id_usuario_supervisor = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: ../../views/supervisores/listar.php?msg=inativado");
    exit();
}

header("Location: ../../views/supervisores/listar.php?erro=" . urlencode("Erro ao alterar status: " . $stmt->error));
exit();

