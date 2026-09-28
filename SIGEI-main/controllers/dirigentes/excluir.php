<?php
require_once "../../config/init.php";

exigirPerfil(array('ADMIN', 'SEDUC'));

$id = (int) ($_GET['id'] ?? 0);
$token = $_GET['csrf_token'] ?? '';

if (!validarTokenCSRF($token)) {
    header("Location: ../../views/dirigentes/listar.php?erro=" . urlencode("Token de segurança inválido."));
    exit();
}

if ($id <= 0) {
    header("Location: ../../views/dirigentes/listar.php?erro=" . urlencode("Dirigente não informado."));
    exit();
}

$stmt = $conexao->prepare("DELETE FROM usuarios_ure WHERE id_usuario_ure = ? AND setor IN ('ASURE', 'GABINETE')");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: ../../views/dirigentes/listar.php?msg=excluido");
    exit();
}

header("Location: ../../views/dirigentes/listar.php?erro=" . urlencode("Erro ao excluir dirigente: " . $stmt->error));
exit();

