<?php
require_once "../../config/init.php";

exigirPerfil(array('DIRIGENTE', 'ADMIN', 'SEDUC'));

$id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
$token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';

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
if ($id === $userId) {
    header("Location: ../../views/setores/listar.php?erro=" . urlencode("Você não pode excluir o próprio usuário logado."));
    exit();
}

// Busca o servidor alvo para validar regional e setor
$stmtBusca = $conexao->prepare("SELECT id_usuario_ure, id_ure, setor, nome, cpf FROM usuarios_ure WHERE id_usuario_ure = ? LIMIT 1");
$stmtBusca->bind_param("i", $id);
$stmtBusca->execute();
$servidorAlvo = $stmtBusca->get_result()->fetch_assoc();
$stmtBusca->close();

if (!$servidorAlvo) {
    header("Location: ../../views/setores/listar.php?erro=" . urlencode("Servidor não encontrado."));
    exit();
}

if ($userPerfil === 'DIRIGENTE') {
    $idUre = (int) ($_SESSION['id_ure'] ?? idUreUsuario($conexao, $userId));
    if ($idUre > 0 && (int) $servidorAlvo['id_ure'] !== $idUre) {
        redirecionarAcessoNegado();
    }
    if (in_array($servidorAlvo['setor'], ['ASURE', 'GABINETE'], true)) {
        header("Location: ../../views/setores/listar.php?erro=" . urlencode("O Dirigente da regional não pode excluir servidores do setor ASURE."));
        exit();
    }
}

$stmt = $conexao->prepare("DELETE FROM usuarios_ure WHERE id_usuario_ure = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $stmt->close();
    registrarAuditoria($conexao, 'GESTAO_SETORES', 'EXCLUIR_SERVIDOR', 'usuarios_ure', $id, [
        'nome' => $servidorAlvo['nome'] ?? '',
        'setor' => $servidorAlvo['setor'] ?? '',
        'id_ure' => (int) ($servidorAlvo['id_ure'] ?? 0)
    ]);
    header("Location: ../../views/setores/listar.php?msg=excluido");
    exit();
}

header("Location: ../../views/setores/listar.php?erro=" . urlencode("Erro ao excluir servidor: " . $stmt->error));
exit();
?>

