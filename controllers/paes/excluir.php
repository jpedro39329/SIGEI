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

$stmt = $conexao->prepare("DELETE FROM usuarios_pae WHERE id_pae = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: ../../views/paes/listar.php?msg=excluido");
    exit();
}

header("Location: ../../views/paes/listar.php?erro=" . urlencode("Erro ao excluir PAE: " . $stmt->error));
exit();

