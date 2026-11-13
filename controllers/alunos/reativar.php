<?php
require_once "../../config/init.php";

exigirPerfil(array('USUARIO_ESCOLA', 'USUARIO_UE', 'ESCOLA', 'USUARIO_EDUCACAO_ESPECIAL', 'USUARIO_SEFISC', 'SEFISC', 'ADMIN', 'SEDUC'));
exigirTokenCSRF();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../views/alunos/listar.php?aba=arquivados");
    exit();
}

$userPerfil = $_SESSION['user_perfil'] ?? '';
$userId = (int) ($_SESSION['user_id'] ?? 0);
$idAluno = (int) ($_POST['id_aluno'] ?? 0);

if ($idAluno <= 0) {
    header("Location: ../../views/alunos/listar.php?aba=arquivados&erro=" . urlencode("Aluno inválido."));
    exit();
}

// Verifica se o aluno existe e está arquivado, incluindo a URE da escola
$check = $conexao->prepare("
    SELECT a.id_aluno, a.id_ue, a.status_aprovacao, ue.id_ure
    FROM alunos a
    LEFT JOIN unidades_escolares ue ON a.id_ue = ue.id_ue
    WHERE a.id_aluno = ?
");
$check->bind_param("i", $idAluno);
$check->execute();
$resAluno = $check->get_result();
if ($resAluno->num_rows === 0) {
    $check->close();
    redirecionarAcessoNegado();
}
$aluno = $resAluno->fetch_assoc();
$check->close();

// Verifica restrição de escola/URE antes de validar o status do aluno
if (in_array($userPerfil, ['USUARIO_ESCOLA', 'USUARIO_UE', 'ESCOLA'], true)) {
    $idEscola = idEscolaUsuario($conexao, $userId);
    if ((int) $aluno['id_ue'] !== $idEscola) {
        redirecionarAcessoNegado();
    }
} elseif (in_array($userPerfil, ['USUARIO_EDUCACAO_ESPECIAL', 'USUARIO_SEFISC', 'SEFISC', 'DIRIGENTE'], true)) {
    $idUre = (int) ($_SESSION['id_ure'] ?? idUreUsuario($conexao, $userId));
    if (($idUre <= 0 || (int) $aluno['id_ure'] !== $idUre)) {
        redirecionarAcessoNegado();
    }
}

if ($aluno['status_aprovacao'] !== 'ARQUIVADO') {
    header("Location: ../../views/alunos/listar.php?aba=arquivados&erro=" . urlencode("Este aluno não está arquivado."));
    exit();
}

// Reativa o aluno voltando para APROVADO
$stmt = $conexao->prepare("
    UPDATE alunos 
    SET status_aprovacao = 'APROVADO', 
        motivo_arquivamento = NULL, 
        data_arquivamento = NULL,
        arquivado_por_nome = NULL,
        arquivado_por_cpf = NULL,
        arquivado_por_perfil = NULL
    WHERE id_aluno = ?
");

if ($stmt) {
    $stmt->bind_param("i", $idAluno);
    if ($stmt->execute()) {
        $stmt->close();
        registrarAuditoria($conexao, 'ALUNOS', 'REATIVAR', 'alunos', $idAluno, [
            'aluno_id' => $idAluno,
            'reativado_por' => $_SESSION['user_name'] ?? 'Usuário',
            'perfil' => $userPerfil
        ]);
        header("Location: ../../views/alunos/listar.php?aba=aprovados&msg=reativado");
        exit();
    }
    $stmt->close();
}

header("Location: ../../views/alunos/listar.php?aba=arquivados&erro=" . urlencode("Erro ao reativar aluno: " . $conexao->error));
exit();
