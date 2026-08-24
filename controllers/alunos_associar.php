<?php
session_start();
include("../config/database.php");
include("../config/perfil_functions.php");

// Apenas ADMIN pode associar PAE a aluno
exigirPerfil(array('ADMIN'));

$id_aluno = (int) $_POST['id_aluno'];
$id_pae = (int) $_POST['id_pae'];
$data_inicio = $_POST['data_inicio'] ?? date('Y-m-d');

// Verifica se o aluno existe e está APROVADO
$queryAluno = "SELECT id_aluno FROM alunos WHERE id_aluno = $id_aluno AND status_aprovacao = 'APROVADO'";
$resultAluno = mysqli_query($conexao, $queryAluno);

if (mysqli_num_rows($resultAluno) == 0) {
    die("Aluno não encontrado ou não está aprovado.");
}

// Verifica se o PAE existe e está ativo
$queryPae = "SELECT id_pae FROM paes WHERE id_pae = $id_pae AND ativo = 1";
$resultPae = mysqli_query($conexao, $queryPae);

if (mysqli_num_rows($resultPae) == 0) {
    die("PAE não encontrado ou está inativo.");
}

// RN01: PAE não pode ter mais de 3 alunos ativos
$totalAlunosPae = contarAlunosPAE($conexao, $id_pae);
if ($totalAlunosPae >= 3) {
    die("Este PAE já possui 3 alunos ativos associados.");
}

// Verifica se o aluno já está associado a este PAE
$queryExiste = "SELECT id_associacao FROM associacoes WHERE id_aluno = $id_aluno AND id_pae = $id_pae AND ativo = 1";
$resultExiste = mysqli_query($conexao, $queryExiste);

if (mysqli_num_rows($resultExiste) > 0) {
    die("Este aluno já está associado a este PAE.");
}

// Cria a associação
$query = "INSERT INTO associacoes (id_aluno, id_pae, ativo, data_inicio)
          VALUES ($id_aluno, $id_pae, 1, '$data_inicio')";

$result = mysqli_query($conexao, $query);

if ($result) {
    header("Location: ../views/associacoes.php?msg=ok");
    exit();
} else {
    echo "Erro ao associar: " . mysqli_error($conexao);
}
?>