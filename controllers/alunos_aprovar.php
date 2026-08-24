<?php
session_start();
include("../config/database.php");
include("../config/perfil_functions.php");

// Apenas USUARIO_EDUCACAO_ESPECIAL pode aprovar/reprovar alunos
exigirPerfil(array('USUARIO_EDUCACAO_ESPECIAL'));

$id_aluno = (int) $_POST['id_aluno'];
$acao = $_POST['acao']; // APROVAR ou REPROVAR
$motivo = mysqli_real_escape_string($conexao, $_POST['motivo'] ?? '');

// Verifica se o aluno existe
$queryVerifica = "SELECT id_aluno FROM alunos WHERE id_aluno = $id_aluno";
$resultVerifica = mysqli_query($conexao, $queryVerifica);

if (mysqli_num_rows($resultVerifica) == 0) {
    die("Aluno não encontrado.");
}

if ($acao === 'APROVAR') {
    $status = 'APROVADO';
    $motivo = '';
} elseif ($acao === 'REPROVAR') {
    $status = 'REPROVADO';
    if (empty($motivo)) {
        die("Informe o motivo da reprovação.");
    }
} else {
    die("Ação inválida.");
}

// Atualiza o status do aluno
$query = "UPDATE alunos SET
    status_aprovacao = '$status',
    motivo_reprovacao = '$motivo'
    WHERE id_aluno = $id_aluno";

$result = mysqli_query($conexao, $query);

if ($result) {
    header("Location: ../views/alunos_aprovar.php?msg=ok");
    exit();
} else {
    echo "Erro ao atualizar aluno: " . mysqli_error($conexao);
}
?>