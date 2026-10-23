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

if (mb_strlen($descricao, 'UTF-8') > 5000) {
    die("A descrição do relatório excede o limite de 5000 caracteres.");
}

// Verifica se o aluno está com atendimento aprovado e associado a este PAE
$stmtVerifica = $conexao->prepare(
    "SELECT ass.id_associacao 
     FROM associacoes ass
     JOIN alunos a ON ass.id_aluno = a.id_aluno
     WHERE ass.id_aluno = ? AND ass.id_pae = ? AND ass.ativo = 1 AND a.status_aprovacao = 'APROVADO'
     LIMIT 1"
);
$stmtVerifica->bind_param("ii", $id_aluno, $id_pae);
$stmtVerifica->execute();
$resultVerifica = $stmtVerifica->get_result();

if ($resultVerifica->num_rows == 0) {
    die("Aluno não está associado a você ou o atendimento não está aprovado.");
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
    registrarAuditoria($conexao, 'PAE', 'REGISTRAR_RELATORIO', 'alunos', $id_aluno, [
        'tipo' => $tipo === 'DIARIO' ? 'Diário' : 'Mensal',
        'id_associacao' => $id_associacao
    ]);

    header("Location: ../../views/relatorios/listar.php?msg=ok");
    exit();
}

echo "Erro ao salvar relatório: " . $stmt->error;
?>