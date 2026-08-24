<?php
session_start();
include("../config/database.php");
include("../config/perfil_functions.php");

// Apenas PAE pode registrar relatórios
exigirPerfil(array('PAE'));

$id_pae = $_SESSION['user_id'];
$id_aluno = (int) $_POST['id_aluno'];
$tipo = $_POST['tipo']; // DIARIO ou MENSAL
$descricao = mysqli_real_escape_string($conexao, $_POST['descricao']);

// Verifica se o aluno está associado a este PAE
$queryVerifica = "SELECT id_associacao FROM associacoes
                  WHERE id_aluno = $id_aluno AND id_pae = $id_pae AND ativo = 1";
$resultVerifica = mysqli_query($conexao, $queryVerifica);

if (mysqli_num_rows($resultVerifica) == 0) {
    die("Aluno não está associado a você.");
}

// Busca o id da associação
$rowAssoc = mysqli_fetch_assoc($resultVerifica);
$id_associacao = $rowAssoc['id_associacao'];

// Insere o relatório
$query = "INSERT INTO relatorios (id_associacao, tipo, descricao)
          VALUES ($id_associacao, '$tipo', '$descricao')";

$result = mysqli_query($conexao, $query);

if ($result) {
    header("Location: ../views/relatorios.php?msg=ok");
    exit();
} else {
    echo "Erro ao salvar relatório: " . mysqli_error($conexao);
}
?>