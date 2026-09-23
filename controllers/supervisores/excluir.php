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

$stmt = $conexao->prepare("DELETE FROM usuarios_supervisor WHERE id_usuario_supervisor = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: ../../views/supervisores/listar.php?msg=excluido");
    exit();
}

header("Location: ../../views/supervisores/listar.php?erro=" . urlencode("Erro ao excluir supervisor: " . $stmt->error));
exit();

