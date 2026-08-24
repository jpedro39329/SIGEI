<?php
session_start();
include("../config/database.php");
include("../config/perfil_functions.php");

// Apenas ADMIN pode desassociar PAE de aluno
exigirPerfil(array('ADMIN'));

$id_associacao = (int) $_POST['id_associacao'];

// Desativa a associação
$query = "UPDATE associacoes SET ativo = 0 WHERE id_associacao = $id_associacao";
$result = mysqli_query($conexao, $query);

if ($result) {
    header("Location: ../views/associacoes.php?msg=removido");
    exit();
} else {
    echo "Erro ao desassociar: " . mysqli_error($conexao);
}
?>