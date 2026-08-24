<?php
session_start();
include("../config/database.php");
include("../config/perfil_functions.php");

// Apenas ADMIN pode cadastrar escolas
exigirPerfil(array('ADMIN'));

// Coleta os campos do formulário
$nome = mysqli_real_escape_string($conexao, $_POST['nome']);
$cie = mysqli_real_escape_string($conexao, $_POST['cie']);
$rua = mysqli_real_escape_string($conexao, $_POST['rua'] ?? '');
$numero = mysqli_real_escape_string($conexao, $_POST['numero'] ?? '');
$bairro = mysqli_real_escape_string($conexao, $_POST['bairro'] ?? '');
$cidade = mysqli_real_escape_string($conexao, $_POST['cidade'] ?? '');
$cep = preg_replace('/\D/', '', $_POST['cep'] ?? '');
$modalidade = $_POST['modalidade']; // PEI ou REGULAR
$horario = mysqli_real_escape_string($conexao, $_POST['horario_funcionamento'] ?? '');
$telefone = mysqli_real_escape_string($conexao, $_POST['telefone'] ?? '');
$email = mysqli_real_escape_string($conexao, $_POST['email'] ?? '');

// Verifica se o CIE já existe
$queryVerifica = "SELECT id_escola FROM escolas WHERE cie = '$cie'";
$resultVerifica = mysqli_query($conexao, $queryVerifica);

if (mysqli_num_rows($resultVerifica) > 0) {
    die("Já existe uma escola com este CIE.");
}

// Insere a escola
$query = "INSERT INTO escolas (
    nome, cie, rua, numero, bairro, cidade, cep,
    modalidade, horario_funcionamento, telefone, email
) VALUES (
    '$nome', '$cie', '$rua', '$numero', '$bairro', '$cidade', '$cep',
    '$modalidade', '$horario', '$telefone', '$email'
)";

$result = mysqli_query($conexao, $query);

if ($result) {
    header("Location: ../views/escolas_cadastrar.php?msg=ok");
    exit();
} else {
    echo "Erro ao cadastrar escola: " . mysqli_error($conexao);
}
?>