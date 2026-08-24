<?php
session_start();

require_once '../config/database.php';
require_once 'upload.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../views/login.php");
    exit();
}

if ($_SESSION['user_perfil'] !== 'USUARIO_EMPRESA') {
    die("Acesso negado.");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/paes_listar.php");
    exit();
}

$nome = trim($_POST['nome'] ?? '');
$cpf = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
$senha = $_POST['senha'] ?? '';
$confirmarSenha = $_POST['confirmar_senha'] ?? '';
$ativo = (int) ($_POST['ativo'] ?? 1);
$idUsuarioEmpresa = (int) $_SESSION['user_id'];

if ($nome === '' || $cpf === '' || $senha === '') {
    header("Location: ../views/paes_cadastrar.php?erro=Preencha os campos obrigatorios.");
    exit();
}

if ($senha !== $confirmarSenha) {
    header("Location: ../views/paes_cadastrar.php?erro=As senhas nao coincidem.");
    exit();
}

$queryEmpresa = "SELECT id_empresa FROM usuarios_empresa WHERE id_usuario_empresa = $idUsuarioEmpresa";
$resultEmpresa = mysqli_query($conexao, $queryEmpresa);
$rowEmpresa = mysqli_fetch_assoc($resultEmpresa);

if (!$rowEmpresa || empty($rowEmpresa['id_empresa'])) {
    header("Location: ../views/paes_cadastrar.php?erro=Usuario nao possui empresa associada.");
    exit();
}

$idEmpresa = (int) $rowEmpresa['id_empresa'];

$sqlVerifica = "SELECT id_pae FROM paes WHERE cpf = ?";
$stmtVerifica = $conexao->prepare($sqlVerifica);
$stmtVerifica->bind_param("s", $cpf);
$stmtVerifica->execute();
$resultVerifica = $stmtVerifica->get_result();

if ($resultVerifica->num_rows > 0) {
    header("Location: ../views/paes_cadastrar.php?erro=CPF ja cadastrado.");
    exit();
}

$contratoArquivo = '';
if (isset($_FILES['contrato_arquivo']) && $_FILES['contrato_arquivo']['error'] === UPLOAD_ERR_OK) {
    $contratoArquivo = uploadArquivo($_FILES['contrato_arquivo'], 'contratos');

    if ($contratoArquivo === false) {
        header("Location: ../views/paes_cadastrar.php?erro=Erro ao enviar contrato. Envie PDF, JPG ou PNG de ate 5MB.");
        exit();
    }
}

$senhaHash = password_hash($senha, PASSWORD_DEFAULT);

$sql = "INSERT INTO paes (nome, cpf, senha, contrato_arquivo, id_empresa, ativo)
        VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conexao->prepare($sql);
$stmt->bind_param("ssssii", $nome, $cpf, $senhaHash, $contratoArquivo, $idEmpresa, $ativo);

if ($stmt->execute()) {
    header("Location: ../views/paes_listar.php?msg=salvo");
    exit();
}

header("Location: ../views/paes_cadastrar.php?erro=Erro ao cadastrar PAE.");
exit();
?>
