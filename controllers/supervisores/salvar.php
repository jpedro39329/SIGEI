<?php
require_once "../../config/init.php";

exigirPerfil(array('ADMIN', 'SEDUC'));
exigirTokenCSRF();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../views/supervisores/listar.php");
    exit();
}

$nome = trim($_POST['nome'] ?? '');
$cpf = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
$idEmpresa = (int) ($_POST['id_empresa'] ?? 0);
$email = trim($_POST['email'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');
$senha = $_POST['senha'] ?? '';
$confirmarSenha = $_POST['confirmar_senha'] ?? '';
$ativo = 1; // Status automaticamente ATIVO conforme solicitado

if ($nome === '' || $cpf === '' || $idEmpresa <= 0 || $senha === '') {
    header("Location: ../../views/supervisores/cadastrar.php?erro=" . urlencode("Preencha todos os campos obrigatórios."));
    exit();
}

if ($senha !== $confirmarSenha) {
    header("Location: ../../views/supervisores/cadastrar.php?erro=" . urlencode("As senhas digitadas não conferem."));
    exit();
}

if (strlen($senha) < 6) {
    header("Location: ../../views/supervisores/cadastrar.php?erro=" . urlencode("A senha deve ter pelo menos 6 caracteres."));
    exit();
}

// Verifica se o CPF já está cadastrado
$stmtVerifica = $conexao->prepare("SELECT id_usuario_supervisor FROM usuarios_supervisor WHERE cpf = ?");
$stmtVerifica->bind_param("s", $cpf);
$stmtVerifica->execute();
if ($stmtVerifica->get_result()->num_rows > 0) {
    header("Location: ../../views/supervisores/cadastrar.php?erro=" . urlencode("Já existe um supervisor cadastrado com este CPF."));
    exit();
}
$stmtVerifica->close();

$senhaHash = password_hash($senha, PASSWORD_DEFAULT);

$stmt = $conexao->prepare(
    "INSERT INTO usuarios_supervisor (nome, cpf, id_empresa, email, telefone, senha, ativo) VALUES (?, ?, ?, ?, ?, ?, ?)"
);

if (!$stmt) {
    header("Location: ../../views/supervisores/cadastrar.php?erro=" . urlencode("Erro ao preparar consulta: " . $conexao->error));
    exit();
}

$stmt->bind_param("ssisssi", $nome, $cpf, $idEmpresa, $email, $telefone, $senhaHash, $ativo);

if ($stmt->execute()) {
    header("Location: ../../views/supervisores/listar.php?msg=cadastrado");
    exit();
}

header("Location: ../../views/supervisores/cadastrar.php?erro=" . urlencode("Erro ao cadastrar supervisor: " . $stmt->error));
exit();