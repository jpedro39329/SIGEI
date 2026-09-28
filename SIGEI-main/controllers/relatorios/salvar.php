<?php
require_once "../../config/init.php";

exigirPerfil(array('PAE'));
exigirTokenCSRF();

$id_pae = (int) $_SESSION['user_id'];
$id_aluno = (int) ($_POST['id_aluno'] ?? 0);
$tipo = $_POST['tipo'] ?? ''; // DIARIO ou MENSAL
$descricao = trim($_POST['descricao'] ?? '');

if ($id_aluno <= 0 || !in_array($tipo, array('DIARIO', 'MENSAL')) || $descricao === '') {
    die("Dados do relatório inválidos.");
}

// Verifica se o aluno está associado a este PAE
$stmtVerifica = $conexao->prepare(
    "SELECT id_associacao FROM associacoes
     WHERE id_aluno = ? AND id_pae = ? AND ativo = 1
     LIMIT 1"
);
$stmtVerifica->bind_param("ii", $id_aluno, $id_pae);
$stmtVerifica->execute();
$resultVerifica = $stmtVerifica->get_result();

if ($resultVerifica->num_rows == 0) {
    die("Aluno não está associado a você.");
}

$rowAssoc = $resultVerifica->fetch_assoc();
$id_associacao = (int) $rowAssoc['id_associacao'];

$stmtVerifica->close();

// Insere o relatório
$stmt = $conexao->prepare(
    "INSERT INTO relatorios (id_associacao, tipo, descricao) VALUES (?, ?, ?)"
);
$stmt->bind_param("iss", $id_associacao, $tipo, $descricao);

if ($stmt->execute()) {
    header("Location: ../../views/relatorios/listar.php?msg=ok");
    exit();
}

echo "Erro ao salvar relatório: " . $stmt->error;
?>