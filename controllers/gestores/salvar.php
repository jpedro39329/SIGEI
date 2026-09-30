<?php
require_once "../../config/init.php";

exigirPerfil(array('ADMIN', 'SEDUC'));
exigirTokenCSRF();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../views/gestores/listar.php");
    exit();
}

$nome = trim($_POST['nome'] ?? '');
$cpf = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
$idUre = (int) ($_POST['id_ure'] ?? 0);
$email = trim($_POST['email'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');
$cargo = trim($_POST['cargo'] ?? 'Assistente Técnico');
$senha = $_POST['senha'] ?? '';
$confirmarSenha = $_POST['confirmar_senha'] ?? '';
$ativo = 1; // Cadastro automaticamente ATIVO conforme solicitado

if ($nome === '' || $cpf === '' || $idUre <= 0 || $senha === '') {
    header("Location: ../../views/gestores/cadastrar.php?erro=" . urlencode("Preencha todos os campos obrigatórios e selecione a URE via UGE."));
    exit();
}

if ($senha !== $confirmarSenha) {
    header("Location: ../../views/gestores/cadastrar.php?erro=" . urlencode("As senhas digitadas não conferem."));
    exit();
}

if (strlen($senha) < 6) {
    header("Location: ../../views/gestores/cadastrar.php?erro=" . urlencode("A senha deve ter pelo menos 6 caracteres."));
    exit();
}

// Verifica se o CPF já está cadastrado
$stmtVerifica = $conexao->prepare("SELECT id_usuario_ure FROM usuarios_ure WHERE cpf = ?");
$stmtVerifica->bind_param("s", $cpf);
$stmtVerifica->execute();
if ($stmtVerifica->get_result()->num_rows > 0) {
    header("Location: ../../views/gestores/cadastrar.php?erro=" . urlencode("Já existe um usuário de URE cadastrado com este CPF."));
    exit();
}
$stmtVerifica->close();

$senhaHash = password_hash($senha, PASSWORD_DEFAULT);
$setor = 'ASURE';
$nivelAcesso = 3;

$stmt = $conexao->prepare(
    "INSERT INTO usuarios_ure (id_ure, nome, cpf, senha, setor, cargo, nivel_acesso, email, telefone, ativo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);

if (!$stmt) {
    header("Location: ../../views/gestores/cadastrar.php?erro=" . urlencode("Erro ao preparar consulta: " . $conexao->error));
    exit();
}

$stmt->bind_param("isssssissi", $idUre, $nome, $cpf, $senhaHash, $setor, $cargo, $nivelAcesso, $email, $telefone, $ativo);

if ($stmt->execute()) {
    header("Location: ../../views/gestores/listar.php?msg=cadastrado");
    exit();
}

header("Location: ../../views/gestores/cadastrar.php?erro=" . urlencode("Erro ao cadastrar dirigente: " . $stmt->error));
exit();