<?php
require_once "../../config/init.php";
require_once "../upload.php";

// Apenas USUARIO_EDUCACAO_ESPECIAL, ADMIN ou SEDUC podem deliberar sobre solicitações
exigirPerfil(array('USUARIO_EDUCACAO_ESPECIAL', 'ADMIN', 'SEDUC'));
exigirTokenCSRF();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../views/solicitacoes/listar.php");
    exit();
}

$id_aluno = (int) ($_POST['id_aluno'] ?? 0);
$acao     = $_POST['acao'] ?? '';
$motivo   = null;

if ($id_aluno <= 0) {
    die("Aluno inválido.");
}

if ($acao === 'APROVAR') {
    $status = 'APROVADO';
    $motivo = null;
} elseif ($acao === 'CORRECAO') {
    $status = 'PENDENTE_CORRECAO';
    $motivo = trim($_POST['motivo'] ?? '');

    if ($motivo === '') {
        header("Location: ../../views/solicitacoes/analisar.php?id=$id_aluno&erro=motivo_correcao");
        exit();
    }
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

// Verifica que o aluno exista e esteja em status analisável
$stmtVerifica = $conexao->prepare("SELECT id_aluno, status_aprovacao FROM alunos WHERE id_aluno = ?");
$stmtVerifica->bind_param("i", $id_aluno);
$stmtVerifica->execute();
$resultVerifica = $stmtVerifica->get_result();
$rowVerifica = $resultVerifica->fetch_assoc();

if (!$rowVerifica) {
    die("Aluno não encontrado.");
}

if (!in_array($rowVerifica['status_aprovacao'], ['PENDENTE', 'REPROVADO', 'PENDENTE_CORRECAO'])) {
    header("Location: ../../views/solicitacoes/listar.php?msg=ya");
    exit();
}

$stmtVerifica->close();

// Upload opcional de anexo (parecer ou documento de suporte da Educação Especial)
if (isset($_FILES['anexo']) && $_FILES['anexo']['error'] === UPLOAD_ERR_OK) {
    $caminhoArquivo = uploadArquivo($_FILES['anexo'], 'laudos');
    if ($caminhoArquivo !== false) {
        $nomeArquivo = $_FILES['anexo']['name'] ?? 'Parecer Educação Especial';
        $descAnexo = ($acao === 'CORRECAO') ? 'Solicitação de Ajuste / Diligência' : 'Parecer Técnico da Deliberação';
        
        $stmtLaudo = $conexao->prepare(
            "INSERT INTO laudos (id_aluno, nome_arquivo, caminho_arquivo, descricao) VALUES (?, ?, ?, ?)"
        );
        $stmtLaudo->bind_param("isss", $id_aluno, $nomeArquivo, $caminhoArquivo, $descAnexo);
        $stmtLaudo->execute();
        $stmtLaudo->close();
    }
}

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