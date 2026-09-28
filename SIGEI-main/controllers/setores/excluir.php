<?php
require_once "../../config/init.php";

exigirPerfil(array('DIRIGENTE', 'ADMIN', 'SEDUC'));

$id = (int) ($_GET['id'] ?? 0);
$token = $_GET['csrf_token'] ?? '';

if (!validarTokenCSRF($token)) {
    header("Location: ../../views/setores/listar.php?erro=" . urlencode("Token de segurança inválido."));
    exit();
}

if ($id <= 0) {
    header("Location: ../../views/setores/listar.php?erro=" . urlencode("Servidor não informado."));
    exit();
}

$userPerfil = $_SESSION['user_perfil'];
$userId = (int) $_SESSION['user_id'];

// Não permitir auto-exclusão
if ($id === $userId && $userPerfil === 'DIRIGENTE') {
    header("Location: ../../views/setores/listar.php?erro=" . urlencode("Você não pode excluir o próprio usuário logado."));
    exit();
}

$stmt = $conexao->prepare("DELETE FROM usuarios_ure WHERE id_usuario_ure = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: ../../views/setores/listar.php?msg=excluido");
    exit();
}

header("Location: ../../views/setores/listar.php?erro=" . urlencode("Erro ao excluir servidor: " . $stmt->error));
exit();
?>

