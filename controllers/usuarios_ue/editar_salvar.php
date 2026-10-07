<?php
require_once "../../config/init.php";

exigirPerfil(array('USUARIO_SEFISC', 'ADMIN', 'SEDUC'));
exigirTokenCSRF();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../views/usuarios_ue/listar.php");
    exit();
}

$idUsuarioUe = (int) ($_POST['id_usuario_ue'] ?? 0);
$userPerfil = $_SESSION['user_perfil'];
$userId = (int) $_SESSION['user_id'];
$idUreUsuario = (int) ($_SESSION['id_ure'] ?? 0);

if ($idUsuarioUe <= 0) {
    die("Usuário inválido.");
}

if ($userPerfil === 'USUARIO_SEFISC' && $idUreUsuario <= 0) {
    $idUreUsuario = idUreUsuario($conexao, $userId);
}

// Verifica se o usuário pertence à URE caso seja SEFISC
if ($userPerfil === 'USUARIO_SEFISC' && $idUreUsuario > 0) {
    $stmtVerifica = $conexao->prepare("
        SELECT uue.id_usuario_ue
        FROM usuarios_ue uue
        JOIN unidades_escolares ue ON uue.id_ue = ue.id_ue
        WHERE uue.id_usuario_ue = ? AND ue.id_ure = ?
    ");
    $stmtVerifica->bind_param("ii", $idUsuarioUe, $idUreUsuario);
    $stmtVerifica->execute();
    if ($stmtVerifica->get_result()->num_rows === 0) {
        die("Acesso negado: usuário não pertence à sua Diretoria Regional.");
    }
    $stmtVerifica->close();
}

$nome = trim($_POST['nome'] ?? '');
$idUe = (int) ($_POST['id_ue'] ?? 0);
$email = trim($_POST['email'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');
$ativo = (int) ($_POST['ativo'] ?? 1);
$senha = $_POST['senha'] ?? '';
$confirmarSenha = $_POST['confirmar_senha'] ?? '';

if ($nome === '' || $idUe <= 0) {
    header("Location: ../../views/usuarios_ue/editar.php?id=$idUsuarioUe&erro=" . urlencode("Preencha todos os campos obrigatórios."));
    exit();
}

if ($senha !== '') {
    if ($senha !== $confirmarSenha) {
        header("Location: ../../views/usuarios_ue/editar.php?id=$idUsuarioUe&erro=" . urlencode("As senhas não coincidem."));
        exit();
    }
    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
    $stmt = $conexao->prepare("
        UPDATE usuarios_ue
        SET id_ue = ?, nome = ?, email = ?, telefone = ?, ativo = ?, senha = ?
        WHERE id_usuario_ue = ?
    ");
    $stmt->bind_param("isssisi", $idUe, $nome, $email, $telefone, $ativo, $senhaHash, $idUsuarioUe);
} else {
    $stmt = $conexao->prepare("
        UPDATE usuarios_ue
        SET id_ue = ?, nome = ?, email = ?, telefone = ?, ativo = ?
        WHERE id_usuario_ue = ?
    ");
    $stmt->bind_param("isssii", $idUe, $nome, $email, $telefone, $ativo, $idUsuarioUe);
}

if ($stmt->execute()) {
    registrarAuditoria($conexao, 'USUARIOS', 'EDITAR', 'usuarios_ue', $idUsuarioUe, [
        'nome' => $nome,
        'id_ue' => $idUe,
        'ativo' => $ativo,
        'email' => $email
    ]);

    header("Location: ../../views/usuarios_ue/listar.php?msg=editado");
    exit();
}

header("Location: ../../views/usuarios_ue/editar.php?id=$idUsuarioUe&erro=" . urlencode("Erro ao atualizar: " . $stmt->error));
exit();
?>

