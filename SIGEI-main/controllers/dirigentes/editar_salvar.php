<?php
require_once "../../config/init.php";

exigirPerfil(array('ADMIN', 'SEDUC'));
exigirTokenCSRF();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../views/dirigentes/listar.php");
    exit();
}

$idDirigente = (int) ($_POST['id_usuario_ure'] ?? 0);
$nome = trim($_POST['nome'] ?? '');
$cpf = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
$idUre = (int) ($_POST['id_ure'] ?? 0);
$email = trim($_POST['email'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');
$ativo = (int) ($_POST['ativo'] ?? 1);
$novaSenha = $_POST['nova_senha'] ?? '';
$confirmarSenha = $_POST['confirmar_senha'] ?? '';

if ($idDirigente <= 0) {
    header("Location: ../../views/dirigentes/listar.php?erro=" . urlencode("Dirigente inválido."));
    exit();
}

if ($nome === '' || $cpf === '' || $idUre <= 0) {
    header("Location: ../../views/dirigentes/editar.php?id=$idDirigente&erro=" . urlencode("Preencha todos os campos obrigatórios."));
    exit();
}

// Verifica se o CPF pertence a outro usuário
$stmtVerifica = $conexao->prepare("SELECT id_usuario_ure FROM usuarios_ure WHERE cpf = ? AND id_usuario_ure != ?");
$stmtVerifica->bind_param("si", $cpf, $idDirigente);
$stmtVerifica->execute();
if ($stmtVerifica->get_result()->num_rows > 0) {
    header("Location: ../../views/dirigentes/editar.php?id=$idDirigente&erro=" . urlencode("Já existe outro usuário de URE com este CPF."));
    exit();
}
$stmtVerifica->close();

if ($novaSenha !== '') {
    if ($novaSenha !== $confirmarSenha) {
        header("Location: ../../views/dirigentes/editar.php?id=$idDirigente&erro=" . urlencode("As novas senhas digitadas não conferem."));
        exit();
    }
    if (strlen($novaSenha) < 6) {
        header("Location: ../../views/dirigentes/editar.php?id=$idDirigente&erro=" . urlencode("A senha deve ter pelo menos 6 caracteres."));
        exit();
    }
    $senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);
    $stmt = $conexao->prepare(
        "UPDATE usuarios_ure SET id_ure = ?, nome = ?, cpf = ?, email = ?, telefone = ?, ativo = ?, senha = ? WHERE id_usuario_ure = ?"
    );
    $stmt->bind_param("issssisi", $idUre, $nome, $cpf, $email, $telefone, $ativo, $senhaHash, $idDirigente);
} else {
    $stmt = $conexao->prepare(
        "UPDATE usuarios_ure SET id_ure = ?, nome = ?, cpf = ?, email = ?, telefone = ?, ativo = ? WHERE id_usuario_ure = ?"
    );
    $stmt->bind_param("issssii", $idUre, $nome, $cpf, $email, $telefone, $ativo, $idDirigente);
}

if ($stmt->execute()) {
    header("Location: ../../views/dirigentes/listar.php?msg=atualizado");
    exit();
}

header("Location: ../../views/dirigentes/editar.php?id=$idDirigente&erro=" . urlencode("Erro ao atualizar dirigente: " . $stmt->error));
exit();

