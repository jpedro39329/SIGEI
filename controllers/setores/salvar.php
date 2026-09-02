<?php
require_once "../../config/init.php";

exigirPerfil(array('DIRIGENTE', 'ADMIN', 'SEDUC'));
exigirTokenCSRF();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../views/setores/cadastrar.php");
    exit();
}

$userPerfil = $_SESSION['user_perfil'];
$userId = (int) $_SESSION['user_id'];

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
$senha = $_POST['senha'] ?? '';
$confirmarSenha = $_POST['confirmar_senha'] ?? '';

if ($nome === '' || $cpf === '' || $idUre <= 0 || $setor === '' || $cargo === '' || $senha === '') {
    header("Location: ../../views/setores/cadastrar.php?erro=" . urlencode("Preencha todos os campos obrigatórios."));
    exit();
}

if (!in_array($setor, ['SEFISC', 'EDU_ESPECIAL'])) {
    header("Location: ../../views/setores/cadastrar.php?erro=" . urlencode("Setor inválido."));
    exit();
}

if ($senha !== $confirmarSenha) {
    header("Location: ../../views/setores/cadastrar.php?erro=" . urlencode("As senhas não coincidem."));
    exit();
}

// Verifica se o CPF já existe
$stmtVerifica = $conexao->prepare("SELECT id_usuario_ure FROM usuarios_ure WHERE cpf = ?");
$stmtVerifica->bind_param("s", $cpf);
$stmtVerifica->execute();
if ($stmtVerifica->get_result()->num_rows > 0) {
    header("Location: ../../views/setores/cadastrar.php?erro=" . urlencode("Já existe um servidor cadastrado com este CPF."));
    exit();
}
$stmtVerifica->close();

$nivelAcesso = ($setor === 'SEFISC') ? 2 : 3;
$senhaHash = password_hash($senha, PASSWORD_DEFAULT);

$stmt = $conexao->prepare(
    "INSERT INTO usuarios_ure (id_ure, nome, cpf, senha, setor, cargo, nivel_acesso, email, telefone, ativo)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);

if (!$stmt) {
    die("Erro ao preparar consulta: " . $conexao->error);
}

$stmt->bind_param("isssssissi", $idUre, $nome, $cpf, $senhaHash, $setor, $cargo, $nivelAcesso, $email, $telefone, $ativo);

if ($stmt->execute()) {
    header("Location: ../../views/setores/cadastrar.php?msg=ok");
    exit();
}

header("Location: ../../views/setores/cadastrar.php?erro=" . urlencode("Erro ao cadastrar servidor: " . $stmt->error));
exit();
?>