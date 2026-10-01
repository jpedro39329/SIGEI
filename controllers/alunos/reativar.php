<?php
require_once "../../config/init.php";

// Apenas perfis autorizados (Escola, Educação Especial, SEDUC, ADMIN) podem reativar
exibirErroSe(!estaLogado(), "Usuário não autenticado.");
exigirTokenCSRF();

$userPerfil = $_SESSION['user_perfil'] ?? '';
$userId = (int) ($_SESSION['user_id'] ?? 0);

if (!in_array($userPerfil, ['USUARIO_ESCOLA', 'USUARIO_UE', 'ESCOLA', 'USUARIO_EDUCACAO_ESPECIAL', 'USUARIO_SEFISC', 'SEFISC', 'ADMIN', 'SEDUC'])) {
    die("Você não tem permissão para reativar alunos.");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../views/alunos/listar.php?aba=arquivados");
    exit();
}

$idAluno = (int) ($_POST['id_aluno'] ?? 0);

if ($idAluno <= 0) {
    header("Location: ../../views/alunos/listar.php?aba=arquivados&erro=" . urlencode("Aluno inválido."));
    exit();
}

// Verifica se o aluno existe e está arquivado
$check = $conexao->prepare("SELECT id_aluno, id_ue, status_aprovacao FROM alunos WHERE id_aluno = ?");
$check->bind_param("i", $idAluno);
$check->execute();
$resAluno = $check->get_result();
if ($resAluno->num_rows === 0) {
    $check->close();
    die("Aluno não encontrado.");
}
$aluno = $resAluno->fetch_assoc();
$check->close();

if ($aluno['status_aprovacao'] !== 'ARQUIVADO') {
    header("Location: ../../views/alunos/listar.php?aba=arquivados&erro=" . urlencode("Este aluno não está arquivado."));
    exit();
}

// Verifica restrição de escola se o perfil for escolar
if (in_array($userPerfil, ['USUARIO_ESCOLA', 'USUARIO_UE', 'ESCOLA'])) {
    $idEscola = idEscolaUsuario($conexao, $userId);
    if ((int) $aluno['id_ue'] !== $idEscola) {
        die("Aluno não pertence à sua unidade escolar.");
    }
}

// Reativa o aluno voltando para APROVADO
$stmt = $conexao->prepare("
    UPDATE alunos 
    SET status_aprovacao = 'APROVADO', 
        motivo_arquivamento = NULL, 
        data_arquivamento = NULL,
        arquivado_por_nome = NULL,
        arquivado_por_perfil = NULL
    WHERE id_aluno = ?
");

if ($stmt) {
    $stmt->bind_param("i", $idAluno);
    if ($stmt->execute()) {
        $stmt->close();
        header("Location: ../../views/alunos/listar.php?aba=aprovados&msg=reativado");
        exit();
    }
    $stmt->close();
}

header("Location: ../../views/alunos/listar.php?aba=arquivados&erro=" . urlencode("Erro ao reativar aluno: " . $conexao->error));
exit();
