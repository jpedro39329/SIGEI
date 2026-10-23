<?php
require_once "../../config/init.php";

exigirPerfil(array('USUARIO_ESCOLA'));
exigirTokenCSRF();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../views/alunos/pendentes.php");
    exit();
}

$id_aluno = (int) ($_POST['id_aluno'] ?? 0);
$id_usuario_escola = (int) $_SESSION['user_id'];
$id_escola = idEscolaUsuario($conexao, $id_usuario_escola);

if ($id_aluno <= 0 || $id_escola <= 0) {
    die("Aluno ou escola inválidos.");
}

$stmtVerifica = $conexao->prepare(
    "SELECT id_aluno, nome, status_aprovacao
     FROM alunos
     WHERE id_aluno = ?
       AND id_ue = ?
       AND status_aprovacao IN ('REPROVADO', 'PENDENTE_CORRECAO')"
);
$stmtVerifica->bind_param("ii", $id_aluno, $id_escola);
$stmtVerifica->execute();
$resVerifica = $stmtVerifica->get_result();

if ($resVerifica->num_rows == 0) {
    die("Aluno não encontrado, não pertence à sua escola ou não está pendente de reenvio.");
}

$alunoDados = $resVerifica->fetch_assoc();
$stmtVerifica->close();

$stmt = $conexao->prepare(
    "UPDATE alunos
     SET status_aprovacao = 'PENDENTE',
         motivo_reprovacao = ''
     WHERE id_aluno = ?
       AND id_ue = ?"
);
$stmt->bind_param("ii", $id_aluno, $id_escola);

if ($stmt->execute()) {
    // Garante que nenhuma associação de PAE permaneça ativa enquanto o aluno volta para reanálise
    $stmtDesativarAssoc = $conexao->prepare("UPDATE associacoes SET ativo = 0, data_fim = CURDATE() WHERE id_aluno = ? AND ativo = 1");
    if ($stmtDesativarAssoc) {
        $stmtDesativarAssoc->bind_param("i", $id_aluno);
        $stmtDesativarAssoc->execute();
        $stmtDesativarAssoc->close();
    }

    registrarAuditoria($conexao, 'ALUNOS', 'REENVIAR', 'alunos', $id_aluno, [
        'nome' => $alunoDados['nome'] ?? '',
        'status_anterior' => $alunoDados['status_aprovacao'] ?? '',
        'status_pos_reenvio' => 'PENDENTE'
    ]);

    header("Location: ../../views/alunos/pendentes.php?msg=reenviado");
    exit();
}

echo "Erro ao reenviar solicitação: " . $stmt->error;
?>
