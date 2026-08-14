<?php

session_start();

require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../views/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/cuidadores_listar.php");
    exit();
}

// Dados do formulario
$nome             = trim($_POST['nome']);
$cpf              = preg_replace('/\D/', '', $_POST['cpf']);
$empresa          = trim($_POST['empresa']);
$senha            = $_POST['senha'];
$confirmar_senha  = $_POST['confirmar_senha'];
$ativo            = (int) $_POST['ativo'];

// ID da empresa logada
$id_usuario_empresa = $_SESSION['user_id'];

// Validacao senha
if ($senha !== $confirmar_senha) {

    header("Location: ../views/cuidadores_cadastrar.php?erro=As senhas nao coincidem.");
    exit();

}

// Verifica CPF ja cadastrado
$sqlVerifica = "SELECT id_usuario FROM usuarios WHERE cpf = ?";
$stmtVerifica = $conexao->prepare($sqlVerifica);

$stmtVerifica->bind_param("s", $cpf);
$stmtVerifica->execute();

$result = $stmtVerifica->get_result();

if ($result->num_rows > 0) {

    header("Location: ../views/cuidadores_cadastrar.php?erro=CPF ja cadastrado.");
    exit();

}

// Criptografa senha
$senhaHash = password_hash($senha, PASSWORD_DEFAULT);

// Insere cuidador
$sql = "INSERT INTO usuarios
(
    nome,
    cpf,
    senha,
    perfil,
    empresa,
    id_usuario_empresa,
    ativo
)
VALUES
(
    ?,
    ?,
    ?,
    'CUIDADOR',
    ?,
    ?,
    ?
)";

$stmt = $conexao->prepare($sql);

$stmt->bind_param(
    "ssssii",
    $nome,
    $cpf,
    $senhaHash,
    $empresa,
    $id_usuario_empresa,
    $ativo
);

if ($stmt->execute()) {

    header("Location: ../views/cuidadores_listar.php");

} else {

    header("Location: ../views/cuidadores_cadastrar.php?erro=Erro ao cadastrar cuidador.");

}

exit();
?>
