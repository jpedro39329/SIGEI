<?php
require_once "../config/init.php";

// Apenas ADMIN e USUARIO_EMPRESA podem associar PAE a aluno
exigirPerfil(array('ADMIN', 'USUARIO_EMPRESA'));
exigirTokenCSRF();

$id_aluno = (int) ($_POST['id_aluno'] ?? 0);
$id_pae = (int) ($_POST['id_pae'] ?? 0);
$data_inicio = $_POST['data_inicio'] ?? date('Y-m-d');

if ($id_aluno <= 0 || $id_pae <= 0) {
    die("Dados inválidos.");
}

// Verifica se o aluno existe e está APROVADO
$stmtAluno = $conexao->prepare("SELECT id_aluno FROM alunos WHERE id_aluno = ? AND status_aprovacao = 'APROVADO'");
$stmtAluno->bind_param("i", $id_aluno);
$stmtAluno->execute();

if ($stmtAluno->get_result()->num_rows == 0) {
    die("Aluno não encontrado ou não está aprovado.");
}

$stmtAluno->close();

// Verifica se o PAE existe e está ativo
$stmtPae = $conexao->prepare("SELECT id_pae FROM paes WHERE id_pae = ? AND ativo = 1");
$stmtPae->bind_param("i", $id_pae);
$stmtPae->execute();

if ($stmtPae->get_result()->num_rows == 0) {
    die("PAE não encontrado ou está inativo.");
}

$stmtPae->close();

// RN01: PAE não pode ter mais de 3 alunos ativos
$totalAlunosPae = contarAlunosPAE($conexao, $id_pae);
if ($totalAlunosPae >= 3) {
    die("Este PAE já possui 3 alunos ativos associados.");
}

// RN02: Aluno não pode ter mais de 3 PAEs ativos
$totalPaesAluno = contarPAEsAluno($conexao, $id_aluno);
if ($totalPaesAluno >= 3) {
    die("Este aluno já possui 3 PAEs ativos associados.");
}

// Verifica se o aluno já está associado a este PAE
$stmtExiste = $conexao->prepare(
    "SELECT id_associacao FROM associacoes WHERE id_aluno = ? AND id_pae = ? AND ativo = 1"
);
$stmtExiste->bind_param("ii", $id_aluno, $id_pae);
$stmtExiste->execute();

if ($stmtExiste->get_result()->num_rows > 0) {
    die("Este aluno já está associado a este PAE.");
}

$stmtExiste->close();

// Cria a associação
$stmt = $conexao->prepare(
    "INSERT INTO associacoes (id_aluno, id_pae, ativo, data_inicio) VALUES (?, ?, 1, ?)"
);
$stmt->bind_param("iis", $id_aluno, $id_pae, $data_inicio);

if ($stmt->execute()) {
    header("Location: ../views/associacoes.php?msg=ok");
    exit();
}

echo "Erro ao associar: " . $stmt->error;
?>