<?php
require_once "../../config/init.php";

// Apenas USUARIO_EDUCACAO_ESPECIAL pode aprovar/reprovar alunos
exigirPerfil(array('USUARIO_EDUCACAO_ESPECIAL'));
exigirTokenCSRF();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../views/solicitacoes/listar.php");
    exit();
}

$id_aluno = (int) ($_POST['id_aluno'] ?? 0);
$acao     = $_POST['acao'] ?? '';
$motivo   = '';

if ($id_aluno <= 0) {
    die("Aluno inválido.");
}

if ($acao === 'APROVAR') {
    $status = 'APROVADO';
} elseif ($acao === 'REPROVAR') {
    $status = 'REPROVADO';
    $motivo = trim($_POST['motivo'] ?? '');

    if ($motivo === '') {
        header("Location: ../../views/solicitacoes/analisar.php?id=$id_aluno&erro=motivo");
        exit();
    }
} else {
    die("Ação inválida.");
}

// Verifica que o aluno exista e ainda esteja pendente
$stmtVerifica = $conexao->prepare("SELECT id_aluno, status_aprovacao FROM alunos WHERE id_aluno = ?");
$stmtVerifica->bind_param("i", $id_aluno);
$stmtVerifica->execute();
$resultVerifica = $stmtVerifica->get_result();
$rowVerifica = $resultVerifica->fetch_assoc();

if (!$rowVerifica) {
    die("Aluno não encontrado.");
}

if ($rowVerifica['status_aprovacao'] !== 'PENDENTE') {
    header("Location: ../../views/solicitacoes/listar.php?msg=ya");
    exit();
}

$stmtVerifica->close();

$stmt = $conexao->prepare(
    "UPDATE alunos SET status_aprovacao = ?, motivo_reprovacao = ? WHERE id_aluno = ?"
);
$stmt->bind_param("ssi", $status, $motivo, $id_aluno);

if ($stmt->execute()) {
    header("Location: ../../views/solicitacoes/listar.php?msg=ok");
    exit();
}

echo "Erro ao atualizar aluno: " . $stmt->error;
?>