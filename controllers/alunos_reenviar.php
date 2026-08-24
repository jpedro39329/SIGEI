<?php
session_start();
include("../config/database.php");
include("../config/perfil_functions.php");

exigirPerfil(array('USUARIO_ESCOLA'));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/alunos_pendentes.php");
    exit();
}

$id_aluno = (int) ($_POST['id_aluno'] ?? 0);
$id_usuario_escola = (int) $_SESSION['user_id'];
$id_escola = idEscolaUsuario($conexao, $id_usuario_escola);

if ($id_aluno <= 0 || $id_escola <= 0) {
    die("Aluno ou escola invalido.");
}

$queryVerifica = "
    SELECT id_aluno
    FROM alunos
    WHERE id_aluno = $id_aluno
      AND id_escola = $id_escola
      AND status_aprovacao = 'REPROVADO'
";
$resultVerifica = mysqli_query($conexao, $queryVerifica);

if (!$resultVerifica || mysqli_num_rows($resultVerifica) == 0) {
    die("Aluno nao encontrado, nao pertence a sua escola ou nao esta reprovado.");
}

$query = "
    UPDATE alunos
    SET status_aprovacao = 'PENDENTE',
        motivo_reprovacao = ''
    WHERE id_aluno = $id_aluno
      AND id_escola = $id_escola
";

$result = mysqli_query($conexao, $query);

if ($result) {
    header("Location: ../views/alunos_pendentes.php?msg=reenviado");
    exit();
}

echo "Erro ao reenviar solicitacao: " . mysqli_error($conexao);
?>
