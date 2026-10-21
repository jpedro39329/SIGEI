<?php
require_once "../../config/init.php";

exigirPerfil(array('ADMIN', 'SEDUC'));
exigirTokenCSRF();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../views/ures/listar.php");
    exit();
}

$nome = trim($_POST['nome'] ?? '');
$uge = trim($_POST['uge'] ?? '');
$endereco = trim($_POST['endereco'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');
$email = trim($_POST['email'] ?? '');

if ($nome === '') {
    header("Location: ../../views/ures/cadastrar.php?erro=" . urlencode("O nome da URE é obrigatório."));
    exit();
}

if ($uge === '') {
    header("Location: ../../views/ures/cadastrar.php?erro=" . urlencode("O código UGE é obrigatório."));
    exit();
}

if (!validarCampoNumerico($uge, true, 1, 20)) {
    header("Location: ../../views/ures/cadastrar.php?erro=" . urlencode("O código UGE deve conter apenas números."));
    exit();
}

if ($telefone !== '' && !validarTelefone($telefone, false)) {
    header("Location: ../../views/ures/cadastrar.php?erro=" . urlencode("Telefone inválido. Informe com DDD (10 ou 11 dígitos)."));
    exit();
}

if ($email !== '' && !validarEmail($email, false)) {
    header("Location: ../../views/ures/cadastrar.php?erro=" . urlencode("Endereço de e-mail inválido."));
    exit();
}

$stmt = $conexao->prepare(
    "INSERT INTO unidades_regionais (nome, uge, endereco, telefone, email) VALUES (?, ?, ?, ?, ?)"
);

if (!$stmt) {
    header("Location: ../../views/ures/cadastrar.php?erro=" . urlencode("Erro ao preparar consulta: " . $conexao->error));
    exit();
}

$stmt->bind_param("sssss", $nome, $uge, $endereco, $telefone, $email);

if ($stmt->execute()) {
    header("Location: ../../views/ures/listar.php?msg=cadastrado");
    exit();
}

header("Location: ../../views/ures/cadastrar.php?erro=" . urlencode("Erro ao cadastrar URE: " . $stmt->error));
exit();