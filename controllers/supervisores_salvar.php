<?php
require_once "../config/init.php";

exigirPerfil(array('ADMIN', 'SEDUC'));
exigirTokenCSRF();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/supervisores_cadastrar.php");
    exit();
}

$nome = trim($_POST['nome'] ?? '');
$cpf = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
$idEmpresa = (int) ($_POST['id_empresa'] ?? 0);
$email = trim($_POST['email'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');
$ativo = (int) ($_POST['ativo'] ?? 1);
$senha = $_POST['senha'] ?? '';
$confirmarSenha = $_POST['confirmar_senha'] ?? '';

if ($nome === '' || $cpf === '' || $idEmpresa <= 0 || $senha === '') {
    header("Location: ../views/supervisores_cadastrar.php?erro=" . urlencode("Preencha todos os campos obrigatórios."));
    exit();
}

if ($senha !== $confirmarSenha) {
    header("Location: ../views/supervisores_cadastrar.php?erro=" . urlencode("As senhas não coincidem."));
    exit();
}

// Verifica se o CPF já está cadastrado
$stmtVerifica = $conexao->prepare("SELECT id_usuario_supervisor FROM usuarios_supervisor WHERE cpf = ?");
$stmtVerifica->bind_param("s", $cpf);
$stmtVerifica->execute();
if ($stmtVerifica->get_result()->num_rows > 0) {
    header("Location: ../views/supervisores_cadastrar.php?erro=" . urlencode("Já existe um supervisor cadastrado com este CPF."));
    exit();
}
$stmtVerifica->close();

$senhaHash = password_hash($senha, PASSWORD_DEFAULT);

$stmt = $conexao->prepare(
    "INSERT INTO usuarios_supervisor (id_empresa, nome, cpf, senha, email, telefone, ativo)
     VALUES (?, ?, ?, ?, ?, ?, ?)"
);

if (!$stmt) {
    die("Erro ao preparar consulta: " . $conexao->error);
}

$stmt->bind_param("isssssi", $idEmpresa, $nome, $cpf, $senhaHash, $email, $telefone, $ativo);

if ($stmt->execute()) {
    header("Location: ../views/supervisores_cadastrar.php?msg=ok");
    exit();
}

header("Location: ../views/supervisores_cadastrar.php?erro=" . urlencode("Erro ao cadastrar supervisor: " . $stmt->error));
exit();
?>