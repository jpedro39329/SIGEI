<?php
session_start();
include("../config/database.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../views/login.php");
    exit();
}

// Verifica se o usuário logado é do tipo UNIDADE_ESCOLAR (apenas eles podem cadastrar alunos)
$userPerfil = $_SESSION['user_perfil'];
if ($userPerfil !== 'UNIDADE_ESCOLAR') {
    die("Apenas unidades escolares podem cadastrar alunos.");
}

$id_usuario_escola = $_SESSION['user_id'];

// Busca o CIE da escola logada
$queryCie = "SELECT cie FROM usuarios WHERE id_usuario = $id_usuario_escola";
$resultCie = mysqli_query($conexao, $queryCie);
$rowCie = mysqli_fetch_assoc($resultCie);

if (!$rowCie || empty($rowCie['cie'])) {
    die("Usuário não possui CIE associado. Contate o administrador.");
}
$cie_escola = $rowCie['cie'];

// Coleta e sanitiza os campos do formulário
$nome = mysqli_real_escape_string($conexao, $_POST['nome']);
$cpf = preg_replace('/\D/', '', $_POST['cpf']);
$dataNascimento = mysqli_real_escape_string($conexao, $_POST['data_nascimento']);
$deficiencia = mysqli_real_escape_string($conexao, $_POST['deficiencia']);
$observacoes = mysqli_real_escape_string($conexao, $_POST['observacoes']);

// Query de inserção com os campos obrigatórios
$query = "INSERT INTO alunos (
    nome,
    cpf,
    data_nascimento,
    deficiencia,
    status_aprovacao,
    cie_escola,
    id_usuario_escola
) VALUES (
    '$nome',
    '$cpf',
    '$dataNascimento',
    '$deficiencia',
    'PENDENTE',
    '$cie_escola',
    $id_usuario_escola
)";

$result = mysqli_query($conexao, $query);

if ($result) {
    header("Location: ../views/alunos_listar.php?msg=sucesso");
    exit();
} else {
    echo "Erro ao cadastrar aluno: " . mysqli_error($conexao);
}
?>
