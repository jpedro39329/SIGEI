<?php
require_once "../config/init.php";

exigirPerfil(array('USUARIO_ESCOLA'));
exigirTokenCSRF();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/alunos_pendentes.php");
    exit();
}

$id_aluno = (int) ($_POST['id_aluno'] ?? 0);
$id_usuario_escola = (int) $_SESSION['user_id'];
$id_escola = idEscolaUsuario($conexao, $id_usuario_escola);

if ($id_aluno <= 0 || $id_escola <= 0) {
    die("Aluno ou escola inválidos.");
}

$stmtVerifica = $conexao->prepare(
    "SELECT id_aluno
     FROM alunos
     WHERE id_aluno = ?
       AND id_escola = ?
       AND status_aprovacao = 'REPROVADO'"
);
$stmtVerifica->bind_param("ii", $id_aluno, $id_escola);
$stmtVerifica->execute();

if ($stmtVerifica->get_result()->num_rows == 0) {
    die("Aluno não encontrado, não pertence à sua escola ou não está reprovado.");
}

$stmtVerifica->close();

$stmt = $conexao->prepare(
    "UPDATE alunos
     SET status_aprovacao = 'PENDENTE',
         motivo_reprovacao = ''
     WHERE id_aluno = ?
       AND id_escola = ?"
);
$stmt->bind_param("ii", $id_aluno, $id_escola);

if ($stmt->execute()) {
    header("Location: ../views/alunos_pendentes.php?msg=reenviado");
    exit();
}

echo "Erro ao reenviar solicitação: " . $stmt->error;
?>
