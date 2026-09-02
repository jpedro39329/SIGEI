<?php
require_once "../../config/init.php";

exigirPerfil(array('ADMIN', 'SEDUC'));
exigirTokenCSRF();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../views/ures/cadastrar.php");
    exit();
}

$nome = trim($_POST['nome'] ?? '');
$endereco = trim($_POST['endereco'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');
$email = trim($_POST['email'] ?? '');

if ($nome === '') {
    die("O nome da URE é obrigatório.");
}

$stmt = $conexao->prepare(
    "INSERT INTO unidades_regionais (nome, endereco, telefone, email) VALUES (?, ?, ?, ?)"
);

if (!$stmt) {
    die("Erro ao preparar consulta: " . $conexao->error);
}

$stmt->bind_param("ssss", $nome, $endereco, $telefone, $email);

if ($stmt->execute()) {
    header("Location: ../../views/ures/cadastrar.php?msg=ok");
    exit();
}

echo "Erro ao cadastrar URE: " . $stmt->error;
?>