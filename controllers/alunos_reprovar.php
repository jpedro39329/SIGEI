<?php
session_start();
include("../config/database.php");
include("../config/perfil_functions.php");

exigirPerfil(array('USUARIO_EDUCACAO_ESPECIAL'));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/alunos_aprovar.php");
    exit();
}

$id_aluno = (int) ($_POST['id_aluno'] ?? 0);
$motivo = mysqli_real_escape_string($conexao, trim($_POST['motivo'] ?? ''));

if ($id_aluno <= 0) {
    die("Aluno invalido.");
}

if ($motivo === '') {
    die("Informe o motivo da reprovacao.");
}

$queryVerifica = "SELECT id_aluno FROM alunos WHERE id_aluno = $id_aluno";
$resultVerifica = mysqli_query($conexao, $queryVerifica);

if (!$resultVerifica || mysqli_num_rows($resultVerifica) == 0) {
    die("Aluno nao encontrado.");
}

$query = "UPDATE alunos SET
    status_aprovacao = 'REPROVADO',
    motivo_reprovacao = '$motivo'
    WHERE id_aluno = $id_aluno";

$result = mysqli_query($conexao, $query);

if ($result) {
    header("Location: ../views/alunos_aprovar.php?msg=ok");
    exit();
}

echo "Erro ao reprovar aluno: " . mysqli_error($conexao);
?>
