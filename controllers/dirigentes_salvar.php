<?php
require_once "../config/init.php";

exigirPerfil(array('ADMIN', 'SEDUC'));
exigirTokenCSRF();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/dirigentes_cadastrar.php");
    exit();
}

$nome = trim($_POST['nome'] ?? '');
$cpf = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
$idUre = (int) ($_POST['id_ure'] ?? 0);
$email = trim($_POST['email'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');
$ativo = (int) ($_POST['ativo'] ?? 1);
$senha = $_POST['senha'] ?? '';
$confirmarSenha = $_POST['confirmar_senha'] ?? '';

if ($nome === '' || $cpf === '' || $idUre <= 0 || $senha === '') {
    header("Location: ../views/dirigentes_cadastrar.php?erro=" . urlencode("Preencha todos os campos obrigatórios."));
    exit();
}

if ($senha !== $confirmarSenha) {
    header("Location: ../views/dirigentes_cadastrar.php?erro=" . urlencode("As senhas não coincidem."));
    exit();
}

// Verifica se o CPF já existe em usuarios_ure
$stmtVerifica = $conexao->prepare("SELECT id_usuario_ure FROM usuarios_ure WHERE cpf = ?");
$stmtVerifica->bind_param("s", $cpf);
$stmtVerifica->execute();
if ($stmtVerifica->get_result()->num_rows > 0) {
    header("Location: ../views/dirigentes_cadastrar.php?erro=" . urlencode("Já existe um usuário cadastrado com este CPF."));
    exit();
}
$stmtVerifica->close();

$senhaHash = password_hash($senha, PASSWORD_DEFAULT);
$setor = 'GABINETE';
$cargo = 'Coordenador Dirigente Regional de Ensino';
$nivelAcesso = 1;

$stmt = $conexao->prepare(
    "INSERT INTO usuarios_ure (id_ure, nome, cpf, senha, setor, cargo, nivel_acesso, email, telefone, ativo)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);

if (!$stmt) {
    die("Erro ao preparar consulta: " . $conexao->error);
}

$stmt->bind_param("isssssissi", $idUre, $nome, $cpf, $senhaHash, $setor, $cargo, $nivelAcesso, $email, $telefone, $ativo);

if ($stmt->execute()) {
    header("Location: ../views/dirigentes_cadastrar.php?msg=ok");
    exit();
}

header("Location: ../views/dirigentes_cadastrar.php?erro=" . urlencode("Erro ao cadastrar dirigente: " . $stmt->error));
exit();
?>