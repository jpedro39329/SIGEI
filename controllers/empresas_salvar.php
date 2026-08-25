<?php
require_once "../config/init.php";
require_once "upload.php";

// Apenas ADMIN pode cadastrar empresas
exigirPerfil(array('ADMIN'));
exigirTokenCSRF();

// Coleta os campos do formulário
$nome = trim($_POST['nome'] ?? '');
$cnpj = preg_replace('/\D/', '', $_POST['cnpj'] ?? '');
$rua = trim($_POST['rua'] ?? '');
$numero = trim($_POST['numero'] ?? '');
$bairro = trim($_POST['bairro'] ?? '');
$cidade = trim($_POST['cidade'] ?? '');
$cep = preg_replace('/\D/', '', $_POST['cep'] ?? '');
$numero_contrato = trim($_POST['numero_contrato'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');
$email = trim($_POST['email'] ?? '');

if ($nome === '' || strlen($cnpj) !== 14) {
    die("Preencha o nome e um CNPJ válido.");
}

// Upload do contrato (opcional)
$contratoArquivo = '';
if (isset($_FILES['contrato_arquivo']) && $_FILES['contrato_arquivo']['error'] === UPLOAD_ERR_OK) {
    $contratoArquivo = uploadArquivo($_FILES['contrato_arquivo'], 'contratos');

    if ($contratoArquivo === false) {
        die("Erro ao enviar o contrato. Envie um PDF, JPG ou PNG de até 5MB.");
    }
}

// Verifica se o CNPJ já existe
$stmtVerifica = $conexao->prepare("SELECT id_empresa FROM empresas WHERE cnpj = ?");
$stmtVerifica->bind_param("s", $cnpj);
$stmtVerifica->execute();

if ($stmtVerifica->get_result()->num_rows > 0) {
    die("Já existe uma empresa com este CNPJ.");
}

$stmtVerifica->close();

// Insere a empresa
$stmt = $conexao->prepare(
    "INSERT INTO empresas (
        nome, cnpj, rua, numero, bairro, cidade, cep,
        numero_contrato, contrato_arquivo, telefone, email
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param(
    "sssssssssss",
    $nome, $cnpj, $rua, $numero, $bairro, $cidade, $cep,
    $numero_contrato, $contratoArquivo, $telefone, $email
);

if ($stmt->execute()) {
    header("Location: ../views/empresas_cadastrar.php?msg=ok");
    exit();
}

echo "Erro ao cadastrar empresa: " . $stmt->error;
?>