<?php
require_once "../config/init.php";

exigirPerfil(array('USUARIO_SEFISC', 'ADMIN', 'SEDUC'));
exigirTokenCSRF();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/usuarios_ue_cadastrar.php");
    exit();
}

$nome = trim($_POST['nome'] ?? '');
$cpf = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
$idUe = (int) ($_POST['id_ue'] ?? 0);
$email = trim($_POST['email'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');
$ativo = (int) ($_POST['ativo'] ?? 1);
$senha = $_POST['senha'] ?? '';
$confirmarSenha = $_POST['confirmar_senha'] ?? '';

if ($nome === '' || $cpf === '' || $idUe <= 0 || $senha === '') {
    header("Location: ../views/usuarios_ue_cadastrar.php?erro=" . urlencode("Preencha todos os campos obrigatórios."));
    exit();
}

if ($senha !== $confirmarSenha) {
    header("Location: ../views/usuarios_ue_cadastrar.php?erro=" . urlencode("As senhas não coincidem."));
    exit();
}

// Verifica se o CPF já existe em usuarios_ue
$stmtVerifica = $conexao->prepare("SELECT id_usuario_ue FROM usuarios_ue WHERE cpf = ?");
$stmtVerifica->bind_param("s", $cpf);
$stmtVerifica->execute();
if ($stmtVerifica->get_result()->num_rows > 0) {
    header("Location: ../views/usuarios_ue_cadastrar.php?erro=" . urlencode("Já existe um usuário da escola cadastrado com este CPF."));
    exit();
}
$stmtVerifica->close();

$senhaHash = password_hash($senha, PASSWORD_DEFAULT);

$stmt = $conexao->prepare(
    "INSERT INTO usuarios_ue (id_ue, nome, cpf, senha, email, telefone, ativo)
     VALUES (?, ?, ?, ?, ?, ?, ?)"
);

if (!$stmt) {
    die("Erro ao preparar consulta: " . $conexao->error);
}

$stmt->bind_param("isssssi", $idUe, $nome, $cpf, $senhaHash, $email, $telefone, $ativo);

if ($stmt->execute()) {
    header("Location: ../views/usuarios_ue_cadastrar.php?msg=ok");
    exit();
}

header("Location: ../views/usuarios_ue_cadastrar.php?erro=" . urlencode("Erro ao cadastrar usuário da escola: " . $stmt->error));
exit();
?>