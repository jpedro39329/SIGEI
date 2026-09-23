<?php
require_once "../../config/init.php";

exigirPerfil(array('ADMIN', 'SEDUC'));
exigirTokenCSRF();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../views/supervisores/listar.php");
    exit();
}

$idSupervisor = (int) ($_POST['id_usuario_supervisor'] ?? 0);
$nome = trim($_POST['nome'] ?? '');
$cpf = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
$idEmpresa = (int) ($_POST['id_empresa'] ?? 0);
$email = trim($_POST['email'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');
$ativo = (int) ($_POST['ativo'] ?? 1);
$novaSenha = $_POST['nova_senha'] ?? '';
$confirmarSenha = $_POST['confirmar_senha'] ?? '';

if ($idSupervisor <= 0) {
    header("Location: ../../views/supervisores/listar.php?erro=" . urlencode("Supervisor inválido."));
    exit();
}

if ($nome === '' || $cpf === '' || $idEmpresa <= 0) {
    header("Location: ../../views/supervisores/editar.php?id=$idSupervisor&erro=" . urlencode("Preencha todos os campos obrigatórios."));
    exit();
}

// Verifica se o CPF pertence a outro usuário
$stmtVerifica = $conexao->prepare("SELECT id_usuario_supervisor FROM usuarios_supervisor WHERE cpf = ? AND id_usuario_supervisor != ?");
$stmtVerifica->bind_param("si", $cpf, $idSupervisor);
$stmtVerifica->execute();
if ($stmtVerifica->get_result()->num_rows > 0) {
    header("Location: ../../views/supervisores/editar.php?id=$idSupervisor&erro=" . urlencode("Já existe outro supervisor com este CPF."));
    exit();
}
$stmtVerifica->close();

if ($novaSenha !== '') {
    if ($novaSenha !== $confirmarSenha) {
        header("Location: ../../views/supervisores/editar.php?id=$idSupervisor&erro=" . urlencode("As novas senhas digitadas não conferem."));
        exit();
    }
    if (strlen($novaSenha) < 6) {
        header("Location: ../../views/supervisores/editar.php?id=$idSupervisor&erro=" . urlencode("A senha deve ter pelo menos 6 caracteres."));
        exit();
    }
    $senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);
    $stmt = $conexao->prepare(
        "UPDATE usuarios_supervisor SET nome = ?, cpf = ?, id_empresa = ?, email = ?, telefone = ?, ativo = ?, senha = ? WHERE id_usuario_supervisor = ?"
    );
    $stmt->bind_param("ssisssii", $nome, $cpf, $idEmpresa, $email, $telefone, $ativo, $senhaHash, $idSupervisor);
} else {
    $stmt = $conexao->prepare(
        "UPDATE usuarios_supervisor SET nome = ?, cpf = ?, id_empresa = ?, email = ?, telefone = ?, ativo = ? WHERE id_usuario_supervisor = ?"
    );
    $stmt->bind_param("ssissii", $nome, $cpf, $idEmpresa, $email, $telefone, $ativo, $idSupervisor);
}

if ($stmt->execute()) {
    header("Location: ../../views/supervisores/listar.php?msg=atualizado");
    exit();
}

header("Location: ../../views/supervisores/editar.php?id=$idSupervisor&erro=" . urlencode("Erro ao atualizar supervisor: " . $stmt->error));
exit();

