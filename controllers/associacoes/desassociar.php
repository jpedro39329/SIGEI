<?php
require_once "../../config/init.php";

exigirPerfil(array('ADMIN', 'SEDUC', 'SUPERVISOR', 'USUARIO_EMPRESA'));
exigirTokenCSRF();

$id_associacao = (int) ($_POST['id_associacao'] ?? 0);

if ($id_associacao <= 0) {
    die("Associação inválida.");
}

// Busca dados da associação antes de desativar para registrar no histórico
$stmtBusca = $conexao->prepare("
    SELECT ass.id_aluno, ass.id_pae, a.nome AS aluno_nome, p.nome AS pae_nome, p.cpf AS pae_cpf
    FROM associacoes ass
    LEFT JOIN alunos a ON ass.id_aluno = a.id_aluno
    LEFT JOIN usuarios_pae p ON ass.id_pae = p.id_pae
    WHERE ass.id_associacao = ?
    LIMIT 1
");
$stmtBusca->bind_param("i", $id_associacao);
$stmtBusca->execute();
$dadosAssoc = $stmtBusca->get_result()->fetch_assoc();
$stmtBusca->close();

// Desativa a associação
$stmt = $conexao->prepare("UPDATE associacoes SET ativo = 0, data_fim = CURDATE() WHERE id_associacao = ?");
$stmt->bind_param("i", $id_associacao);

$redirectPae = (int) ($_POST['redirect_pae'] ?? 0);

if ($stmt->execute()) {
    if ($dadosAssoc && !empty($dadosAssoc['id_aluno'])) {
        registrarAuditoria($conexao, 'PAE', 'DESASSOCIAR_PAE', 'alunos', (int) $dadosAssoc['id_aluno'], [
            'id_pae' => (int) ($dadosAssoc['id_pae'] ?? 0),
            'pae_nome' => $dadosAssoc['pae_nome'] ?? '',
            'pae_cpf' => preg_replace('/\D/', '', $dadosAssoc['pae_cpf'] ?? ''),
            'aluno_nome' => $dadosAssoc['aluno_nome'] ?? ''
        ]);
    }

    if ($redirectPae > 0) {
        header("Location: ../../views/associacoes/visualizar.php?id=" . $redirectPae . "&msg=removido");
    } else {
        header("Location: ../../views/associacoes/gerenciar.php?msg=removido");
    }
    exit();
}

echo "Erro ao desassociar: " . $stmt->error;
?>