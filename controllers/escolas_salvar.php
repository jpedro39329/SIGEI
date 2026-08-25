<?php
require_once "../config/init.php";

// Apenas ADMIN pode cadastrar escolas
exigirPerfil(array('ADMIN'));
exigirTokenCSRF();

// Coleta os campos do formulário
$nome = trim($_POST['nome'] ?? '');
$cie = trim($_POST['cie'] ?? '');
$rua = trim($_POST['rua'] ?? '');
$numero = trim($_POST['numero'] ?? '');
$bairro = trim($_POST['bairro'] ?? '');
$cidade = trim($_POST['cidade'] ?? '');
$cep = preg_replace('/\D/', '', $_POST['cep'] ?? '');
$modalidade = $_POST['modalidade'] ?? ''; // PEI ou REGULAR
$horario = trim($_POST['horario_funcionamento'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');
$email = trim($_POST['email'] ?? '');

if ($nome === '' || $cie === '' || !in_array($modalidade, array('PEI', 'REGULAR'))) {
    die("Preencha o nome, o CIE e a modalidade.");
}

// Verifica se o CIE já existe
$stmtVerifica = $conexao->prepare("SELECT id_escola FROM escolas WHERE cie = ?");
$stmtVerifica->bind_param("s", $cie);
$stmtVerifica->execute();

if ($stmtVerifica->get_result()->num_rows > 0) {
    die("Já existe uma escola com este CIE.");
}

$stmtVerifica->close();

// Insere a escola
$stmt = $conexao->prepare(
    "INSERT INTO escolas (
        nome, cie, rua, numero, bairro, cidade, cep,
        modalidade, horario_funcionamento, telefone, email
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param(
    "sssssssssss",
    $nome, $cie, $rua, $numero, $bairro, $cidade, $cep,
    $modalidade, $horario, $telefone, $email
);

if ($stmt->execute()) {
    header("Location: ../views/escolas_cadastrar.php?msg=ok");
    exit();
}

echo "Erro ao cadastrar escola: " . $stmt->error;
?>