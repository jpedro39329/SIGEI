<?php
require_once "../../config/init.php";

exigirPerfil(array('DIRIGENTE', 'ADMIN', 'SEDUC'));
exigirTokenCSRF();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../views/setores/listar.php");
    exit();
}

$userPerfil = $_SESSION['user_perfil'];
$userId = (int) $_SESSION['user_id'];
$idServidor = (int) ($_POST['id_usuario_ure'] ?? 0);

if ($idServidor <= 0) {
    header("Location: ../../views/setores/listar.php?erro=" . urlencode("Servidor não informado."));
    exit();
}

if ($userPerfil === 'DIRIGENTE') {
    $idUre = (int) ($_SESSION['id_ure'] ?? 0);
    if ($idUre <= 0) {
        $idUre = idUreUsuario($conexao, $userId);
    }
} else {
    $idUre = (int) ($_POST['id_ure'] ?? 0);
}

$nome = trim($_POST['nome'] ?? '');
$cpf = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
$setor = $_POST['setor'] ?? '';
$cargo = trim($_POST['cargo'] ?? '');
$email = trim($_POST['email'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');
$ativo = (int) ($_POST['ativo'] ?? 1);
$novaSenha = $_POST['nova_senha'] ?? '';
$confirmarSenha = $_POST['confirmar_senha'] ?? '';

if ($nome === '' || $cpf === '' || $idUre <= 0 || $setor === '' || $cargo === '') {
    header("Location: ../../views/setores/editar.php?id=$idServidor&erro=" . urlencode("Preencha todos os campos obrigatórios."));
    exit();
}

if (!in_array($setor, ['SEFISC', 'EDU_ESPECIAL', 'ASURE', 'GABINETE'])) {
    header("Location: ../../views/setores/editar.php?id=$idServidor&erro=" . urlencode("Setor inválido."));
    exit();
}

// Verifica se CPF já pertence a outro usuário
$stmtVerifica = $conexao->prepare("SELECT id_usuario_ure FROM usuarios_ure WHERE cpf = ? AND id_usuario_ure != ?");
$stmtVerifica->bind_param("si", $cpf, $idServidor);
$stmtVerifica->execute();
if ($stmtVerifica->get_result()->num_rows > 0) {
    header("Location: ../../views/setores/editar.php?id=$idServidor&erro=" . urlencode("Já existe outro servidor com este CPF."));
    exit();
}
$stmtVerifica->close();

$nivelAcesso = ($setor === 'SEFISC') ? 2 : (($setor === 'ASURE' || $setor === 'GABINETE') ? 3 : 2);

if (!empty($novaSenha)) {
    if ($novaSenha !== $confirmarSenha) {
        header("Location: ../../views/setores/editar.php?id=$idServidor&erro=" . urlencode("As senhas não coincidem."));
        exit();
    }
    if (strlen($novaSenha) < 6) {
        header("Location: ../../views/setores/editar.php?id=$idServidor&erro=" . urlencode("A senha deve ter pelo menos 6 caracteres."));
        exit();
    }
    $senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);
    $stmt = $conexao->prepare("
        UPDATE usuarios_ure
        SET id_ure = ?, nome = ?, cpf = ?, senha = ?, setor = ?, cargo = ?, nivel_acesso = ?, email = ?, ativo = ?
        WHERE id_usuario_ure = ?
    ");
    $stmt->bind_param("isssssisii", $idUre, $nome, $cpf, $senhaHash, $setor, $cargo, $nivelAcesso, $email, $ativo, $idServidor);
} else {
    $stmt = $conexao->prepare("
        UPDATE usuarios_ure
        SET id_ure = ?, nome = ?, cpf = ?, setor = ?, cargo = ?, nivel_acesso = ?, email = ?, ativo = ?
        WHERE id_usuario_ure = ?
    ");
    $stmt->bind_param("issssissii", $idUre, $nome, $cpf, $setor, $cargo, $nivelAcesso, $email, $ativo, $idServidor);
}

if ($stmt->execute()) {
    registrarAuditoria($conexao, 'GESTAO_SETORES', 'EDITAR_SERVIDOR', 'usuarios_ure', $idServidor, [
        'nome' => $nome,
        'setor' => $setor,
        'cargo' => $cargo,
        'id_ure' => $idUre,
        'ativo' => $ativo
    ]);
    header("Location: ../../views/setores/listar.php?msg=atualizado");
    exit();
}

header("Location: ../../views/setores/editar.php?id=$idServidor&erro=" . urlencode("Erro ao atualizar servidor: " . $stmt->error));
exit();
?>

