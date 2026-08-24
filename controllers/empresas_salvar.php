<?php
session_start();
include("../config/database.php");
include("../config/perfil_functions.php");
include("upload.php");

// Apenas ADMIN pode cadastrar empresas
exigirPerfil(array('ADMIN'));

// Coleta os campos do formulário
$nome = mysqli_real_escape_string($conexao, $_POST['nome']);
$cnpj = preg_replace('/\D/', '', $_POST['cnpj']);
$rua = mysqli_real_escape_string($conexao, $_POST['rua'] ?? '');
$numero = mysqli_real_escape_string($conexao, $_POST['numero'] ?? '');
$bairro = mysqli_real_escape_string($conexao, $_POST['bairro'] ?? '');
$cidade = mysqli_real_escape_string($conexao, $_POST['cidade'] ?? '');
$cep = preg_replace('/\D/', '', $_POST['cep'] ?? '');
$numero_contrato = mysqli_real_escape_string($conexao, $_POST['numero_contrato'] ?? '');
$telefone = mysqli_real_escape_string($conexao, $_POST['telefone'] ?? '');
$email = mysqli_real_escape_string($conexao, $_POST['email'] ?? '');

// Upload do contrato (opcional)
$contratoArquivo = '';
if (isset($_FILES['contrato_arquivo']) && $_FILES['contrato_arquivo']['error'] === UPLOAD_ERR_OK) {
    $contratoArquivo = uploadArquivo($_FILES['contrato_arquivo'], 'contratos');
}

// Verifica se o CNPJ já existe
$queryVerifica = "SELECT id_empresa FROM empresas WHERE cnpj = '$cnpj'";
$resultVerifica = mysqli_query($conexao, $queryVerifica);

if (mysqli_num_rows($resultVerifica) > 0) {
    die("Já existe uma empresa com este CNPJ.");
}

// Insere a empresa
$query = "INSERT INTO empresas (
    nome, cnpj, rua, numero, bairro, cidade, cep,
    numero_contrato, contrato_arquivo, telefone, email
) VALUES (
    '$nome', '$cnpj', '$rua', '$numero', '$bairro', '$cidade', '$cep',
    '$numero_contrato', '$contratoArquivo', '$telefone', '$email'
)";

$result = mysqli_query($conexao, $query);

if ($result) {
    header("Location: ../views/empresas_cadastrar.php?msg=ok");
    exit();
} else {
    echo "Erro ao cadastrar empresa: " . mysqli_error($conexao);
}
?>