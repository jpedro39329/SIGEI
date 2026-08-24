<?php
session_start();
include("../config/database.php");
include("../config/perfil_functions.php");

exigirPerfil(array('USUARIO_ESCOLA'));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/alunos_listar.php");
    exit();
}

$id_aluno = (int) ($_POST['id_aluno'] ?? 0);
$id_usuario_escola = (int) $_SESSION['user_id'];
$id_escola = idEscolaUsuario($conexao, $id_usuario_escola);

if ($id_aluno <= 0 || $id_escola <= 0) {
    die("Aluno ou escola invalidos.");
}

$queryAluno = "SELECT id_aluno FROM alunos WHERE id_aluno = $id_aluno AND id_escola = $id_escola";
$resultAluno = mysqli_query($conexao, $queryAluno);

if (!$resultAluno || mysqli_num_rows($resultAluno) == 0) {
    die("Aluno nao encontrado ou nao pertence a sua escola.");
}

$nome = mysqli_real_escape_string($conexao, trim($_POST['nome'] ?? ''));
$deficiencia = mysqli_real_escape_string($conexao, trim($_POST['deficiencia'] ?? ''));
$cuidados = mysqli_real_escape_string($conexao, trim($_POST['observacoes'] ?? ''));
$nomeResponsavel = mysqli_real_escape_string($conexao, trim($_POST['nome_responsavel'] ?? ''));
$cpfResponsavel = preg_replace('/\D/', '', $_POST['cpf_responsavel'] ?? '');

if ($nome === '' || $deficiencia === '') {
    die("Nome e deficiencia sao obrigatorios.");
}

$query = "UPDATE alunos SET
    nome = '$nome',
    descricao_deficiencia = '$deficiencia',
    descricao_cuidados = '$cuidados',
    nome_responsavel = '$nomeResponsavel',
    cpf_responsavel = '$cpfResponsavel'
    WHERE id_aluno = $id_aluno AND id_escola = $id_escola";

$result = mysqli_query($conexao, $query);

if ($result) {
    header("Location: ../views/alunos_visualizar.php?id=$id_aluno&msg=editado");
    exit();
}

echo "Erro ao editar aluno: " . mysqli_error($conexao);
?>
