<?php
session_start();
include("../config/database.php");
include("../config/perfil_functions.php");

// Apenas USUARIO_EDUCACAO_ESPECIAL pode aprovar/reprovar alunos
exigirPerfil(array('USUARIO_EDUCACAO_ESPECIAL'));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/solicitacoes.php");
    exit();
}

$id_aluno = (int) ($_POST['id_aluno'] ?? 0);
$acao     = $_POST['acao'] ?? '';

if ($id_aluno <= 0) {
    die("Aluno inválido.");
}

// Verifica que o aluno exista e ainda esteja pendente
$queryVerifica = "SELECT id_aluno, status_aprovacao FROM alunos WHERE id_aluno = $id_aluno";
$resultVerifica = mysqli_query($conexao, $queryVerifica);
$rowVerifica = mysqli_fetch_assoc($resultVerifica);

if (!$rowVerifica) {
    die("Aluno não encontrado.");
}

if ($rowVerifica['status_aprovacao'] !== 'PENDENTE') {
    header("Location: ../views/solicitacoes.php?msg=ya");
    exit();
}

if ($acao === 'APROVAR') {
    $status = 'APROVADO';
    $motivo = '';
} elseif ($acao === 'REPROVAR') {
    $status = 'REPROVADO';
    $motivo = mysqli_real_escape_string($conexao, trim($_POST['motivo'] ?? ''));
    if ($motivo === '') {
        header("Location: ../views/solicitacao_analisar.php?id=$id_aluno&erro=motivo");
        exit();
    }
} else {
    die("Ação inválida.");
}

$query = "UPDATE alunos SET status_aprovacao = '$status', motivo_reprovacao = '$motivo' WHERE id_aluno = $id_aluno";
$result = mysqli_query($conexao, $query);

if ($result) {
    header("Location: ../views/solicitacoes.php?msg=ok");
    exit();
}

echo "Erro ao atualizar aluno: " . mysqli_error($conexao);
?>